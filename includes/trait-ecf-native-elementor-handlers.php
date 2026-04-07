<?php

trait ECF_Framework_Native_Elementor_Handlers_Trait {
    public function handle_native_sync() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['panel' => 'sync', 'ecf_sync' => 'error']);
        }

        $this->debug_log('native sync entered');
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ecf_native_sync')) {
            $this->debug_log('native sync nonce failed');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'sync', 'ecf_sync' => 'error'],
                $this->t('Security check failed. Please reload the page and try again.', 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu und versuche es erneut.')
            );
        }

        try {
            $var_result = $this->sync_native_variables_merge();
            $this->debug_log('native variables synced', $var_result);
            $class_result = $this->sync_native_classes_merge();
            $this->debug_log('native classes synced', $class_result);
            $message = $this->build_sync_summary_message(
                'Variables',
                'Variablen',
                $var_result['created'],
                $var_result['updated']
            ) . ' ' . $this->build_sync_summary_message(
                'Classes',
                'Klassen',
                $class_result['created'],
                $class_result['updated']
            );

            if (!empty($class_result['skipped'])) {
                $message .= ' ' . sprintf(
                    $this->t(
                        '%1$d new Global Classes were skipped because Elementor can currently not create more than %3$d Global Classes and already uses %2$d.',
                        '%1$d neue Global Classes wurden übersprungen, weil Elementor aktuell nicht mehr als %3$d Global Classes anlegen kann und bereits %2$d belegt sind.'
                    ),
                    $class_result['skipped'],
                    $class_result['total'],
                    $class_result['limit']
                );
            }

            $this->debug_log('native sync redirecting success');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'ok'],
                $message
            );
        } catch (\Throwable $e) {
            $this->debug_log('native sync exception', ['message' => $e->getMessage()]);
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                $e->getMessage()
            );
        }
    }

    public function handle_class_library_sync() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['panel' => 'utilities', 'ecf_sync' => 'error']);
        }

        $this->debug_log('class library sync entered');
        $nonce = isset($_POST['_ecf_class_library_sync_nonce']) ? sanitize_text_field(wp_unslash($_POST['_ecf_class_library_sync_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ecf_class_library_sync')) {
            $this->debug_log('class library sync nonce failed');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'utilities', 'ecf_sync' => 'error'],
                $this->t('Security check failed. Please reload the page and try again.', 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu und versuche es erneut.')
            );
        }

        try {
            $submitted = $_POST[$this->option_name] ?? [];
            $sanitized = $this->sanitize_settings(is_array($submitted) ? wp_unslash($submitted) : []);
            update_option($this->option_name, $sanitized);
            $this->debug_log('class library settings saved');

            $class_result = $this->sync_native_classes_merge();
            $this->debug_log('class library classes synced', $class_result);
            $message = $this->build_sync_summary_message(
                'Classes',
                'Klassen',
                $class_result['created'],
                $class_result['updated']
            );
            if (!empty($class_result['deleted'])) {
                $message = rtrim($message, '.') . ', ' . sprintf(
                    $this->t('%1$d removed.', '%1$d entfernt.'),
                    (int) $class_result['deleted']
                );
            }

            if (!empty($class_result['skipped'])) {
                $message .= ' ' . sprintf(
                    $this->t(
                        '%1$d new Global Classes were skipped because Elementor can currently not create more than %3$d Global Classes and already uses %2$d.',
                        '%1$d neue globale Klassen wurden übersprungen, weil Elementor aktuell nicht mehr als %3$d globale Klassen anlegen kann und bereits %2$d belegt sind.'
                    ),
                    $class_result['skipped'],
                    $class_result['total'],
                    $class_result['limit']
                );
            }

            $this->debug_log('class library redirecting success');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'utilities', 'ecf_sync' => 'ok'],
                $message
            );
        } catch (\Throwable $e) {
            $this->debug_log('class library exception', ['message' => $e->getMessage()]);
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'utilities', 'ecf_sync' => 'error'],
                $e->getMessage()
            );
        }
    }

    public function handle_native_cleanup() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_native_cleanup');

        try {
            $vars_count = $this->get_native_variable_cleanup_count();
            $classes_count = $this->get_native_class_cleanup_count();

            if ($vars_count === 0 && $classes_count === 0) {
                $message = rawurlencode($this->t(
                    'No ECF variables or global classes were found in Elementor.',
                    'Es wurden keine ECF-Variablen oder globalen Klassen in Elementor gefunden.'
                ));
                wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=ok&ecf_message=' . $message));
                exit;
            }

            $vars_deleted = $this->cleanup_native_variables();
            $classes_deleted = $this->cleanup_native_classes();
            $message = rawurlencode(
                sprintf(
                    'Es wurden %1$d Variablen und %2$d globale Klassen entfernt. Der Elementor-Cache wurde automatisch geleert.',
                    $vars_deleted,
                    $classes_deleted
                )
            );
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=ok&ecf_message=' . $message));
            exit;
        } catch (\Throwable $e) {
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=error&ecf_message=' . rawurlencode($e->getMessage())));
            exit;
        }
    }

    public function handle_class_cleanup() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['panel' => 'sync', 'ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_class_cleanup');

        try {
            $classes_count = $this->get_native_class_cleanup_count();

            if ($classes_count === 0) {
                $message = rawurlencode($this->t(
                    'No ECF global classes were found in Elementor.',
                    'Es wurden keine ECF-Klassen in Elementor gefunden.'
                ));
                wp_safe_redirect(admin_url('admin.php?page=ecf-framework&panel=sync&ecf_sync=ok&ecf_message=' . $message));
                exit;
            }

            $classes_deleted = $this->cleanup_native_classes();
            $message = rawurlencode(sprintf(
                $this->t(
                    '%1$d ECF classes were removed from Elementor. You can now sync them again as clean empty classes.',
                    '%1$d ECF-Klassen wurden aus Elementor entfernt. Du kannst sie jetzt wieder als saubere leere Klassen synchronisieren.'
                ),
                $classes_deleted
            ));
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&panel=sync&ecf_sync=ok&ecf_message=' . $message));
            exit;
        } catch (\Throwable $e) {
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&panel=sync&ecf_sync=error&ecf_message=' . rawurlencode($e->getMessage())));
            exit;
        }
    }

    public function ajax_get_variables() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            wp_send_json_error('Unauthorized');
        }

        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            wp_send_json_error('Elementor variable classes not available.');
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            wp_send_json_error('No active kit.');
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $ecf = [];
        $foreign = [];

        foreach ($collection->all() as $id => $variable) {
            if ($variable->is_deleted()) {
                continue;
            }
            $entry = [
                'id' => $id,
                'label' => $variable->label(),
                'type' => $variable->type(),
                'value' => $variable->value(),
            ];
            if (strpos(strtolower($variable->label()), 'ecf-') === 0) {
                $ecf[] = $entry;
            } else {
                $foreign[] = $entry;
            }
        }

        wp_send_json_success(['ecf' => $ecf, 'foreign' => $foreign]);
    }

    public function ajax_get_classes() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            wp_send_json_error('Unauthorized');
        }

        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            wp_send_json_error('Elementor global classes repository not available.');
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        $order = $current['order'] ?? [];

        if (!is_array($items)) {
            $items = [];
        }

        $ordered_ids = [];
        foreach ($order as $id) {
            if (isset($items[$id])) {
                $ordered_ids[] = $id;
            }
        }
        foreach (array_keys($items) as $id) {
            if (!in_array($id, $ordered_ids, true)) {
                $ordered_ids[] = $id;
            }
        }

        $ecf = [];
        $foreign = [];

        foreach ($ordered_ids as $id) {
            $item = $items[$id];
            $entry = [
                'id' => $id,
                'label' => $item['label'] ?? $id,
                'type' => $this->native_class_category($item),
                'value' => $this->native_class_preview_value($item),
            ];

            if ($this->is_ecf_native_class($id, $item)) {
                $ecf[] = $entry;
            } else {
                $foreign[] = $entry;
            }
        }

        wp_send_json_success(['ecf' => $ecf, 'foreign' => $foreign]);
    }

    public function ajax_delete_variables() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            wp_send_json_error('Unauthorized');
        }

        $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
        if (empty($ids)) {
            wp_send_json_error('No IDs provided.');
        }

        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            wp_send_json_error('Elementor variable classes not available.');
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            wp_send_json_error('No active kit.');
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $deleted = 0;

        foreach ($collection->all() as $id => $variable) {
            if (in_array($id, $ids, true) && $this->is_ecf_native_variable($variable) && $this->delete_native_variable_entity($collection, $id, $variable)) {
                $deleted++;
            }
        }

        $repo->save($collection);
        $this->clear_elementor_sync_caches();

        wp_send_json_success(['deleted' => $deleted]);
    }

    public function ajax_update_variable() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            wp_send_json_error('Unauthorized');
        }

        $id = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
        $label = sanitize_text_field(wp_unslash($_POST['label'] ?? ''));
        $type = sanitize_key($_POST['type'] ?? '');
        $value = wp_unslash($_POST['value'] ?? '');

        if ($id === '' || $label === '' || $type === '') {
            wp_send_json_error('Missing required fields.');
        }

        if (!in_array($type, ['global-color-variable', 'global-size-variable', 'global-string-variable'], true)) {
            wp_send_json_error('Unsupported variable type.');
        }

        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            wp_send_json_error('Elementor variable classes not available.');
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            wp_send_json_error('No active kit.');
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $target = null;

        foreach ($collection->all() as $variable_id => $variable) {
            if ((string) $variable_id === $id) {
                $target = $variable;
                break;
            }
        }

        if (!$target) {
            wp_send_json_error('Variable not found.');
        }

        if ($this->is_ecf_native_variable($target)) {
            wp_send_json_error('Generated ECF variables cannot be edited here.');
        }

        if ($type === 'global-color-variable') {
            $sanitized_value = $this->sanitize_css_color_value($value);
        } elseif ($type === 'global-size-variable') {
            $sanitized_value = $this->sanitize_css_size_value($value);
        } else {
            $sanitized_value = sanitize_text_field($value);
        }

        if ($sanitized_value === '') {
            wp_send_json_error('Invalid variable value.');
        }

        $target->apply_changes([
            'label' => $label,
            'type' => $type,
            'value' => $sanitized_value,
        ]);

        if (method_exists($target, 'is_deleted') && $target->is_deleted() && method_exists($target, 'restore')) {
            $target->restore();
        }

        $repo->save($collection);
        $this->clear_elementor_sync_caches();

        wp_send_json_success([
            'item' => [
                'id' => $id,
                'label' => $label,
                'type' => $type,
                'value' => $sanitized_value,
            ],
        ]);
    }

    public function ajax_delete_classes() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            wp_send_json_error('Unauthorized');
        }

        $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
        if (empty($ids)) {
            wp_send_json_error('No IDs provided.');
        }

        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            wp_send_json_error('Elementor global classes repository not available.');
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        $order = $current['order'] ?? [];
        $deleted = 0;

        if (!is_array($items)) {
            $items = [];
        }

        foreach ($ids as $id) {
            if (isset($items[$id]) && $this->is_ecf_native_class($id, is_array($items[$id]) ? $items[$id] : [])) {
                unset($items[$id]);
                $order = array_values(array_filter($order, static fn($entry_id) => $entry_id !== $id));
                $deleted++;
            }
        }

        $repo->put($items, $order);
        $this->clear_elementor_sync_caches();

        wp_send_json_success(['deleted' => $deleted]);
    }

    public function handle_export() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_export');

        $settings = $this->get_settings();
        $filename = 'ecf-framework-' . date('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        echo wp_json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function handle_import() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_import');

        if (empty($_FILES['ecf_import_file']['tmp_name'])) {
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=error&ecf_message=' . rawurlencode('Keine Datei hochgeladen.')));
            exit;
        }

        $file = $_FILES['ecf_import_file'];
        $filename = sanitize_file_name($file['name'] ?? '');
        $filesize = (int) ($file['size'] ?? 0);
        $max_size = 1024 * 1024 * 2;

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=error&ecf_message=' . rawurlencode('Datei-Upload fehlgeschlagen.')));
            exit;
        }

        if ($filename === '' || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'json') {
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=error&ecf_message=' . rawurlencode('Bitte eine gültige JSON-Datei hochladen.')));
            exit;
        }

        if ($filesize <= 0 || $filesize > $max_size) {
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=error&ecf_message=' . rawurlencode('Die JSON-Datei ist leer oder zu groß.')));
            exit;
        }

        $content = file_get_contents($_FILES['ecf_import_file']['tmp_name']);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=error&ecf_message=' . rawurlencode('Ungültige JSON-Datei.')));
            exit;
        }

        $sanitized = $this->sanitize_settings($data);
        update_option($this->option_name, $sanitized);

        wp_safe_redirect(admin_url('admin.php?page=ecf-framework&ecf_sync=ok&ecf_message=' . rawurlencode('Einstellungen erfolgreich importiert.')));
        exit;
    }

    public function rest_sync_native(\WP_REST_Request $request) {
        try {
            $var_result = $this->sync_native_variables_merge();
            $class_result = $this->sync_native_classes_merge();

            return rest_ensure_response([
                'success' => true,
                'variables' => $var_result,
                'classes' => $class_result,
                'message' => 'Native Elementor sync completed.',
            ]);
        } catch (\Throwable $e) {
            return new \WP_Error(
                'ecf_sync_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}
