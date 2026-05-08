<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class-Refactor: Find & Replace für Klassennamen in _elementor_data.
 * Scope: Klassen-NAMEN umbenennen — V3 (klassische Widgets, _css_classes
 * als Space-separated String) + V4 (Atomic Widgets, klassen-name als
 * String in classes.value).
 *
 * NICHT im Scope: Layrix-Global-Class-IDs (`g-ecf-...`). Die werden in
 * V4 Atomic Widgets als ID statt Name referenziert; ein Rename dieser
 * Klassen läuft über Layrix-Settings, nicht über dieses Tool.
 *
 * Sicherheit / Daten-Integrität:
 * - Default-Workflow ist preview → confirm → apply
 * - Apply schreibt nur Posts die im Preview als Treffer markiert waren
 * - JSON-Walker ersetzt nur an Klassen-Slots (`_css_classes` V3,
 *   `{$$type:"classes",value:[...]}` V4) — keine Kollateral-Hits in
 *   URLs, Text-Content, Labels oder Custom-CSS, selbst wenn der gleiche
 *   String dort wörtlich vorkommt.
 * - Pro Post wird _elementor_data dekodiert, modifiziert, neu encodiert
 *   und mit update_post_meta zurückgeschrieben (single-shot, kein partial)
 * - JSON-Validity wird nach Replace nochmal geprüft (defense-in-depth)
 * - Nach Erfolg: Elementor-CSS-Cache invalidiert
 */
trait ECF_Framework_Class_Refactor_Trait {
    /**
     * Findet alle Posts in denen die alte Klasse vorkommt.
     */
    public function scan_class_usage_for_refactor(string $old_class): array {
        $old_class = trim($old_class);
        if ($old_class === '') return [];

        global $wpdb;
        $sql = $wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_type
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_elementor_data'
              AND pm.meta_value LIKE %s
              AND p.post_status IN ('publish', 'draft', 'private', 'pending', 'future')
            LIMIT 500
        ", '%' . $wpdb->esc_like($old_class) . '%');

        $rows = $wpdb->get_results($sql);
        if (empty($rows)) return [];

        $results = [];
        foreach ($rows as $row) {
            $haystack = (string) get_post_meta((int) $row->ID, '_elementor_data', true);
            $hits = $this->count_class_hits_in_data($haystack, $old_class);
            if ($hits === 0) continue;
            $results[] = [
                'post_id'    => (int) $row->ID,
                'post_title' => $row->post_title !== '' ? $row->post_title : sprintf('(#%d)', (int) $row->ID),
                'post_type'  => (string) $row->post_type,
                'edit_url'   => admin_url('post.php?post=' . (int) $row->ID . '&action=elementor'),
                'hits'       => $hits,
            ];
        }
        usort($results, static fn($a, $b) => ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0));
        return $results;
    }

    private function count_class_hits_in_data(string $data, string $old_class): int {
        $decoded = json_decode($data, true);
        if (!is_array($decoded)) return 0;
        $count = 0;
        $this->walk_class_locations($decoded, function ($entry) use ($old_class, &$count) {
            // entry ist entweder V3 _css_classes-String (space-separated) oder ein
            // einzelner V4 atomic classes.value[]-Eintrag.
            if (!is_string($entry)) return;
            $tokens = preg_split('/\s+/', trim($entry), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($tokens as $tok) {
                if ($tok === $old_class) $count++;
            }
        });
        return $count;
    }

    /**
     * Geht den Tree rekursiv durch und ruft den Callback genau an den
     * Stellen auf, an denen Klassennamen leben:
     *  - V3: `_css_classes` (Space-separated String)
     *  - V4: `{$$type:"classes", value:[...]}` Prop
     * Andere Strings (Text-Content, URLs, Labels, Custom-CSS) werden ignoriert.
     */
    private function walk_class_locations(array $node, callable $cb): void {
        foreach ($node as $key => $val) {
            if ($key === '_css_classes' && is_string($val)) {
                $cb($val);
                continue;
            }
            if (is_array($val)) {
                $is_classes_prop = isset($val['$$type'])
                    && $val['$$type'] === 'classes'
                    && isset($val['value']) && is_array($val['value']);
                if ($is_classes_prop) {
                    foreach ($val['value'] as $entry) {
                        $cb($entry);
                    }
                    continue;
                }
                $this->walk_class_locations($val, $cb);
            }
        }
    }

    /**
     * Führt den Replace auf den gegebenen Post-IDs durch.
     * Returnt Statistik: posts_updated, total_replacements.
     */
    public function apply_class_refactor(string $old_class, string $new_class, array $post_ids): array {
        $old_class = trim($old_class);
        $new_class = trim($new_class);
        if ($old_class === '' || $new_class === '' || $old_class === $new_class) {
            return ['posts_updated' => 0, 'total_replacements' => 0, 'errors' => []];
        }
        // Klassennamen müssen valide CSS-Klassen sein
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $new_class)) {
            return ['posts_updated' => 0, 'total_replacements' => 0, 'errors' => ['Ungültiger neuer Klassenname.']];
        }

        $errors = [];
        // Hinweis: Wenn old_class eine Layrix-Global-Class ist, werden in V4
        // Atomic Widgets nur Posts erfasst die den Klassen-NAMEN als String
        // halten. Class-ID-References (`g-ecf-…`) bleiben bewusst unangetastet —
        // Rename einer Global-Class gehört in Layrix-Settings, nicht hier.
        if ($this->lookup_class_id_in_registry($old_class) !== '') {
            $errors[] = sprintf(
                'Hinweis: "%s" ist eine Layrix-Global-Klasse. V4-Atomic-Referenzen über Class-ID werden nicht angefasst — Rename via Layrix-Settings.',
                $old_class
            );
        }

        $posts_updated = 0;
        $total_replacements = 0;

        foreach ($post_ids as $post_id) {
            $post_id = (int) $post_id;
            if ($post_id <= 0) continue;
            $raw = (string) get_post_meta($post_id, '_elementor_data', true);
            if ($raw === '') continue;

            $modified = $this->replace_class_in_data($raw, $old_class, $new_class, $count);
            if ($count === 0 || $modified === $raw) continue;

            // Validate JSON before write — niemals broken state speichern
            $decoded = json_decode($modified, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = sprintf('Post %d: JSON nach Replace ungültig — übersprungen', $post_id);
                continue;
            }

            update_post_meta($post_id, '_elementor_data', wp_slash($modified));
            $posts_updated++;
            $total_replacements += $count;
            // Per-Post Elementor-Cache invalidieren
            delete_post_meta($post_id, '_elementor_css');
        }

        // Globaler Cache-Bust
        if (function_exists('\Elementor\Plugin::instance')) {
            try {
                \Elementor\Plugin::instance()->files_manager->clear_cache();
            } catch (\Throwable $e) {}
        }

        return [
            'posts_updated'      => $posts_updated,
            'total_replacements' => $total_replacements,
            'errors'             => $errors,
        ];
    }

    private function replace_class_in_data(string $data, string $old_class, string $new_class, ?int &$count): string {
        $count = 0;
        $decoded = json_decode($data, true);
        if (!is_array($decoded)) return $data;

        $this->walk_and_replace_classes($decoded, $old_class, $new_class, $count);
        if ($count === 0) return $data;

        // Elementor speichert _elementor_data ohne escaped slashes / unicode.
        $reencoded = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($reencoded === false) {
            $count = 0;
            return $data;
        }
        return $reencoded;
    }

    private function walk_and_replace_classes(array &$node, string $old_class, string $new_class, int &$count): void {
        foreach ($node as $key => &$val) {
            // V3: _css_classes ist ein space-separierter String mit Klassennamen.
            if ($key === '_css_classes' && is_string($val)) {
                $tokens = preg_split('/\s+/', trim($val), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $changed = false;
                foreach ($tokens as $i => $tok) {
                    if ($tok === $old_class) {
                        $tokens[$i] = $new_class;
                        $count++;
                        $changed = true;
                    }
                }
                if ($changed) $val = implode(' ', $tokens);
                continue;
            }
            if (!is_array($val)) continue;

            // V4 atomic classes prop: {"$$type":"classes","value":["g-ecf-...","name"]}
            // Wir ersetzen nur Klassen-NAMEN-Strings, keine Class-IDs.
            $is_classes_prop = isset($val['$$type'])
                && $val['$$type'] === 'classes'
                && isset($val['value']) && is_array($val['value']);
            if ($is_classes_prop) {
                foreach ($val['value'] as $i => $entry) {
                    if (!is_string($entry)) continue;
                    if ($entry === $old_class) {
                        $val['value'][$i] = $new_class;
                        $count++;
                    }
                }
                continue;
            }

            $this->walk_and_replace_classes($val, $old_class, $new_class, $count);
        }
    }

    private function lookup_class_id_in_registry(string $label): string {
        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) return '';
        try {
            $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()
                ->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
            $current = $repo->all()->get();
            $needle = strtolower($label);
            foreach (($current['items'] ?? []) as $id => $item) {
                if (!is_array($item)) continue;
                if (strtolower((string) ($item['label'] ?? '')) === $needle) {
                    return (string) $id;
                }
            }
        } catch (\Throwable $e) {}
        return '';
    }

    public function rest_class_refactor_preview(\WP_REST_Request $request) {
        $from = (string) $request->get_param('from');
        $to   = (string) $request->get_param('to');
        if ($from === '') {
            return new \WP_Error('ecf_class_refactor_invalid', 'from required', ['status' => 400]);
        }
        $matches = $this->scan_class_usage_for_refactor($from);
        return rest_ensure_response([
            'success'      => true,
            'from'         => $from,
            'to'           => $to,
            'count'        => count($matches),
            'total_hits'   => array_sum(array_map(static fn($m) => $m['hits'] ?? 0, $matches)),
            'matches'      => $matches,
        ]);
    }

    public function rest_class_refactor_apply(\WP_REST_Request $request) {
        $payload = (array) $request->get_json_params();
        $from = (string) ($payload['from'] ?? '');
        $to   = (string) ($payload['to']   ?? '');
        $post_ids = isset($payload['post_ids']) && is_array($payload['post_ids'])
            ? array_map('intval', $payload['post_ids']) : [];
        if ($from === '' || $to === '' || empty($post_ids)) {
            return new \WP_Error('ecf_class_refactor_invalid', 'from, to, post_ids required', ['status' => 400]);
        }
        $result = $this->apply_class_refactor($from, $to, $post_ids);
        return rest_ensure_response(array_merge(['success' => true], $result));
    }
}
