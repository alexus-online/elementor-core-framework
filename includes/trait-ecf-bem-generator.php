<?php
/**
 * Auto-BEM Class Generator — walks an Elementor V4 element tree, derives BEM
 * class names from each element's _title (or elType fallback), and on apply
 * registers those classes in Elementor's Global Classes Registry + writes the
 * class-IDs into each widget's settings.classes.value.
 *
 * Flat-BEM model (Bricks/ACSS-style): all descendants of the chosen block
 * become `<block>__<element>`, regardless of nesting depth.
 *
 * REST endpoints (wired in trait-ecf-rest-api.php):
 *   POST /wp-json/ecf-framework/v1/bem/preview  — { post_id, element_id }
 *   POST /wp-json/ecf-framework/v1/bem/apply    — { post_id, classes: {element_id: bem-class} }
 */

if ( ! defined( 'ABSPATH' ) ) exit;

trait ECF_Framework_BEM_Generator_Trait {

	public function rest_bem_preview( \WP_REST_Request $request ) {
		$post_id    = (int) $request->get_param( 'post_id' );
		$element_id = sanitize_text_field( (string) $request->get_param( 'element_id' ) );

		if ( $post_id <= 0 || $element_id === '' ) {
			return new \WP_Error( 'ecf_bem_invalid', 'post_id + element_id required', [ 'status' => 400 ] );
		}

		$tree = $this->load_elementor_tree( $post_id );
		if ( $tree === null ) {
			return new \WP_Error( 'ecf_bem_no_data', 'post has no _elementor_data', [ 'status' => 404 ] );
		}

		$block = $this->find_element_by_id( $tree, $element_id );
		if ( $block === null ) {
			$sample_ids = $this->sample_element_ids( $tree, 12 );
			return new \WP_Error(
				'ecf_bem_not_found',
				sprintf(
					'Element "%s" nicht im gespeicherten _elementor_data des Posts %d. Erste IDs im Tree: %s. Häufigste Ursache: Editor-Änderungen noch nicht gespeichert — bitte "Aktualisieren" klicken und erneut versuchen.',
					$element_id,
					$post_id,
					empty( $sample_ids ) ? '(leer)' : implode( ', ', $sample_ids )
				),
				[ 'status' => 404, 'sample_ids' => $sample_ids ]
			);
		}

		$block_label_raw   = $this->extract_element_label( $block );
		$block_is_default  = $this->is_default_label( $block );
		$block_slug        = $this->slugify_for_bem( $block_label_raw );

		// Collect descendants flat (BEM-purist: any nesting depth → block__element)
		$descendants = [];
		$this->walk_descendants( $block, $descendants, 1 );

		// Slug-dedupe across descendants only (block itself is separate)
		$seen   = [];
		$result = [];
		foreach ( $descendants as $node ) {
			$raw_label = $this->extract_element_label( $node['element'] );
			$slug      = $this->slugify_for_bem( $raw_label );
			if ( $slug === '' ) {
				$slug = sanitize_html_class( str_replace( 'e-', '', (string) ( $node['element']['elType'] ?? 'el' ) ) ) ?: 'el';
			}
			if ( isset( $seen[ $slug ] ) ) {
				$seen[ $slug ]++;
				$slug .= '-' . $seen[ $slug ];
			} else {
				$seen[ $slug ] = 1;
			}
			$result[] = [
				'element_id'        => (string) ( $node['element']['id'] ?? '' ),
				'el_type'           => (string) ( $node['element']['elType'] ?? '' ),
				'original_label'    => $raw_label,
				'is_default_label'  => $this->is_default_label( $node['element'] ),
				'depth'             => (int) $node['depth'],
				'suggested_element' => $slug,
				'suggested_class'   => $block_slug !== '' ? ( $block_slug . '__' . $slug ) : $slug,
			];
		}

		return rest_ensure_response( [
			'success'           => true,
			'block_element_id'  => $element_id,
			'block_label'       => $block_label_raw,
			'block_is_default'  => $block_is_default,
			'block_suggested'   => $block_slug,
			'descendants'       => $result,
			'descendant_count'  => count( $result ),
		] );
	}

	public function rest_bem_apply( \WP_REST_Request $request ) {
		$payload    = (array) $request->get_json_params();
		$post_id    = (int) ( $payload['post_id'] ?? 0 );
		$classes    = (array) ( $payload['classes'] ?? [] );

		if ( $post_id <= 0 || empty( $classes ) ) {
			return new \WP_Error( 'ecf_bem_invalid', 'post_id + non-empty classes required', [ 'status' => 400 ] );
		}

		if ( ! class_exists( '\Elementor\Modules\GlobalClasses\Global_Classes_Repository' ) ) {
			return new \WP_Error( 'ecf_bem_no_registry', 'Global Classes module unavailable', [ 'status' => 500 ] );
		}

		// Sanitize map: { element_id => bem-class-label }
		$clean_map = [];
		foreach ( $classes as $el_id => $bem_label ) {
			$el_id     = sanitize_text_field( (string) $el_id );
			$bem_label = $this->sanitize_bem_label( (string) $bem_label );
			if ( $el_id === '' || $bem_label === '' ) continue;
			$clean_map[ $el_id ] = $bem_label;
		}
		if ( empty( $clean_map ) ) {
			return new \WP_Error( 'ecf_bem_invalid', 'no valid class names after sanitize', [ 'status' => 400 ] );
		}

		// Load tree
		$raw = (string) get_post_meta( $post_id, '_elementor_data', true );
		if ( $raw === '' ) {
			return new \WP_Error( 'ecf_bem_no_data', 'post has no _elementor_data', [ 'status' => 404 ] );
		}
		$tree = json_decode( $raw, true );
		if ( ! is_array( $tree ) ) {
			return new \WP_Error( 'ecf_bem_corrupt', 'failed to parse _elementor_data', [ 'status' => 500 ] );
		}

		// Existing Global-Class label→id lookup (case-insensitive)
		$repo          = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make();
		$current       = $repo->all()->get();
		$existing_items = (array) ( $current['items'] ?? [] );
		$existing_order = (array) ( $current['order'] ?? [] );
		$label_to_id   = [];
		foreach ( $existing_items as $id => $item ) {
			if ( ! is_array( $item ) ) continue;
			$label_to_id[ strtolower( (string) ( $item['label'] ?? '' ) ) ] = (string) $id;
		}

		// Build {element_id => class_id}, registering new classes as needed.
		$el_to_class_id = [];
		$new_class_ids  = [];
		foreach ( $clean_map as $el_id => $bem_label ) {
			$lookup = strtolower( $bem_label );
			if ( isset( $label_to_id[ $lookup ] ) ) {
				$el_to_class_id[ $el_id ] = $label_to_id[ $lookup ];
				continue;
			}
			$class_id = $this->generate_bem_class_id( $bem_label, $existing_items );
			$existing_items[ $class_id ] = [
				'id'         => $class_id,
				'label'      => $bem_label,
				'type'       => 'class',
				'variants'   => [],
				'sync_to_v3' => false,
			];
			$label_to_id[ $lookup ]    = $class_id;
			$el_to_class_id[ $el_id ]  = $class_id;
			$new_class_ids[]           = $class_id;
			if ( ! in_array( $class_id, $existing_order, true ) ) {
				$existing_order[] = $class_id;
			}
		}

		// Bulk-write registry
		try {
			$repo->put( $existing_items, $existing_order );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'ecf_bem_registry_write', 'Global Classes write failed: ' . $e->getMessage(), [ 'status' => 500 ] );
		}

		// Append class-IDs into each element's settings.classes.value
		$applied_to = 0;
		$this->walk_apply_classes( $tree, $el_to_class_id, $applied_to );

		// Re-encode + persist
		$reencoded = wp_json_encode( $tree, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( $reencoded === false ) {
			return new \WP_Error( 'ecf_bem_encode', 'failed to re-encode _elementor_data', [ 'status' => 500 ] );
		}
		update_post_meta( $post_id, '_elementor_data', wp_slash( $reencoded ) );
		delete_post_meta( $post_id, '_elementor_css' );

		// Global Elementor cache bust so frontend picks up the new CSS
		if ( class_exists( '\Elementor\Plugin' ) ) {
			try {
				\Elementor\Plugin::instance()->files_manager->clear_cache();
			} catch ( \Throwable $e ) {}
		}

		return rest_ensure_response( [
			'success'         => true,
			'classes_created' => count( $new_class_ids ),
			'classes_reused'  => count( $clean_map ) - count( $new_class_ids ),
			'elements_tagged' => $applied_to,
		] );
	}

	// ─── Helpers ──────────────────────────────────────────────────────────

	/** Load + decode _elementor_data; returns array tree or null. */
	private function load_elementor_tree( int $post_id ): ?array {
		$raw = (string) get_post_meta( $post_id, '_elementor_data', true );
		if ( $raw === '' ) return null;
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/** Collect first N element IDs from the tree (for debug error responses). */
	private function sample_element_ids( array $elements, int $limit ): array {
		$out = [];
		$this->sample_ids_walk( $elements, $out, $limit );
		return $out;
	}

	private function sample_ids_walk( array $elements, array &$out, int $limit ): void {
		foreach ( $elements as $el ) {
			if ( count( $out ) >= $limit ) return;
			if ( ! is_array( $el ) ) continue;
			$id = (string) ( $el['id'] ?? '' );
			$type = (string) ( $el['elType'] ?? '?' );
			if ( $id !== '' ) $out[] = $id . '(' . $type . ')';
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$this->sample_ids_walk( $el['elements'], $out, $limit );
			}
		}
	}

	/** Recursive DFS for element with matching id. */
	private function find_element_by_id( array $elements, string $id ): ?array {
		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) ) continue;
			if ( ( $el['id'] ?? '' ) === $id ) return $el;
			$children = $el['elements'] ?? [];
			if ( is_array( $children ) && ! empty( $children ) ) {
				$hit = $this->find_element_by_id( $children, $id );
				if ( $hit !== null ) return $hit;
			}
		}
		return null;
	}

	/** Collect all descendants flat (depth-first) with depth marker. */
	private function walk_descendants( array $block, array &$out, int $depth ): void {
		$children = $block['elements'] ?? [];
		if ( ! is_array( $children ) ) return;
		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) continue;
			$out[] = [ 'element' => $child, 'depth' => $depth ];
			if ( ! empty( $child['elements'] ) ) {
				$this->walk_descendants( $child, $out, $depth + 1 );
			}
		}
	}

	/**
	 * Read element's structure-panel label. Priority:
	 *   1. editor_settings.title   — V4 atomic widgets (incl. Layrix's own
	 *      Section Inner Div_Block) + custom Navigator rename in V4
	 *   2. settings._title         — V1 classic widgets ("Edit Label" feature)
	 *   3. elType (stripped of e-) — last-resort fallback
	 */
	private function extract_element_label( array $element ): string {
		$editor_title = (string) ( $element['editor_settings']['title'] ?? '' );
		if ( trim( $editor_title ) !== '' ) return $editor_title;

		$v1_title = (string) ( $element['settings']['_title'] ?? '' );
		if ( trim( $v1_title ) !== '' ) return $v1_title;

		$el_type = (string) ( $element['elType'] ?? '' );
		if ( $el_type !== '' && strpos( $el_type, 'e-' ) === 0 ) {
			$el_type = substr( $el_type, 2 );
		}
		return $el_type !== '' ? $el_type : 'element';
	}

	/** True when element has no custom title set by the user. */
	private function is_default_label( array $element ): bool {
		return trim( (string) ( $element['editor_settings']['title'] ?? '' ) ) === ''
			&& trim( (string) ( $element['settings']['_title'] ?? '' ) ) === '';
	}

	/**
	 * Slugify a label for BEM use.
	 * - Transliterate accents (Überschrift → uberschrift)
	 * - Lowercase, strip non [a-z0-9-]
	 * - Collapse runs of hyphens
	 * - Trim leading/trailing hyphens
	 */
	private function slugify_for_bem( string $label ): string {
		$s = (string) $label;
		if ( function_exists( 'remove_accents' ) ) $s = remove_accents( $s );
		$s = strtolower( $s );
		$s = preg_replace( '/[^a-z0-9]+/', '-', $s );
		$s = preg_replace( '/-+/', '-', (string) $s );
		return trim( (string) $s, '-' );
	}

	/**
	 * Sanitize a user-edited full BEM class label coming from the modal.
	 * Allowed: a-z 0-9, '-' and '_' (BEM uses double-underscore + dash-dash).
	 * Must start with a letter; max 64 chars (sensible cap).
	 */
	private function sanitize_bem_label( string $label ): string {
		$s = strtolower( trim( $label ) );
		$s = preg_replace( '/[^a-z0-9_\-]+/', '', $s );
		$s = (string) $s;
		if ( $s === '' ) return '';
		if ( ! preg_match( '/^[a-z]/', $s ) ) return '';
		if ( strlen( $s ) > 64 ) $s = substr( $s, 0, 64 );
		return $s;
	}

	/**
	 * Generate a deterministic Global-Class ID; on hash collision with an
	 * existing different label, append a uuid suffix.
	 */
	private function generate_bem_class_id( string $bem_label, array $existing_items ): string {
		$base = 'g-bem-' . substr( md5( $bem_label ), 0, 10 );
		if ( ! isset( $existing_items[ $base ] ) ) return $base;
		// Collision on same hash but different label — unlikely, fallback to uuid suffix
		return 'g-bem-' . substr( md5( $bem_label . wp_generate_uuid4() ), 0, 10 );
	}

	/**
	 * Recursive walk that appends class-IDs into matching elements'
	 * settings.classes.value array. Idempotent — same class-ID is not added
	 * twice on the same element.
	 */
	private function walk_apply_classes( array &$elements, array $el_to_class_id, int &$applied_to ): void {
		foreach ( $elements as &$el ) {
			if ( ! is_array( $el ) ) continue;
			$id = (string) ( $el['id'] ?? '' );
			if ( $id !== '' && isset( $el_to_class_id[ $id ] ) ) {
				$class_id = $el_to_class_id[ $id ];

				if ( ! isset( $el['settings'] ) || ! is_array( $el['settings'] ) ) {
					$el['settings'] = [];
				}
				$current = $el['settings']['classes'] ?? null;
				$value   = [];
				if ( is_array( $current ) && isset( $current['value'] ) && is_array( $current['value'] ) ) {
					$value = array_values( array_filter( $current['value'], 'is_string' ) );
				}
				if ( ! in_array( $class_id, $value, true ) ) {
					$value[] = $class_id;
				}
				$el['settings']['classes'] = [
					'$$type' => 'classes',
					'value'  => $value,
				];
				$applied_to++;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$this->walk_apply_classes( $el['elements'], $el_to_class_id, $applied_to );
			}
		}
	}
}
