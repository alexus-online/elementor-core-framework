<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Asset_Loading_Trait {
    private function build_editor_preview_maps($settings) {
        $root_base_px = $this->get_root_font_base_px($settings);
        $spacing_preview_map = [];
        $type_preview_map = [];
        $radius_preview_map = [];

        foreach ($this->build_spacing_scale_preview($settings['spacing'], $root_base_px) as $item) {
            $token_key = strtolower(ltrim((string) ($item['token'] ?? ''), '-'));
            if ($token_key === '') {
                continue;
            }
            $spacing_preview_map[$token_key] = [
                'minPx'    => $item['min_px'] ?? '',
                'maxPx'    => $item['max_px'] ?? '',
                'cssValue' => $item['css_value'] ?? '',
            ];
        }

        foreach ($this->build_type_scale_preview($settings['typography']['scale'], $root_base_px) as $item) {
            $token_key = strtolower(ltrim((string) ($item['token'] ?? ''), '-'));
            if ($token_key === '') {
                continue;
            }
            $type_preview_map[$token_key] = [
                'minPx'    => $item['min_px'] ?? '',
                'maxPx'    => $item['max_px'] ?? '',
                'cssValue' => $item['css_value'] ?? '',
            ];
        }

        foreach (($settings['radius'] ?? []) as $row) {
            $name = sanitize_key($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $token_key = 'ecf-radius-' . $name;
            $radius_preview_map[$token_key] = [
                'minPx'    => $this->format_preview_number((float) ($row['min'] ?? 0), 3),
                'maxPx'    => $this->format_preview_number((float) ($row['max'] ?? ($row['min'] ?? 0)), 3),
                'cssValue' => $this->radius_css_value($row, 375, 1280, $root_base_px),
            ];
        }

        return [
            'spacingPreview' => $spacing_preview_map,
            'typePreview' => $type_preview_map,
            'radiusPreview' => $radius_preview_map,
        ];
    }

    private function asset_version($relative_path, $fallback) {
        $full_path = plugin_dir_path(__FILE__) . '../' . ltrim($relative_path, '/');
        return file_exists($full_path) ? filemtime($full_path) : $fallback;
    }

    public function admin_assets($hook) {
        if ($hook !== 'toplevel_page_ecf-framework') {
            return;
        }

        wp_enqueue_media();

        $settings = $this->get_settings();
        $preview_maps = $this->build_editor_preview_maps($settings);
        $admin_css_ver = $this->asset_version('assets/admin.css', '0.1.5');
        $admin_js_ver  = $this->asset_version('assets/admin.js', '0.1.5');

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('ecf-admin', plugins_url('assets/admin.css', ECF_FRAMEWORK_FILE), [], $admin_css_ver);
        wp_enqueue_script('ecf-admin', plugins_url('assets/admin.js', ECF_FRAMEWORK_FILE), ['jquery', 'wp-color-picker'], $admin_js_ver, true);
        wp_localize_script('ecf-admin', 'ecfAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_variables'),
            'i18n' => [
                'loading'        => $this->t('Loading…', 'Lade…'),
                'none'           => $this->t('No entries found.', 'Keine Einträge vorhanden.'),
                'select_all'     => $this->t('Select all', 'Alle wählen'),
                'deselect_all'   => $this->t('Deselect all', 'Auswahl aufheben'),
                'none_selected'  => $this->t('No entries selected.', 'Keine Einträge ausgewählt.'),
                'confirm_delete' => $this->t(' entry/entries delete?', ' Eintrag/Einträge löschen?'),
                'deleting'       => $this->t('Deleting…', 'Lösche…'),
                'delete_sel'     => $this->t('Delete selected', 'Auswahl löschen'),
                'error'          => $this->t('Error: ', 'Fehler: '),
                'type_color'     => $this->t('Color', 'Farbe'),
                'type_size'      => $this->t('Size', 'Größe'),
                'type_string'    => $this->t('String', 'Text'),
                'type_all'       => $this->t('All', 'Alle'),
                'type_other'     => $this->t('Other', 'Sonstige'),
                'type_spacing'   => $this->t('Spacing', 'Abstände'),
                'type_typography'=> $this->t('Typography', 'Typografie'),
                'type_layout'    => $this->t('Layout', 'Layout'),
                'type_radius'    => $this->t('Radius', 'Radius'),
                'type_shadow'    => $this->t('Shadow', 'Schatten'),
                'type_class'     => $this->t('Global Class', 'Globale Klasse'),
                'col_name'       => $this->t('Class Name', 'Klassenname'),
                'col_type'       => $this->t('Type', 'Typ'),
                'col_value'      => $this->t('Value', 'Wert'),
                'choose_font'    => $this->t('Choose font file', 'Schriftdatei wählen'),
                'use_font'       => $this->t('Use this file', 'Diese Datei verwenden'),
                'select_file'    => $this->t('Select file', 'Datei wählen'),
                'copy'           => $this->t('Copy', 'Kopieren'),
                'copied'         => $this->t('Copied!', 'Kopiert!'),
                'edit'           => $this->t('Edit', 'Bearbeiten'),
                'delete'         => $this->t('Delete', 'Löschen'),
                'save'           => $this->t('Save', 'Speichern'),
                'cancel'         => $this->t('Cancel', 'Abbrechen'),
                'search_delete_confirm' => $this->t('Do you really want to delete "%s"?', 'Möchtest du "%s" wirklich löschen?'),
                'search_edit_generated' => $this->t('This ECF variable is generated from the framework settings. Please change it in the matching ECF section instead.', 'Diese ECF-Variable wird aus den Framework-Einstellungen generiert. Bitte ändere sie im passenden ECF-Bereich.'),
                'search_edit_class'     => $this->t('Global Classes should be managed in Elementor or through the ECF sync, not directly in the search results.', 'Globale Klassen sollten in Elementor oder über den ECF-Sync verwaltet werden, nicht direkt in den Suchtreffern.'),
                'search_updated'        => $this->t('Variable updated.', 'Variable aktualisiert.'),
                'search_deleted'        => $this->t('Entry deleted.', 'Eintrag gelöscht.'),
            ],
            'spacingPreview' => $preview_maps['spacingPreview'],
            'typePreview' => $preview_maps['typePreview'],
            'radiusPreview' => $preview_maps['radiusPreview'],
        ]);
    }

    public function editor_assets() {
        $editor_css_ver = $this->asset_version('assets/editor.css', '0.1.0');
        $editor_js_ver  = $this->asset_version('assets/editor.js', '0.1.0');
        $settings = $this->get_settings();
        $preview_maps = $this->build_editor_preview_maps($settings);

        wp_enqueue_style('ecf-editor', plugins_url('assets/editor.css', ECF_FRAMEWORK_FILE), [], $editor_css_ver);
        wp_enqueue_script('ecf-editor', plugins_url('assets/editor.js', ECF_FRAMEWORK_FILE), ['jquery'], $editor_js_ver, true);
        wp_localize_script('ecf-editor', 'ecfEditor', [
            'variableTypeFilterEnabled' => !empty($settings['elementor_variable_type_filter']),
            'variableTypeFilterScopes' => [
                'color'  => !empty($settings['elementor_variable_type_filter_scopes']['color']),
                'text'   => !empty($settings['elementor_variable_type_filter_scopes']['text']),
                'space'  => !empty($settings['elementor_variable_type_filter_scopes']['space']),
                'radius' => !empty($settings['elementor_variable_type_filter_scopes']['radius']),
                'shadow' => !empty($settings['elementor_variable_type_filter_scopes']['shadow']),
                'string' => !empty($settings['elementor_variable_type_filter_scopes']['string']),
            ],
            'spacingPreview' => $preview_maps['spacingPreview'],
            'typePreview'    => $preview_maps['typePreview'],
            'radiusPreview'  => $preview_maps['radiusPreview'],
        ]);
    }
}
