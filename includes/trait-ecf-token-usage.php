<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Token-Usage-Inspector: scant _elementor_data aller Posts und mappt
 * Variable-IDs (e-gv-XXX) und Class-IDs (g-ecf-XXX) zurück auf Layrix-
 * Token-Labels (z.B. ecf-space-m, ecf-color-primary).
 *
 * Nutzt dieselben Parsing-Primitive wie der bestehende Sync-Code — kein
 * neues Risiko durch Custom-Parser.
 */
trait ECF_Framework_Token_Usage_Trait {
    /**
     * Findet alle Vorkommen eines Layrix-Tokens (Label) in Posts.
     *
     * @param string $token_label z.B. 'ecf-space-m' oder 'ecf-color-primary'
     * @return array Liste von Vorkommen, jeweils:
     *     - post_id
     *     - post_title
     *     - post_type
     *     - edit_url (Elementor-Editor-URL)
     *     - hits: int (Anzahl Vorkommen in diesem Post)
     *     - kinds: ['variable' => bool, 'class' => bool]
     */
    public function scan_token_usage(string $token_label): array {
        $token_label = trim($token_label);
        if ($token_label === '') return [];

        // 1. Variable-ID & Class-ID für das Token ermitteln
        $needles = [];
        if (method_exists($this, 'lookup_synced_variable_id')) {
            $var_id = $this->lookup_synced_variable_id($token_label);
            if ($var_id) $needles['variable'] = (string) $var_id;
        }
        // Class-IDs sind deterministisch — nur Token-Labels die Klassennamen sind
        // (z.B. 'ecf-button', 'ecf-heading-1') werden auch als Class gesucht.
        // Klassen mit gleichem Label-Format wie Tokens existieren nicht, aber wenn
        // jemand explizit einen Klassen-Namen sucht, finden wir die Klasse.
        $needles['class_id'] = 'g-ecf-' . substr(md5($token_label), 0, 10);

        // Auch Real-Class-ID aus Registry — falls die Klasse mit anderer ID existiert
        if (method_exists($this, 'reverse_lookup_variable_label')
            || class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            try {
                if (class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
                    $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()
                        ->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
                    $current = $repo->all()->get();
                    foreach (($current['items'] ?? []) as $id => $item) {
                        if (!is_array($item)) continue;
                        if (strtolower((string) ($item['label'] ?? '')) === strtolower($token_label)) {
                            $needles['class_id_real'] = (string) $id;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        if (empty($needles['variable']) && empty($needles['class_id']) && empty($needles['class_id_real'])) {
            return [];
        }

        // 2. Alle Posts mit _elementor_data scannen (raw SQL für Performance,
        //    `LIKE`-Filter pro needle damit nur potenziell relevante Posts geladen werden)
        global $wpdb;
        $like_parts = [];
        foreach ($needles as $needle) {
            if ($needle === '') continue;
            $like_parts[] = $wpdb->prepare('meta_value LIKE %s', '%' . $wpdb->esc_like($needle) . '%');
        }
        if (empty($like_parts)) return [];

        $sql = "
            SELECT p.ID, p.post_title, p.post_type, pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_elementor_data'
              AND (" . implode(' OR ', $like_parts) . ")
              AND p.post_status IN ('publish', 'draft', 'private', 'pending', 'future')
            LIMIT 500
        ";

        $rows = $wpdb->get_results($sql);
        if (empty($rows)) return [];

        // 3. Pro Post: zähle Vorkommen pro Needle-Type
        $results = [];
        foreach ($rows as $row) {
            $haystack = (string) $row->meta_value;
            $hits_var   = !empty($needles['variable']) ? substr_count($haystack, $needles['variable']) : 0;
            $hits_class = 0;
            if (!empty($needles['class_id'])) {
                $hits_class += substr_count($haystack, $needles['class_id']);
            }
            if (!empty($needles['class_id_real']) && (empty($needles['class_id']) || $needles['class_id_real'] !== $needles['class_id'])) {
                $hits_class += substr_count($haystack, $needles['class_id_real']);
            }
            $total = $hits_var + $hits_class;
            if ($total === 0) continue;

            $edit_url = $this->build_elementor_edit_url((int) $row->ID);
            $results[] = [
                'post_id'    => (int) $row->ID,
                'post_title' => $row->post_title !== '' ? $row->post_title : sprintf('(#%d ohne Titel)', (int) $row->ID),
                'post_type'  => (string) $row->post_type,
                'edit_url'   => $edit_url,
                'hits'       => $total,
                'kinds'      => [
                    'variable' => $hits_var > 0,
                    'class'    => $hits_class > 0,
                ],
            ];
        }

        // Sortiere nach Anzahl Hits absteigend
        usort($results, static function ($a, $b) {
            return ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0);
        });

        return $results;
    }

    private function build_elementor_edit_url(int $post_id): string {
        if ($post_id <= 0) return '';
        $url = admin_url('post.php?post=' . $post_id . '&action=elementor');
        return $url;
    }

    /**
     * REST: GET /wp-json/ecf-framework/v1/token-usage?token=ecf-space-m
     */
    public function rest_token_usage(\WP_REST_Request $request) {
        $token = (string) $request->get_param('token');
        if ($token === '') {
            return new \WP_Error('ecf_token_required', 'token param required', ['status' => 400]);
        }
        $results = $this->scan_token_usage($token);
        return rest_ensure_response([
            'success' => true,
            'token'   => $token,
            'count'   => count($results),
            'results' => $results,
        ]);
    }
}
