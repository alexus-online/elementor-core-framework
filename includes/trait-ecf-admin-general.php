<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Admin_General_Trait {
    private function general_setting_favorite_keys() {
        return [
            'root_font_size',
            'interface_language',
            'github_update_checks_enabled',
            'content_max_width',
            'elementor_boxed_width',
            'base_font_family',
            'base_text_color',
            'base_background_color',
            'link_color',
            'focus_color',
            'show_elementor_status_cards',
            'elementor_variable_type_filter',
        ];
    }

    private function default_general_setting_favorites() {
        return [
            'root_font_size' => '1',
            'interface_language' => '1',
            'content_max_width' => '1',
            'elementor_boxed_width' => '1',
            'base_font_family' => '1',
            'base_text_color' => '1',
            'github_update_checks_enabled' => '1',
            'show_elementor_status_cards' => '1',
            'elementor_variable_type_filter' => '1',
        ];
    }

    private function is_general_setting_favorite($settings, $key) {
        return !empty($settings['general_setting_favorites'][$key]);
    }

    private function render_general_setting_favorite_toggle($settings, $key) {
        ?>
        <label class="ecf-favorite-toggle"
               data-tip="<?php echo esc_attr__('Use the heart to pin this setting to Favorites or remove it from Favorites again.', 'ecf-framework'); ?>"
               aria-label="<?php echo esc_attr__('Use the heart to pin this setting to Favorites or remove it from Favorites again.', 'ecf-framework'); ?>">
            <input type="checkbox"
                   name="<?php echo esc_attr($this->option_name); ?>[general_setting_favorites][<?php echo esc_attr($key); ?>]"
                   value="1"
                   data-ecf-general-favorite-toggle
                   data-ecf-favorite-key="<?php echo esc_attr($key); ?>"
                   <?php checked($this->is_general_setting_favorite($settings, $key)); ?>>
            <span class="dashicons dashicons-heart" aria-hidden="true"></span>
            <span class="screen-reader-text"><?php echo esc_html__('Favorite', 'ecf-framework'); ?></span>
        </label>
        <?php
    }

    private function general_setting_label($label, $tip, $icon = 'admin-generic') {
        return sprintf(
            '<span class="ecf-tip-hover ecf-general-setting-label" data-tip="%1$s"><span class="ecf-general-setting-label__icon dashicons dashicons-%2$s" aria-hidden="true"></span><span class="ecf-general-setting-label__text">%3$s</span></span>',
            esc_attr(__($tip, 'ecf-framework')),
            esc_attr($icon),
            esc_html($label)
        );
    }

    private function general_setting_favorite_definitions($settings) {
        $root_base_px = $this->get_root_font_base_px($settings);
        $base_font_options = $this->base_font_family_options($settings);
        $base_font_value = (string) ($settings['base_font_family'] ?? 'var(--ecf-font-primary)');
        $base_font_label = $base_font_options[$base_font_value] ?? $base_font_value;

        return [
            'root_font_size' => [
                'group' => 'website',
                'tab' => 'system',
                'title' => __('Root Font Size', 'ecf-framework'),
                'value' => sprintf(__('%s%% (%spx = 1rem)', 'ecf-framework'), str_replace('.', ',', (string) ($settings['root_font_size'] ?? '62.5')), $this->format_preview_number($root_base_px)),
            ],
            'github_update_checks_enabled' => [
                'group' => 'plugin',
                'tab' => 'system',
                'title' => __('GitHub update checks', 'ecf-framework'),
                'value' => !empty($settings['github_update_checks_enabled']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework'),
            ],
            'interface_language' => [
                'group' => 'plugin',
                'tab' => 'system',
                'title' => __('Plugin Language', 'ecf-framework'),
                'value' => $this->selected_interface_language($settings) === 'de'
                    ? __('German', 'ecf-framework')
                    : __('English', 'ecf-framework'),
            ],
            'content_max_width' => [
                'group' => 'website',
                'tab' => 'layout',
                'title' => __('Content Max Width', 'ecf-framework'),
                'value' => (string) ($settings['content_max_width'] ?? '72ch'),
            ],
            'elementor_boxed_width' => [
                'group' => 'website',
                'tab' => 'layout',
                'title' => __('Elementor Boxed Width', 'ecf-framework'),
                'value' => (string) ($settings['elementor_boxed_width'] ?? '1140px'),
            ],
            'base_font_family' => [
                'group' => 'website',
                'tab' => 'typography',
                'title' => __('Base Font Family', 'ecf-framework'),
                'value' => $base_font_label,
            ],
            'base_text_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Base Text Color', 'ecf-framework'),
                'value' => (string) ($settings['base_text_color'] ?? '#111827'),
            ],
            'base_background_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Base Background Color', 'ecf-framework'),
                'value' => (string) ($settings['base_background_color'] ?? '#ffffff'),
            ],
            'link_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Link Color', 'ecf-framework'),
                'value' => (string) ($settings['link_color'] ?? '#3b82f6'),
            ],
            'focus_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Focus Color', 'ecf-framework'),
                'value' => (string) ($settings['focus_color'] ?? '#6366f1'),
            ],
            'show_elementor_status_cards' => [
                'group' => 'plugin',
                'tab' => 'behavior',
                'title' => __('Status cards in Variables & Sync', 'ecf-framework'),
                'value' => !empty($settings['show_elementor_status_cards']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework'),
            ],
            'elementor_variable_type_filter' => [
                'group' => 'plugin',
                'tab' => 'behavior',
                'title' => __('Filter variables by field type', 'ecf-framework'),
                'value' => !empty($settings['elementor_variable_type_filter']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework'),
            ],
        ];
    }

    private function render_general_favorites_section($settings) {
        $definitions = $this->general_setting_favorite_definitions($settings);
        $group_labels = [
            'website' => __('Website', 'ecf-framework'),
            'plugin'  => __('Plugin', 'ecf-framework'),
        ];
        ?>
        <div class="ecf-general-favorites" data-ecf-general-favorites>
            <p class="ecf-muted-copy"><?php echo esc_html__('Your pinned quick settings. Use the star icon on any supported setting to add or remove it here.', 'ecf-framework'); ?></p>
            <div class="ecf-general-favorites__empty" data-ecf-general-favorites-empty hidden>
                <?php echo esc_html__('No favorites selected yet. Mark important settings with the heart icon.', 'ecf-framework'); ?>
            </div>
            <?php foreach ($group_labels as $group_key => $group_label): ?>
                <div class="ecf-general-favorites__group" data-ecf-general-favorites-group="<?php echo esc_attr($group_key); ?>">
                    <div class="ecf-vargroup-header">
                        <h3><?php echo esc_html($group_label); ?></h3>
                    </div>
                    <div class="ecf-general-favorites__grid">
                        <?php foreach ($definitions as $favorite_key => $definition): ?>
                            <?php if (($definition['group'] ?? '') !== $group_key) { continue; } ?>
                            <div class="ecf-general-favorite-card"
                                 data-ecf-favorite-card="<?php echo esc_attr($favorite_key); ?>"
                                 <?php echo $this->is_general_setting_favorite($settings, $favorite_key) ? '' : 'hidden'; ?>>
                                <div class="ecf-general-favorite-card__top">
                                    <strong><?php echo esc_html($definition['title']); ?></strong>
                                </div>
                                <div class="ecf-general-favorite-card__editor">
                                    <?php $this->render_general_favorite_editor($settings, $favorite_key); ?>
                                </div>
                                <div class="ecf-general-favorite-card__meta"><?php echo esc_html($definition['value']); ?></div>
                                <div class="ecf-general-favorite-card__remove-row">
                                    <span class="ecf-general-favorite-card__remove-label"><?php echo esc_html__('Remove from favorites', 'ecf-framework'); ?></span>
                                    <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--tiny" data-ecf-favorite-remove="<?php echo esc_attr($favorite_key); ?>" title="<?php echo esc_attr__('Remove from favorites', 'ecf-framework'); ?>">
                                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_root_font_size_select($settings, $canonical = false) {
        $value = (string) ($settings['root_font_size'] ?? '62.5');
        $root_base_px = $this->get_root_font_base_px($settings);
        $name_attr = $canonical ? ' name="' . esc_attr($this->option_name) . '[root_font_size]"' : '';
        $sync_attr = $canonical ? ' data-ecf-root-font-source="1"' : ' data-ecf-root-font-mirror="1"';
        ?>
        <label class="ecf-root-font-select" data-ecf-general-field="root_font_size">
            <span class="ecf-root-font-select__label">
                <?php echo $this->general_setting_label(__('Root Font Size', 'ecf-framework'), 'Base rem size used for token conversion. Choose 100% for 16px = 1rem or 62.5% for 10px = 1rem.', 'editor-textcolor'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'root_font_size'); ?>
            </span>
            <span class="ecf-root-font-select__control">
                <select<?php echo $name_attr; ?><?php echo $sync_attr; ?>>
                    <option value="62.5" <?php selected($value, '62.5'); ?>>62,5%</option>
                    <option value="100" <?php selected($value, '100'); ?>>100%</option>
                </select>
                <span class="ecf-root-font-select__meta" data-ecf-root-font-inline><?php echo esc_html(sprintf(__('%spx = 1rem', 'ecf-framework'), $this->format_preview_number($root_base_px))); ?></span>
            </span>
        </label>
        <?php
    }

    private function render_interface_language_field($settings) {
        $current = $this->selected_interface_language($settings);
        $wp_default = $this->wordpress_default_interface_language();
        ?>
        <label data-ecf-general-field="interface_language">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__('Plugin Language', 'ecf-framework'), 'Controls the ECF interface language. The initial selection follows the current WordPress language setting.', 'translation'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'interface_language'); ?>
            </span>
            <select name="<?php echo esc_attr($this->option_name); ?>[interface_language]" class="ecf-general-favorite-input">
                <option value="de" <?php selected($current, 'de'); ?>>
                    <?php echo esc_html($wp_default === 'de' ? __('German (matches current WordPress language)', 'ecf-framework') : __('German', 'ecf-framework')); ?>
                </option>
                <option value="en" <?php selected($current, 'en'); ?>>
                    <?php echo esc_html($wp_default === 'en' ? __('English (matches current WordPress language)', 'ecf-framework') : __('English', 'ecf-framework')); ?>
                </option>
            </select>
        </label>
        <?php
    }

    private function render_general_color_field($settings, $key, $label_en, $label_de, $tip_en, $tip_de, $icon = 'admin-appearance') {
        $value = (string) ($settings[$key] ?? '');
        ?>
        <label data-ecf-general-field="<?php echo esc_attr($key); ?>">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__($label_en, 'ecf-framework'), $tip_en, $icon); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, $key); ?>
            </span>
            <input type="text" class="ecf-color-input" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" data-default-color="<?php echo esc_attr($value); ?>">
        </label>
        <?php
    }

    private function boxed_format_options() {
        return [
            'px'     => ['label' => 'px',  'tip' => __('Simple pixel value. Example: 1140 becomes 1140px.', 'ecf-framework')],
            '%'      => ['label' => '%',   'tip' => __('Percentage value. Example: 90 becomes 90%.', 'ecf-framework')],
            'rem'    => ['label' => 'rem', 'tip' => __('Root-based unit. Example: 72 becomes 72rem.', 'ecf-framework')],
            'em'     => ['label' => 'em',  'tip' => __('Element-based unit. Example: 72 becomes 72em.', 'ecf-framework')],
            'vw'     => ['label' => 'vw',  'tip' => __('Viewport width unit. Example: 90 becomes 90vw.', 'ecf-framework')],
            'vh'     => ['label' => 'vh',  'tip' => __('Viewport height unit. Example: 80 becomes 80vh.', 'ecf-framework')],
            'custom' => ['label' => 'f(x)', 'tip' => __('Full CSS expression. Use values like min(100% - 2rem, 1140px), calc(...) or clamp(...).', 'ecf-framework')],
        ];
    }

    private function content_format_options() {
        return [
            'px'     => ['label' => 'px',  'tip' => __('Simple pixel value. Good for strict content widths like 720px.', 'ecf-framework')],
            'ch'     => ['label' => 'ch',  'tip' => __('Character-based width. Great for readable text columns like 65ch or 72ch.', 'ecf-framework')],
            '%'      => ['label' => '%',   'tip' => __('Percentage value if the content width should stay fluid.', 'ecf-framework')],
            'rem'    => ['label' => 'rem', 'tip' => __('Root-based unit. Useful if content width should scale with your root font size.', 'ecf-framework')],
            'em'     => ['label' => 'em',  'tip' => __('Element-based unit. Rarely needed, but possible for content wrappers.', 'ecf-framework')],
            'vw'     => ['label' => 'vw',  'tip' => __('Viewport width unit. Useful for fluid readable widths.', 'ecf-framework')],
            'vh'     => ['label' => 'vh',  'tip' => __('Viewport height unit. Usually uncommon here, but available if needed.', 'ecf-framework')],
            'custom' => ['label' => 'f(x)', 'tip' => __('Full CSS expression. Use values like min(72ch, 100% - 2rem), calc(...) or clamp(...).', 'ecf-framework')],
        ];
    }

    private function render_general_size_field_inline($settings, $field_key, $stored_value, $options, $default_format, $placeholder, $title) {
        $parts = $this->parse_css_size_parts($stored_value);
        $selected_format = isset($options[$parts['format']]) ? $parts['format'] : $default_format;
        ?>
        <div class="ecf-inline-size-input ecf-inline-size-input--favorite">
            <input type="text"
                   name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field_key); ?>_value]"
                   value="<?php echo esc_attr($parts['value']); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>"
                   title="<?php echo esc_attr($title); ?>">
            <div class="ecf-format-picker" data-ecf-format-picker>
                <input type="hidden"
                       name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field_key); ?>_format]"
                       value="<?php echo esc_attr($selected_format); ?>"
                       data-ecf-format-input>
                <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false">
                    <span data-ecf-format-current><?php echo esc_html($options[$selected_format]['label']); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
                <div class="ecf-format-picker__menu" data-ecf-format-menu hidden>
                    <div class="ecf-format-picker__options">
                        <?php foreach ($options as $format_value => $format_config): ?>
                            <button type="button"
                                    class="ecf-format-picker__option<?php echo $format_value === $selected_format ? ' is-active' : ''; ?>"
                                    data-ecf-format-option
                                    data-value="<?php echo esc_attr($format_value); ?>"
                                    data-label="<?php echo esc_attr($format_config['label']); ?>"
                                    data-tip="<?php echo esc_attr($format_config['tip']); ?>">
                                <?php echo esc_html($format_config['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_general_favorite_editor($settings, $key) {
        switch ($key) {
            case 'root_font_size':
                ?>
                <select name="<?php echo esc_attr($this->option_name); ?>[root_font_size]" data-ecf-root-font-mirror="1" class="ecf-general-favorite-input">
                    <option value="62.5" <?php selected((string) ($settings['root_font_size'] ?? '62.5'), '62.5'); ?>>62,5%</option>
                    <option value="100" <?php selected((string) ($settings['root_font_size'] ?? '62.5'), '100'); ?>>100%</option>
                </select>
                <?php
                break;
            case 'interface_language':
                $this->render_interface_language_field($settings);
                break;
            case 'github_update_checks_enabled':
                ?>
                <label class="ecf-form-grid__checkbox ecf-form-grid__checkbox--favorite">
                    <input type="checkbox"
                           name="<?php echo esc_attr($this->option_name); ?>[github_update_checks_enabled]"
                           value="1"
                           <?php checked(!empty($settings['github_update_checks_enabled'])); ?>>
                    <span><?php echo esc_html(!empty($settings['github_update_checks_enabled']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework')); ?></span>
                </label>
                <?php
                break;
            case 'content_max_width':
                $this->render_general_size_field_inline(
                    $settings,
                    'content_max_width',
                    $settings['content_max_width'] ?? '72ch',
                    $this->content_format_options(),
                    'ch',
                    '72 oder min(72ch, 100% - 2rem)',
                    __('Readable width for text/content areas.', 'ecf-framework')
                );
                break;
            case 'elementor_boxed_width':
                $this->render_general_size_field_inline(
                    $settings,
                    'elementor_boxed_width',
                    $settings['elementor_boxed_width'] ?? '1140px',
                    $this->boxed_format_options(),
                    'px',
                    '1140 oder clamp(20rem, 80vw, 1140px)',
                    __('Width of centered boxed layout containers.', 'ecf-framework')
                );
                break;
            case 'base_font_family':
                $this->render_base_font_family_field($settings);
                break;
            case 'base_text_color':
            case 'base_background_color':
            case 'link_color':
            case 'focus_color':
                ?>
                <input type="text"
                       class="ecf-color-input ecf-general-favorite-input"
                       name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]"
                       value="<?php echo esc_attr((string) ($settings[$key] ?? '')); ?>"
                       data-default-color="<?php echo esc_attr((string) ($settings[$key] ?? '')); ?>">
                <?php
                break;
            case 'show_elementor_status_cards':
            case 'elementor_variable_type_filter':
                ?>
                <label class="ecf-form-grid__checkbox ecf-form-grid__checkbox--favorite">
                    <input type="checkbox"
                           name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]"
                           value="1"
                           <?php checked(!empty($settings[$key])); ?>>
                    <span><?php echo esc_html(!empty($settings[$key]) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework')); ?></span>
                </label>
                <?php
                break;
        }
    }

    private function base_font_family_options($settings) {
        $options = [
            'var(--ecf-font-primary)' => __('Primary', 'ecf-framework') . ': ' . ($settings['typography']['fonts'][0]['value'] ?? 'Inter, sans-serif'),
            'var(--ecf-font-secondary)' => __('Secondary', 'ecf-framework') . ': ' . ($settings['typography']['fonts'][1]['value'] ?? 'Georgia, serif'),
            'var(--ecf-font-mono)' => __('Mono', 'ecf-framework') . ': ' . ($settings['typography']['fonts'][2]['value'] ?? 'JetBrains Mono, monospace'),
        ];

        foreach ((array) ($settings['typography']['local_fonts'] ?? []) as $row) {
            $family = trim((string) ($row['family'] ?? ''));
            if ($family === '') {
                continue;
            }
            $options["'" . $family . "'"] = __('Uploaded font', 'ecf-framework') . ': ' . $family;
        }

        return $options;
    }

    private function render_base_font_family_field($settings) {
        $current = (string) ($settings['base_font_family'] ?? 'var(--ecf-font-primary)');
        $options = $this->base_font_family_options($settings);
        $is_custom = !isset($options[$current]);
        $current_local_family = '';
        foreach ((array) ($settings['typography']['local_fonts'] ?? []) as $row) {
            $family = trim((string) ($row['family'] ?? ''));
            if ($family !== '' && ("'" . $family . "'") === $current) {
                $current_local_family = $family;
                break;
            }
        }
        ?>
        <label data-ecf-general-field="base_font_family">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__('Base Font Family', 'ecf-framework'), 'Base font stack applied to the whole site body. Choose one of your saved stacks or a locally uploaded font. Use Custom only for a special free text stack.', 'editor-textcolor'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'base_font_family'); ?>
            </span>
            <div class="ecf-form-grid ecf-form-grid--single">
                <select name="<?php echo esc_attr($this->option_name); ?>[base_font_family_preset]" data-ecf-base-font-preset>
                    <?php foreach ($options as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected(!$is_custom && $current === $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                    <option value="__custom__" <?php selected($is_custom); ?>><?php echo esc_html__('Custom stack', 'ecf-framework'); ?></option>
                </select>
                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[base_font_family_custom]" value="<?php echo esc_attr($is_custom ? $current : ''); ?>" placeholder="Inter, sans-serif" data-ecf-base-font-custom <?php echo $is_custom ? '' : 'hidden'; ?>>
            </div>
            <div class="ecf-inline-actions ecf-inline-actions--fonts">
                <button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--tiny" data-ecf-local-font-add>
                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                    <span><?php echo esc_html__('Add local font', 'ecf-framework'); ?></span>
                </button>
                <?php if ($current_local_family !== ''): ?>
                    <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--tiny" data-ecf-local-font-remove="<?php echo esc_attr($current_local_family); ?>">
                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                        <span><?php echo esc_html__('Remove selected local font', 'ecf-framework'); ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </label>
        <?php
    }
}
