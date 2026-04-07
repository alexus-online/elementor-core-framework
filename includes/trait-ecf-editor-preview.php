<?php

trait ECF_Framework_Editor_Preview_Trait {
    private function format_preview_number($number, $precision = 2) {
        $formatted = number_format((float) $number, $precision, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function build_type_scale_preview($scale, $root_base_px = 16) {
        $steps = $scale['steps'];
        $min_base = floatval($scale['min_base'] ?? ($scale['max_base'] ?? 16) * floatval($scale['scale_factor'] ?? 0.8));
        $max_base = floatval($scale['max_base'] ?? $scale['base'] ?? 16);
        $min_ratio = floatval($scale['min_ratio'] ?? $scale['ratio'] ?? 1.125);
        $max_ratio = floatval($scale['max_ratio'] ?? $scale['ratio'] ?? 1.25);
        $base_index = array_search($scale['base_index'], $steps, true);
        if ($base_index === false) {
            $base_index = 2;
        }
        $fluid = !empty($scale['fluid']);
        $min_vw = intval($scale['min_vw']);
        $max_vw = intval($scale['max_vw']);

        $result = [];
        foreach ($steps as $i => $step) {
            $exp = $i - $base_index;
            $max_size = round($max_base * pow($max_ratio, $exp), 3);
            $min_size = round($min_base * pow($min_ratio, $exp), 3);

            if ($fluid && $max_vw > $min_vw) {
                $css_value = $this->build_fluid_rem_clamp($min_size, $max_size, $min_vw, $max_vw, $root_base_px);
            } else {
                $min_size = $max_size;
                $css_value = $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px)) . 'rem';
            }

            $result[] = [
                'step' => $step,
                'token' => '--ecf-text-' . $step,
                'css_value' => $css_value,
                'min' => $this->format_preview_number($this->format_rem_value($min_size, 2, $root_base_px)),
                'max' => $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px)),
                'min_px' => $this->format_preview_number($min_size, 3),
                'max_px' => $this->format_preview_number($max_size, 3),
            ];
        }

        return $result;
    }

    private function font_format_from_url($url) {
        $path = wp_parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
        $formats = [
            'woff2' => 'woff2',
            'woff' => 'woff',
            'ttf' => 'truetype',
            'otf' => 'opentype',
        ];
        return $formats[$ext] ?? '';
    }

    private function render_local_font_rows($rows, $input_key) {
        echo '<div class="ecf-font-file-table" data-local-font-table data-input-key="' . esc_attr($input_key) . '">';
        echo '<div class="ecf-font-file-head"><span>' . esc_html($this->t('Key', 'Key')) . '</span><span>' . esc_html($this->t('Family', 'Familie')) . '</span><span>' . esc_html($this->t('File URL', 'Datei-URL')) . '</span><span>' . esc_html($this->t('Weight', 'Stärke')) . '</span><span>' . esc_html($this->t('Style', 'Stil')) . '</span><span>' . esc_html($this->t('Display', 'Display')) . '</span><span></span></div>';
        foreach ($rows as $i => $row) {
            echo '<div class="ecf-font-file-row">';
            echo '<input type="text" name="' . esc_attr($input_key . '[' . $i . '][name]') . '" value="' . esc_attr($row['name'] ?? '') . '" placeholder="primary-regular" />';
            echo '<input type="text" name="' . esc_attr($input_key . '[' . $i . '][family]') . '" value="' . esc_attr($row['family'] ?? '') . '" placeholder="Primary" />';
            echo '<div class="ecf-font-file-picker">';
            echo '<input type="text" class="ecf-font-file-url" name="' . esc_attr($input_key . '[' . $i . '][src]') . '" value="' . esc_attr($row['src'] ?? '') . '" placeholder="' . esc_attr($this->t('Select a local upload', 'Lokalen Upload wählen')) . '" readonly />';
            echo '<button type="button" class="button ecf-font-file-select">' . esc_html($this->t('Select file', 'Datei wählen')) . '</button>';
            echo '</div>';
            echo '<input type="text" name="' . esc_attr($input_key . '[' . $i . '][weight]') . '" value="' . esc_attr($row['weight'] ?? '400') . '" placeholder="400" />';
            echo '<select name="' . esc_attr($input_key . '[' . $i . '][style]') . '">';
            foreach (['normal', 'italic', 'oblique'] as $style) {
                echo '<option value="' . esc_attr($style) . '" ' . selected($row['style'] ?? 'normal', $style, false) . '>' . esc_html($style) . '</option>';
            }
            echo '</select>';
            echo '<select name="' . esc_attr($input_key . '[' . $i . '][display]') . '">';
            foreach (['swap', 'fallback', 'optional', 'block', 'auto'] as $display) {
                echo '<option value="' . esc_attr($display) . '" ' . selected($row['display'] ?? 'swap', $display, false) . '>' . esc_html($display) . '</option>';
            }
            echo '</select>';
            echo '<button type="button" class="button ecf-remove-row">×</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button button-secondary ecf-add-local-font">' . esc_html($this->t('Add local font file', 'Lokale Schriftdatei hinzufügen')) . '</button>';
    }

    private function find_preview_item_by_step($items, $step) {
        foreach ((array) $items as $item) {
            if (($item['step'] ?? '') === $step) {
                return $item;
            }
        }

        return [];
    }

    private function find_radius_preview_item($rows) {
        $preferred = ['m', 'md', 'base'];

        foreach ($preferred as $name) {
            foreach ((array) $rows as $row) {
                if (sanitize_key($row['name'] ?? '') === $name) {
                    return $row;
                }
            }
        }

        return $rows[0] ?? [];
    }

    private function root_font_size_hint($root_base_px) {
        return sprintf(
            $this->t(
                'Current root font size: %spx = 1rem.',
                'Aktuelle Root Font Size: %spx = 1rem.'
            ),
            $this->format_preview_number($root_base_px)
        );
    }

    private function get_editor_palette_html() {
        return '<div class="ecf-editor-help">' . esc_html($this->t('Manage starter classes and optional utility classes in the Klassen tab. Add class names here only when you really need them on the current element.', 'Verwalte Starter-Klassen und optionale Utility-Klassen im Klassen-Tab. Trage hier nur dann Klassennamen ein, wenn du sie am aktuellen Element wirklich brauchst.')) . '</div>';
    }

    public function inject_editor_controls($element, $section_id, $args) {
        if ('_section_responsive' !== $section_id) {
            return;
        }

        $element->start_controls_section('ecf_framework_section', [
            'label' => esc_html__('ECF Framework', 'ecf-framework'),
            'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
        ]);

        $element->add_control('ecf_framework_palette', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => $this->get_editor_palette_html(),
            'content_classes' => 'ecf-editor-raw',
        ]);

        $element->add_control('ecf_classes', [
            'label' => esc_html__('ECF Classes', 'ecf-framework'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'rows' => 4,
            'description' => esc_html__('Zusätzliche ECF-Klassen, getrennt durch Leerzeichen.', 'ecf-framework'),
        ]);

        $element->end_controls_section();
    }

    public function append_ecf_classes_before_render($element) {
        if (!method_exists($element, 'get_settings_for_display')) {
            return;
        }
        $settings = $element->get_settings_for_display();
        if (empty($settings['ecf_classes'])) {
            return;
        }
        $classes = trim((string) $settings['ecf_classes']);
        if ($classes === '') {
            return;
        }
        $classes = preg_replace('/\s+/', ' ', $classes);
        $element->add_render_attribute('_wrapper', 'class', $classes);
    }
}
