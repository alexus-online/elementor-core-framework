<?php

trait ECF_Framework_Render_Helpers_Trait {
    private function render_rows($group, $rows, $input_key = null) {
        if ($input_key === null) {
            $input_key = $this->option_name . '[' . $group . ']';
        }
        $is_minmax = ($group === 'radius');
        $is_color = ($group === 'colors');
        $col_class = $is_minmax ? 'ecf-table--minmax' : ($is_color ? 'ecf-table--color' : '');

        echo '<div class="ecf-table ' . esc_attr($col_class) . '" data-group="' . esc_attr($group) . '" data-input-key="' . esc_attr($input_key) . '" data-minmax="' . ($is_minmax ? '1' : '0') . '">';

        if ($is_color) {
            echo '<div class="ecf-head ecf-head--color">';
            echo '<span>' . $this->tip_hover_label($this->t('Color', 'Farbe'), 'Color preview', 'Farbvorschau') . '</span>';
            echo '<span>' . $this->tip_hover_label($this->t('Name', 'Name'), 'Color name / CSS variable', 'Farbname / CSS-Variable') . '</span>';
            echo '<span>' . $this->tip_hover_label($this->t('Value', 'Wert'), 'Color value', 'Farbwert') . '</span>';
            echo '<span>' . $this->tip_hover_label($this->t('Format', 'Format'), 'Color format', 'Farbformat') . '</span>';
            echo '<span></span>';
            echo '</div>';
        } elseif ($is_minmax) {
            echo '<div class="ecf-head ecf-head--minmax"><span>' . $this->tip_hover_label($this->t('Class Name', 'Klassenname'), 'Token name / CSS class name', 'Tokenname / CSS-Klassenname') . '</span><span>' . $this->tip_hover_label('Min', 'Minimum value', 'Minimalwert') . '</span><span>' . $this->tip_hover_label('Max', 'Maximum value', 'Maximalwert') . '</span><span></span></div>';
        } else {
            echo '<div class="ecf-head"><span>' . $this->tip_hover_label($this->t('Class Name', 'Klassenname'), 'Token name / CSS class name', 'Tokenname / CSS-Klassenname') . '</span><span>' . $this->tip_hover_label($this->t('Value', 'Wert'), 'Token value / CSS value', 'Tokenwert / CSS-Wert') . '</span><span></span></div>';
        }

        foreach ($rows as $i => $row) {
            if ($is_color) {
                $format = strtolower($row['format'] ?? 'hex');
                if (!in_array($format, ['hex', 'hexa', 'rgb', 'rgba', 'hsl', 'hsla'], true)) {
                    $format = 'hex';
                }
                echo '<div class="ecf-row ecf-row--color">';
                $picker_hex = $this->format_css_color($this->parse_css_color($row['value']), 'hex');
                echo '<input type="text" class="ecf-color-field" value="' . esc_attr($picker_hex) . '" placeholder="#000000" />';
                echo '<input type="hidden" class="ecf-color-value-input" name="' . $input_key . '[' . $i . '][value]" value="' . esc_attr($row['value']) . '" />';
                echo '<input type="text" name="' . $input_key . '[' . $i . '][name]" value="' . esc_attr($row['name']) . '" placeholder="' . esc_attr($this->t('name', 'Name')) . '" />';
                echo '<input type="text" class="ecf-color-value-display" value="' . esc_attr($row['value']) . '" spellcheck="false" autocomplete="off" />';
                echo '<select class="ecf-color-format-select" name="' . $input_key . '[' . $i . '][format]">';
                echo '<option value="hex"' . selected($format, 'hex', false) . '>HEX</option>';
                echo '<option value="hexa"' . selected($format, 'hexa', false) . '>HEXA</option>';
                echo '<option value="rgb"' . selected($format, 'rgb', false) . '>RGB</option>';
                echo '<option value="rgba"' . selected($format, 'rgba', false) . '>RGBA</option>';
                echo '<option value="hsl"' . selected($format, 'hsl', false) . '>HSL</option>';
                echo '<option value="hsla"' . selected($format, 'hsla', false) . '>HSLA</option>';
                echo '</select>';
                echo '<button type="button" class="ecf-remove-row" title="×">×</button>';
                echo '</div>';
            } elseif ($is_minmax) {
                echo '<div class="ecf-row ecf-row--minmax">';
                echo '<input type="text" name="' . $input_key . '[' . $i . '][name]" value="' . esc_attr($row['name']) . '" placeholder="' . esc_attr($this->t('class name', 'Klassenname')) . '" />';
                $min_val = esc_attr($row['min'] ?? $row['value'] ?? '');
                $max_val = esc_attr($row['max'] ?? $row['value'] ?? '');
                echo '<input type="text" name="' . $input_key . '[' . $i . '][min]" value="' . $min_val . '" placeholder="min" />';
                echo '<input type="text" name="' . $input_key . '[' . $i . '][max]" value="' . $max_val . '" placeholder="max" />';
                echo '<button type="button" class="ecf-remove-row" title="×">×</button>';
                echo '</div>';
            } else {
                echo '<div class="ecf-row">';
                echo '<input type="text" name="' . $input_key . '[' . $i . '][name]" value="' . esc_attr($row['name']) . '" placeholder="' . esc_attr($this->t('class name', 'Klassenname')) . '" />';
                echo '<input type="text" name="' . $input_key . '[' . $i . '][value]" value="' . esc_attr($row['value']) . '" placeholder="value" />';
                echo '<button type="button" class="ecf-remove-row" title="×">×</button>';
                echo '</div>';
            }
        }
        echo '</div>';

        echo '<div class="ecf-row-controls ecf-row-controls--bottom">';
        echo '<button type="button" class="ecf-step-btn ecf-add-row" data-group="' . esc_attr($group) . '" title="' . esc_attr($this->t('Add', 'Hinzufügen')) . '">+</button>';
        echo '<button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-remove-last-row" data-group="' . esc_attr($group) . '" title="' . esc_attr($this->t('Remove last', 'Letzten entfernen')) . '">−</button>';
        echo '</div>';
    }

    private function size_prop($value) {
        if (!preg_match('/^(-?\d+(?:\.\d+)?)([a-z%]+)$/i', trim((string) $value), $m)) {
            return null;
        }
        return ['$$type' => 'size', 'value' => ['size' => $m[1] + 0, 'unit' => strtolower($m[2])]];
    }

    private function color_prop($value) {
        return ['$$type' => 'color', 'value' => $this->sanitize_css_color_value($value)];
    }

    private function string_prop($value) {
        return ['$$type' => 'string', 'value' => (string) $value];
    }

    private function class_variant(array $props) {
        return [[
            'meta' => ['state' => null, 'breakpoint' => 'desktop'],
            'props' => $props,
        ]];
    }
}
