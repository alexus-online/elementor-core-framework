<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read the user's existing Elementor kit (Theme Style + Global Colors +
 * Global Typography) and offer to seed Layrix's settings with those values.
 * Solves the "I just installed Layrix and now my site has Tailwind blue
 * defaults instead of my brand colors" friction on first setup.
 *
 * Read-only on the Elementor side. Writes only to Layrix's own option,
 * with a preview step so the user sees what's changing before confirming.
 */
trait ECF_Framework_Theme_Style_Import_Trait {

    private function read_elementor_kit_settings(): ?array {
        if (!class_exists('\Elementor\Plugin')) {
            return null;
        }
        try {
            $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
            if (!$kit) return null;
            $settings = $kit->get_settings();
            return is_array($settings) ? $settings : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve a typography size field that Elementor stores as
     * { unit: 'rem'|'px'|'em', size: 1.125 } or as a plain string.
     */
    private function elementor_typography_size_to_css(?array $size): string {
        if (!is_array($size)) return '';
        $unit = $size['unit'] ?? 'px';
        $val  = $size['size'] ?? null;
        if ($val === null || $val === '') return '';
        return rtrim(rtrim(number_format((float) $val, 3, '.', ''), '0'), '.') . $unit;
    }

    private function find_elementor_color_by_id(array $colors, string $id): string {
        foreach ($colors as $c) {
            if (($c['_id'] ?? '') === $id && !empty($c['color'])) {
                return (string) $c['color'];
            }
        }
        return '';
    }

    /**
     * Read the Elementor kit and produce a normalized "what we found" array.
     * Each entry is [layrix_path, elementor_value, layrix_label]. Empty
     * values mean Elementor has nothing to offer for that field.
     */
    public function build_kit_import_preview(): array {
        $kit = $this->read_elementor_kit_settings();
        if (!is_array($kit)) {
            return ['available' => false, 'fields' => []];
        }

        $system_colors = is_array($kit['system_colors'] ?? null) ? $kit['system_colors'] : [];

        // Map Elementor "system" color slots to Layrix color tokens. Layrix
        // doesn't have a built-in "accent" mapping equal to Elementor's —
        // we map: Elementor primary → Layrix primary, secondary → secondary,
        // accent → accent. Elementor's "text" is used for base_text_color,
        // not a color token.
        $primary   = $this->find_elementor_color_by_id($system_colors, 'primary');
        $secondary = $this->find_elementor_color_by_id($system_colors, 'secondary');
        $accent    = $this->find_elementor_color_by_id($system_colors, 'accent');
        $text_col  = $this->find_elementor_color_by_id($system_colors, 'text');

        $body_color           = (string) ($kit['body_color']                            ?? '');
        $background_color     = (string) ($kit['background_color']                      ?? '');
        $link_color           = (string) ($kit['link_normal_color']                     ?? '');
        $body_font_family     = (string) ($kit['body_typography_font_family']           ?? '');
        $body_font_size       = $this->elementor_typography_size_to_css($kit['body_typography_font_size'] ?? null);
        $body_font_weight     = (string) ($kit['body_typography_font_weight']           ?? '');
        $body_line_height     = $this->elementor_typography_size_to_css($kit['body_typography_line_height'] ?? null);
        $heading_font_family  = (string) ($kit['h1_typography_font_family']             ?? '');
        if ($heading_font_family === '') {
            $heading_font_family = (string) ($kit['heading_typography_font_family'] ?? '');
        }

        // Layout: container_width is the boxed max-width (e.g. 1140px),
        // content_width is the inner text max-width.
        $container_width = $this->elementor_typography_size_to_css($kit['container_width'] ?? null);
        $content_width   = $this->elementor_typography_size_to_css($kit['content_width']   ?? null);

        // System typography entries (primary/secondary/text/accent each
        // carry their own font family setting). Map each to a Layrix
        // typography.fonts.<id> path so the user can pick which to import.
        $sys_typo = is_array($kit['system_typography'] ?? null) ? $kit['system_typography'] : [];
        $typo_by_id = [];
        foreach ($sys_typo as $row) {
            $id     = sanitize_key((string) ($row['_id'] ?? ''));
            $family = trim((string) ($row['typography_font_family'] ?? ''));
            if ($id !== '' && $family !== '') {
                $typo_by_id[$id] = $family;
            }
        }

        $fields = [
            'colors.primary'         => ['label' => __('Farbe: Primär', 'ecf-framework'),         'value' => $primary,             'type' => 'color'],
            'colors.secondary'       => ['label' => __('Farbe: Sekundär', 'ecf-framework'),       'value' => $secondary,           'type' => 'color'],
            'colors.accent'          => ['label' => __('Farbe: Akzent', 'ecf-framework'),         'value' => $accent,              'type' => 'color'],
            'colors.text'            => ['label' => __('Farbe: Text-Token', 'ecf-framework'),     'value' => $text_col,            'type' => 'color'],
            'base_text_color'        => ['label' => __('Body-Textfarbe', 'ecf-framework'),        'value' => $body_color,          'type' => 'color'],
            'base_background_color'  => ['label' => __('Body-Hintergrund', 'ecf-framework'),      'value' => $background_color,    'type' => 'color'],
            'link_color'             => ['label' => __('Link-Farbe', 'ecf-framework'),            'value' => $link_color,          'type' => 'color'],
        ];

        // Custom colors — append each as colors.<sanitized_name>.
        $custom_colors = is_array($kit['custom_colors'] ?? null) ? $kit['custom_colors'] : [];
        foreach ($custom_colors as $cc) {
            $title = trim((string) ($cc['title'] ?? ''));
            $hex   = trim((string) ($cc['color'] ?? ''));
            if ($title === '' || $hex === '') continue;
            $key = sanitize_key($title);
            if ($key === '') continue;
            // Don't shadow system-color slots.
            if (in_array($key, ['primary', 'secondary', 'accent', 'text', 'surface'], true)) continue;
            $fields['colors.' . $key] = [
                'label' => sprintf(__('Custom-Farbe: %s', 'ecf-framework'), $title),
                'value' => $hex,
                'type'  => 'color',
            ];
        }

        $fields['base_font_family']      = ['label' => __('Body-Schriftart', 'ecf-framework'),       'value' => $body_font_family,    'type' => 'font'];
        $fields['base_body_text_size']   = ['label' => __('Body-Schriftgröße', 'ecf-framework'),     'value' => $body_font_size,      'type' => 'size'];
        $fields['base_body_font_weight'] = ['label' => __('Body-Schriftgewicht', 'ecf-framework'),   'value' => $body_font_weight,    'type' => 'text'];
        $fields['heading_font_family']   = ['label' => __('Überschriften-Schriftart', 'ecf-framework'), 'value' => $heading_font_family, 'type' => 'font'];

        // System Typography → Layrix font tokens (typography.fonts.*)
        foreach (['primary' => __('Schriftart-Token: Primär', 'ecf-framework'),
                  'secondary' => __('Schriftart-Token: Sekundär', 'ecf-framework'),
                  'text' => __('Schriftart-Token: Text', 'ecf-framework'),
                  'accent' => __('Schriftart-Token: Akzent', 'ecf-framework')] as $id => $label) {
            if (!empty($typo_by_id[$id])) {
                $fields['typography.fonts.' . $id] = [
                    'label' => $label,
                    'value' => $typo_by_id[$id],
                    'type'  => 'font',
                ];
            }
        }

        if ($body_line_height !== '') {
            $fields['typography.leading.normal'] = [
                'label' => __('Body-Zeilenhöhe', 'ecf-framework'),
                'value' => $body_line_height,
                'type'  => 'text',
            ];
        }

        // Layout
        if ($container_width !== '') {
            $fields['elementor_boxed_width'] = [
                'label' => __('Container-Breite (boxed)', 'ecf-framework'),
                'value' => $container_width,
                'type'  => 'size',
            ];
        }
        if ($content_width !== '') {
            $fields['content_max_width'] = [
                'label' => __('Inhaltsbreite (max-width für Fließtext)', 'ecf-framework'),
                'value' => $content_width,
                'type'  => 'size',
            ];
        }

        return [
            'available' => true,
            'kit_id'    => (int) ($kit['post_id'] ?? 0),
            'fields'    => $fields,
        ];
    }

    /**
     * Apply the import. $accept is a flat array of field-paths the user
     * confirmed (e.g., ['colors.primary', 'base_text_color']). Only those
     * are written. Returns ['written' => N, 'skipped' => N].
     */
    public function apply_kit_import(array $accept): array {
        $preview = $this->build_kit_import_preview();
        if (empty($preview['available'])) {
            return ['written' => 0, 'skipped' => 0, 'error' => 'no_kit'];
        }
        $accept_set = array_flip(array_map('strval', $accept));

        $settings = $this->get_settings();
        $written = 0;
        $skipped = 0;

        foreach ($preview['fields'] as $path => $field) {
            $value = trim((string) $field['value']);
            if ($value === '') { $skipped++; continue; }
            if (!isset($accept_set[$path])) { $skipped++; continue; }

            if (strpos($path, 'colors.') === 0) {
                $color_name = substr($path, 7);
                $hex = $this->sanitize_css_color_value($value);
                if ($hex === '') { $skipped++; continue; }
                $found = false;
                foreach ($settings['colors'] as $i => $row) {
                    if (($row['name'] ?? '') === $color_name) {
                        $settings['colors'][$i]['value']  = $hex;
                        $settings['colors'][$i]['format'] = 'hex';
                        $found = true;
                        $written++;
                        break;
                    }
                }
                if (!$found) {
                    $settings['colors'][] = [
                        'name'            => $color_name,
                        'value'           => $hex,
                        'format'          => 'hex',
                        'generate_shades' => '1',
                        'shade_count'     => 6,
                        'generate_tints'  => '0',
                        'tint_count'      => 6,
                    ];
                    $written++;
                }
                continue;
            }

            // typography.fonts.<id> and typography.leading.<id> — nested
            // arrays of {name, value} pairs; find by name or append.
            if (preg_match('/^typography\.(fonts|leading)\.(.+)$/', $path, $m)) {
                $bucket = $m[1];
                $key    = sanitize_key($m[2]);
                if ($key === '') { $skipped++; continue; }
                if (!isset($settings['typography'][$bucket]) || !is_array($settings['typography'][$bucket])) {
                    $settings['typography'][$bucket] = [];
                }
                $found = false;
                foreach ($settings['typography'][$bucket] as $i => $row) {
                    if (($row['name'] ?? '') === $key) {
                        $settings['typography'][$bucket][$i]['value'] = sanitize_text_field($value);
                        $found = true;
                        $written++;
                        break;
                    }
                }
                if (!$found) {
                    $settings['typography'][$bucket][] = [
                        'name'  => $key,
                        'value' => sanitize_text_field($value),
                    ];
                    $written++;
                }
                continue;
            }

            if ($field['type'] === 'color') {
                $hex = $this->sanitize_css_color_value($value);
                if ($hex !== '') {
                    $settings[$path] = $hex;
                    $written++;
                } else {
                    $skipped++;
                }
                continue;
            }

            if ($field['type'] === 'size') {
                $clean = $this->sanitize_css_size_value($value) ?: $value;
                $settings[$path] = $clean;
                $written++;
                continue;
            }

            // font + plain text fields
            $settings[$path] = sanitize_text_field($value);
            $written++;
        }

        if ($written > 0) {
            update_option($this->option_name, $settings);
            $this->settings_cache = null;
            $this->clear_css_cache();
        }

        return ['written' => $written, 'skipped' => $skipped];
    }

    public function rest_kit_import_preview(\WP_REST_Request $request) {
        return rest_ensure_response($this->build_kit_import_preview());
    }

    public function rest_kit_import_apply(\WP_REST_Request $request) {
        $payload = (array) $request->get_json_params();
        $accept  = (array) ($payload['accept'] ?? []);
        $result  = $this->apply_kit_import($accept);
        $result['success'] = empty($result['error']);
        return rest_ensure_response($result);
    }
}
