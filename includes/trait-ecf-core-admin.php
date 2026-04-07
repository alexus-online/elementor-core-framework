<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Core_Admin_Trait {
    private function can_manage_framework() {
        return current_user_can('manage_options') && current_user_can('activate_plugins');
    }

    public function menu() {
        add_menu_page('ECF Elementor v4 Core Framework', 'ECF Elementor v4 Core Framework', 'manage_options', 'ecf-framework', [$this, 'settings_page'], 'dashicons-admin-customizer', 58);
    }

    public function register() {
        register_setting('ecf_group', $this->option_name, [$this, 'sanitize_settings']);
    }

    private function synced_variable_labels_option_name() {
        return $this->option_name . '_synced_variable_labels';
    }

    private function is_german() {
        return strpos(get_locale(), 'de_') === 0;
    }

    private function is_backend_german() {
        return $this->is_german();
    }

    private function t($en, $de) {
        return $this->is_german() ? $de : $en;
    }

    private function tip($en, $de) {
        $text = esc_attr($this->t($en, $de));
        return '<span class="ecf-tip" data-tip="'.$text.'">?</span>';
    }

    private function tip_hover_label($label, $tip_en, $tip_de) {
        return '<span class="ecf-tip-hover" data-tip="'.esc_attr($this->t($tip_en, $tip_de)).'">'.esc_html($label).'</span>';
    }

    private function debug_logging_enabled() {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    private function debug_log($message, $context = null) {
        if (!$this->debug_logging_enabled()) {
            return;
        }

        $line = 'ECF debug: ' . $message;
        if ($context !== null) {
            $line .= ' ' . wp_json_encode($context);
        }

        error_log($line);
    }

    private function unauthorized_notice_message() {
        return $this->t(
            'You are not allowed to perform this action.',
            'Du darfst diese Aktion nicht ausführen.'
        );
    }

    private function redirect_with_message($base_url, array $query = [], $message = '', $message_key = 'ecf_message') {
        if ($message !== '') {
            $query[$message_key] = $message;
        }

        wp_safe_redirect(add_query_arg($query, $base_url));
        exit;
    }

    private function deny_admin_request($base_url, array $query = [], $message_key = 'ecf_message') {
        $this->redirect_with_message($base_url, $query, $this->unauthorized_notice_message(), $message_key);
    }
}
