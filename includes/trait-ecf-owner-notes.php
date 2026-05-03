<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Owner-only notes / ideas dashboard.
 *
 * Visible only when the current user's email matches the hardcoded owner
 * email (or LAYRIX_OWNER_EMAIL constant if defined). Other installs never
 * even create the storage option — the menu button and page render are
 * skipped, no DB write happens.
 */
trait ECF_Framework_Owner_Notes_Trait {

    private function layrix_owner_email(): string {
        if (defined('LAYRIX_OWNER_EMAIL')) {
            return strtolower(trim((string) constant('LAYRIX_OWNER_EMAIL')));
        }
        return 'info@kaiser-alexander.de';
    }

    public function is_layrix_owner(): bool {
        if (!is_user_logged_in()) {
            return false;
        }
        $email = strtolower(trim((string) wp_get_current_user()->user_email));
        return $email !== '' && $email === $this->layrix_owner_email();
    }

    private function owner_notes_option_name(): string {
        return 'ecf_layrix_owner_notes';
    }

    private function get_owner_notes(): array {
        if (!$this->is_layrix_owner()) {
            return [];
        }
        $raw = get_option($this->owner_notes_option_name(), []);
        return is_array($raw) ? array_values($raw) : [];
    }

    private function save_owner_notes(array $notes): void {
        if (!$this->is_layrix_owner()) {
            return;
        }
        update_option($this->owner_notes_option_name(), array_values($notes), false);
    }

    private function sanitize_owner_note(array $input, ?array $existing = null): array {
        $allowed_status = ['idea', 'doing', 'done', 'parked'];
        $allowed_level  = ['low', 'medium', 'high'];
        $allowed_cat    = array_keys($this->owner_note_categories());

        return [
            'id'       => $existing['id']      ?? ('idea_' . wp_generate_uuid4()),
            'title'    => sanitize_text_field((string) ($input['title']   ?? '')),
            'desc'     => wp_kses_post((string)        ($input['desc']    ?? '')),
            'time'     => sanitize_text_field((string) ($input['time']    ?? '')),
            'version'  => sanitize_text_field((string) ($input['version'] ?? '')),
            'category' => in_array(($input['category'] ?? ''), $allowed_cat,    true) ? $input['category'] : ($existing['category'] ?? 'feature'),
            'risk'     => in_array(($input['risk']     ?? ''), $allowed_level,  true) ? $input['risk']     : ($existing['risk']     ?? 'medium'),
            'value'    => in_array(($input['value']    ?? ''), $allowed_level,  true) ? $input['value']    : ($existing['value']    ?? 'medium'),
            'status'   => in_array(($input['status']   ?? ''), $allowed_status, true) ? $input['status']   : ($existing['status']   ?? 'idea'),
            'position' => isset($input['position']) ? (int) $input['position'] : (int) ($existing['position'] ?? 0),
            'created'  => (int) ($existing['created'] ?? time()),
            'updated'  => time(),
        ];
    }

    private function owner_note_categories(): array {
        return [
            'feature'    => ['label' => __('Feature',    'ecf-framework'), 'color' => '#3B82F6'],
            'ui'         => ['label' => __('UI/UX',      'ecf-framework'), 'color' => '#A855F7'],
            'onboarding' => ['label' => __('Onboarding', 'ecf-framework'), 'color' => '#14B8A6'],
            'docs'       => ['label' => __('Docs',       'ecf-framework'), 'color' => '#6366F1'],
            'migration'  => ['label' => __('Migration',  'ecf-framework'), 'color' => '#F97316'],
            'elementor'  => ['label' => __('Elementor',  'ecf-framework'), 'color' => '#22C55E'],
            'bug'        => ['label' => __('Bug',        'ecf-framework'), 'color' => '#EF4444'],
            'workflow'   => ['label' => __('Workflow',   'ecf-framework'), 'color' => '#06B6D4'],
            'research'   => ['label' => __('Research',   'ecf-framework'), 'color' => '#6B7280'],
        ];
    }

    public function handle_owner_note_save(): void {
        if (!$this->is_layrix_owner()) {
            wp_die(__('Forbidden', 'ecf-framework'), '', ['response' => 403]);
        }
        check_admin_referer('ecf_owner_note_save');

        $input = wp_unslash($_POST['note'] ?? []);
        if (!is_array($input)) $input = [];

        $notes = $this->get_owner_notes();
        $editing_id = sanitize_text_field((string) ($_POST['edit_id'] ?? ''));

        if ($editing_id !== '') {
            $found = false;
            foreach ($notes as $i => $n) {
                if (($n['id'] ?? '') === $editing_id) {
                    $notes[$i] = $this->sanitize_owner_note($input, $n);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $notes[] = $this->sanitize_owner_note($input);
            }
        } else {
            $notes[] = $this->sanitize_owner_note($input);
        }

        $this->save_owner_notes($notes);

        wp_safe_redirect(add_query_arg(['saved' => '1'], $this->owner_notes_page_url()));
        exit;
    }

    public function handle_owner_note_delete(): void {
        if (!$this->is_layrix_owner()) {
            wp_die(__('Forbidden', 'ecf-framework'), '', ['response' => 403]);
        }
        $id = sanitize_text_field((string) ($_GET['id'] ?? ''));
        check_admin_referer('ecf_owner_note_delete_' . $id);

        $notes = array_values(array_filter(
            $this->get_owner_notes(),
            static fn($n) => ($n['id'] ?? '') !== $id
        ));
        $this->save_owner_notes($notes);

        wp_safe_redirect(add_query_arg(['deleted' => '1'], $this->owner_notes_page_url()));
        exit;
    }

    private function owner_notes_page_url(): string {
        return admin_url('admin.php?page=ecf-framework#ideas');
    }

    private function owner_note_status_label(string $status): string {
        switch ($status) {
            case 'doing':  return __('In Arbeit', 'ecf-framework');
            case 'done':   return __('Erledigt',  'ecf-framework');
            case 'parked': return __('Geparkt',   'ecf-framework');
            default:       return __('Idee',      'ecf-framework');
        }
    }

    private function owner_note_level_label(string $level): string {
        switch ($level) {
            case 'low':  return __('niedrig', 'ecf-framework');
            case 'high': return __('hoch',    'ecf-framework');
            default:     return __('mittel',  'ecf-framework');
        }
    }

    public function rest_owner_permission(): bool {
        return $this->is_layrix_owner();
    }

    public function rest_owner_notes_list(\WP_REST_Request $request) {
        return rest_ensure_response([
            'success' => true,
            'count'   => count($this->get_owner_notes()),
            'notes'   => $this->get_owner_notes(),
        ]);
    }

    public function rest_owner_note_create(\WP_REST_Request $request) {
        $payload = (array) $request->get_json_params();
        if (empty($payload)) $payload = (array) $request->get_params();

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return new \WP_Error('ecf_owner_note_invalid', 'title required', ['status' => 400]);
        }

        $notes = $this->get_owner_notes();
        $note  = $this->sanitize_owner_note($payload);
        $notes[] = $note;
        $this->save_owner_notes($notes);

        return rest_ensure_response([
            'success' => true,
            'note'    => $note,
            'count'   => count($notes),
        ]);
    }

    public function rest_owner_note_delete(\WP_REST_Request $request) {
        $id = sanitize_text_field((string) $request['id']);
        $before = $this->get_owner_notes();
        $after  = array_values(array_filter($before, static fn($n) => ($n['id'] ?? '') !== $id));
        $this->save_owner_notes($after);
        return rest_ensure_response([
            'success' => true,
            'deleted' => (count($before) !== count($after)),
        ]);
    }

    public function rest_owner_notes_reorder(\WP_REST_Request $request) {
        $payload = (array) $request->get_json_params();
        $ids = array_values(array_filter(array_map(
            static fn($v) => sanitize_text_field((string) $v),
            (array) ($payload['ids'] ?? [])
        )));
        if (empty($ids)) {
            return new \WP_Error('ecf_owner_note_invalid', 'ids required', ['status' => 400]);
        }
        $notes = $this->get_owner_notes();
        $by_id = [];
        foreach ($notes as $n) { $by_id[$n['id'] ?? ''] = $n; }
        // Apply new positions in the order received. Notes not in the
        // payload keep their current position (shifted to end).
        $i = 0;
        foreach ($ids as $id) {
            if (isset($by_id[$id])) {
                $by_id[$id]['position'] = $i++;
                $by_id[$id]['updated']  = time();
            }
        }
        // Append any notes the client didn't reorder, after the reordered set.
        foreach ($notes as $n) {
            $nid = $n['id'] ?? '';
            if ($nid && !in_array($nid, $ids, true)) {
                $by_id[$nid]['position'] = $i++;
            }
        }
        $this->save_owner_notes(array_values($by_id));
        return rest_ensure_response(['success' => true, 'count' => count($by_id)]);
    }

    public function render_owner_notes_sidebar_button(): void {
        if (!$this->is_layrix_owner()) return;
        $notes = $this->get_owner_notes();
        $total = count($notes);
        $open  = 0;
        foreach ($notes as $n) {
            $s = $n['status'] ?? 'idea';
            if ($s === 'idea' || $s === 'doing') $open++;
        }
        // Badge color: orange if there's open work, muted green when all closed.
        $bg = $open > 0 ? '#f59e0b' : '#10B981';
        $fg = '#000';
        ?>
        <button type="button" class="v2-ni" data-v2-page="ideas" title="<?php echo esc_attr(sprintf(__('%1$d offen von %2$d gesamt', 'ecf-framework'), $open, $total)); ?>">
            <svg viewBox="0 0 13 13" fill="currentColor"><path d="M2 2h9v9H2zM4 4h5M4 6h5M4 8h3" stroke="currentColor" stroke-width="0.8" fill="none" opacity=".55"/></svg>
            <?php esc_html_e('Ideen', 'ecf-framework'); ?>
            <span class="v2-tc" style="background:<?php echo esc_attr($bg); ?>;color:<?php echo esc_attr($fg); ?>"><?php echo (int) $open; ?>/<?php echo (int) $total; ?></span>
        </button>
        <?php
    }

    public function render_owner_notes_page(): void {
        if (!$this->is_layrix_owner()) return;
        $notes = $this->get_owner_notes();
        // Sort: open status (idea/doing) first, closed (done/parked) last.
        // Within each group: by manual position ASC, then by created DESC.
        $closed = ['done' => true, 'parked' => true];
        usort($notes, static function ($a, $b) use ($closed) {
            $a_closed = isset($closed[$a['status'] ?? 'idea']) ? 1 : 0;
            $b_closed = isset($closed[$b['status'] ?? 'idea']) ? 1 : 0;
            if ($a_closed !== $b_closed) return $a_closed <=> $b_closed;
            $ap = (int) ($a['position'] ?? PHP_INT_MAX);
            $bp = (int) ($b['position'] ?? PHP_INT_MAX);
            if ($ap !== $bp) return $ap <=> $bp;
            return ($b['created'] ?? 0) <=> ($a['created'] ?? 0);
        });
        $rest_url   = esc_url_raw(rest_url('ecf-framework/v1/owner-notes'));
        $rest_nonce = wp_create_nonce('wp_rest');
        ?>
        <style>
            #ecf-v2-page-ideas .ecf-idea-list { display: grid; gap: 14px; }
            #ecf-v2-page-ideas .ecf-idea-card {
                background: var(--v2-s1);
                border: 1px solid var(--v2-border);
                border-radius: 10px;
                padding: 18px 20px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            #ecf-v2-page-ideas .ecf-idea-card--done {
                background: linear-gradient(135deg, rgba(16,185,129,.06), var(--v2-s1) 60%);
                border-color: rgba(16,185,129,.25);
            }
            #ecf-v2-page-ideas .ecf-idea-card--done .ecf-idea-card__title::before {
                content: '';
                display: inline-block;
                width: 8px;
                height: 8px;
                background: #10B981;
                border-radius: 50%;
                margin-right: 8px;
                vertical-align: middle;
                box-shadow: 0 0 0 0 rgba(16,185,129,.5);
                animation: ecf-idea-pulse 2s infinite;
            }
            @keyframes ecf-idea-pulse {
                0%   { box-shadow: 0 0 0 0 rgba(16,185,129,.55); }
                70%  { box-shadow: 0 0 0 8px rgba(16,185,129,0); }
                100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
            }
            #ecf-v2-page-ideas .ecf-idea-card--parked { opacity: 0.5; }
            #ecf-v2-page-ideas .ecf-idea-card__head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                flex-wrap: wrap;
            }
            #ecf-v2-page-ideas .ecf-idea-card__title {
                margin: 0;
                font-size: 15px;
                font-weight: 600;
                line-height: 1.35;
                color: var(--v2-text);
                flex: 1 1 280px;
                min-width: 0;
            }
            #ecf-v2-page-ideas .ecf-idea-card__meta {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
                flex-shrink: 0;
                align-items: center;
            }
            #ecf-v2-page-ideas .ecf-idea-chip {
                font-size: 10.5px;
                font-family: var(--v2-mono);
                font-weight: 500;
                padding: 3px 8px;
                border-radius: 4px;
                color: #fff;
                white-space: nowrap;
            }
            #ecf-v2-page-ideas .ecf-idea-chip--version {
                background: transparent;
                border: 1px solid rgba(255,255,255,.18);
                color: var(--v2-text2);
            }
            #ecf-v2-page-ideas .ecf-idea-card--done .ecf-idea-chip--version {
                border-color: #10B981;
                color: #6ee7b7;
            }
            #ecf-v2-page-ideas .ecf-idea-card__effort {
                font-size: 12px;
                color: var(--v2-text2);
                font-family: var(--v2-mono);
                background: rgba(255,255,255,.03);
                padding: 6px 10px;
                border-radius: 6px;
                border-left: 3px solid var(--v2-accent);
            }
            #ecf-v2-page-ideas .ecf-idea-card__effort-label {
                color: var(--v2-text3);
                font-weight: 500;
                margin-right: 4px;
            }
            #ecf-v2-page-ideas .ecf-idea-card__desc {
                font-size: 13px;
                line-height: 1.6;
                color: var(--v2-text2);
            }
            #ecf-v2-page-ideas .ecf-idea-card__foot {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                padding-top: 10px;
                border-top: 1px solid var(--v2-border);
                margin-top: 2px;
            }
            #ecf-v2-page-ideas .ecf-idea-card__date {
                font-size: 11px;
                color: var(--v2-text3);
                font-family: var(--v2-mono);
            }
            #ecf-v2-page-ideas .ecf-idea-card__actions { display: flex; gap: 6px; }
            #ecf-v2-page-ideas .ecf-idea-btn {
                font-size: 11.5px;
                padding: 5px 10px;
                background: rgba(255,255,255,.04);
                border: 1px solid var(--v2-border);
                color: var(--v2-text2);
                border-radius: 4px;
                cursor: pointer;
                transition: all .12s;
            }
            #ecf-v2-page-ideas .ecf-idea-btn:hover { background: rgba(255,255,255,.08); color: var(--v2-text); }
            #ecf-v2-page-ideas .ecf-idea-btn--danger:hover { background: rgba(239,68,68,.15); border-color: #ef4444; color: #fca5a5; }

            #ecf-v2-page-ideas .ecf-idea-filters {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
                margin: 0 0 14px;
                padding: 10px 0;
                border-bottom: 1px solid var(--v2-border);
            }
            #ecf-v2-page-ideas .ecf-idea-filter {
                font-size: 11.5px;
                padding: 5px 11px;
                background: rgba(255,255,255,.03);
                border: 1px solid var(--v2-border);
                color: var(--v2-text2);
                border-radius: 100px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all .12s;
            }
            #ecf-v2-page-ideas .ecf-idea-filter:hover { background: rgba(255,255,255,.07); color: var(--v2-text); }
            #ecf-v2-page-ideas .ecf-idea-filter--on { background: var(--ecf-filter-color, var(--v2-accent)); border-color: var(--ecf-filter-color, var(--v2-accent)); color: #fff; }
            #ecf-v2-page-ideas .ecf-idea-filter__count {
                font-family: var(--v2-mono);
                font-size: 10px;
                opacity: .7;
                background: rgba(0,0,0,.25);
                padding: 1px 6px;
                border-radius: 100px;
            }

            /* View toggle */
            #ecf-v2-page-ideas .ecf-idea-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 12px;
                flex-wrap: wrap;
            }
            #ecf-v2-page-ideas .ecf-idea-viewtoggle { display: inline-flex; gap: 0; border: 1px solid var(--v2-border); border-radius: 6px; overflow: hidden; }
            #ecf-v2-page-ideas .ecf-idea-viewtoggle button {
                font-size: 11.5px;
                padding: 5px 12px;
                background: transparent;
                border: 0;
                color: var(--v2-text3);
                cursor: pointer;
                border-right: 1px solid var(--v2-border);
            }
            #ecf-v2-page-ideas .ecf-idea-viewtoggle button:last-child { border-right: 0; }
            #ecf-v2-page-ideas .ecf-idea-viewtoggle button:hover { background: rgba(255,255,255,.04); color: var(--v2-text); }
            #ecf-v2-page-ideas .ecf-idea-viewtoggle button.is-on { background: var(--v2-accent); color: #fff; }

            /* Table view */
            #ecf-v2-page-ideas .ecf-idea-list[data-view="cards"] .ecf-idea-table { display: none; }
            #ecf-v2-page-ideas .ecf-idea-list[data-view="table"] .ecf-idea-card { display: none; }
            #ecf-v2-page-ideas .ecf-idea-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 12px;
                table-layout: fixed;
            }
            #ecf-v2-page-ideas .ecf-idea-table th {
                text-align: left;
                font-size: 10.5px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: var(--v2-text3);
                padding: 8px 10px;
                border-bottom: 1px solid var(--v2-border);
                background: var(--v2-s1);
                position: sticky;
                top: 0;
                z-index: 1;
            }
            #ecf-v2-page-ideas .ecf-idea-table td {
                padding: 9px 10px;
                border-bottom: 1px solid var(--v2-border);
                vertical-align: middle;
            }
            #ecf-v2-page-ideas .ecf-idea-table tr:hover td { background: rgba(255,255,255,.025); }
            #ecf-v2-page-ideas .ecf-idea-table tr.is-done td { background: rgba(16,185,129,.04); }
            #ecf-v2-page-ideas .ecf-idea-table tr.is-done:hover td { background: rgba(16,185,129,.08); }
            #ecf-v2-page-ideas .ecf-idea-table tr.is-parked td { opacity: .5; }
            #ecf-v2-page-ideas .ecf-idea-table-status {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 11px;
                color: var(--v2-text2);
                white-space: nowrap;
            }
            #ecf-v2-page-ideas .ecf-idea-table-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex-shrink: 0;
            }
            #ecf-v2-page-ideas .ecf-idea-table-dot.is-done {
                box-shadow: 0 0 0 0 rgba(16,185,129,.5);
                animation: ecf-idea-pulse 2s infinite;
            }
            #ecf-v2-page-ideas .ecf-idea-table-title {
                font-weight: 500;
                color: var(--v2-text);
                cursor: pointer;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            #ecf-v2-page-ideas .ecf-idea-table-title:hover { color: var(--v2-accent2); }
            #ecf-v2-page-ideas .ecf-idea-prio { font-family: var(--v2-mono); font-size: 11px; white-space: nowrap; }
            #ecf-v2-page-ideas .ecf-idea-prio-r { color: #f59e0b; }
            #ecf-v2-page-ideas .ecf-idea-prio-n { color: #22c55e; }
            #ecf-v2-page-ideas .ecf-idea-version-cell { font-family: var(--v2-mono); color: var(--v2-text2); }
            #ecf-v2-page-ideas .ecf-idea-table-actions { white-space: nowrap; text-align: right; }
            #ecf-v2-page-ideas .ecf-idea-table-actions button {
                font-size: 11px;
                padding: 3px 8px;
                background: transparent;
                border: 1px solid var(--v2-border);
                border-radius: 4px;
                color: var(--v2-text3);
                cursor: pointer;
                margin-left: 4px;
            }
            #ecf-v2-page-ideas .ecf-idea-table-actions button:hover { color: var(--v2-text); border-color: var(--v2-border2); }
            #ecf-v2-page-ideas .ecf-idea-table-actions button.is-danger:hover { color: #fca5a5; border-color: #ef4444; }
            #ecf-v2-page-ideas .ecf-idea-drag-handle {
                color: var(--v2-text3);
                text-align: center;
                user-select: none;
                cursor: grab;
                font-size: 14px;
                line-height: 1;
                opacity: .4;
                transition: opacity .12s;
            }
            #ecf-v2-page-ideas .ecf-idea-table tr:hover .ecf-idea-drag-handle { opacity: 1; }
            #ecf-v2-page-ideas .ecf-idea-drag-handle:active { cursor: grabbing; }
            #ecf-v2-page-ideas .ecf-idea-table tr.is-done .ecf-idea-drag-handle,
            #ecf-v2-page-ideas .ecf-idea-table tr.is-parked .ecf-idea-drag-handle { cursor: default; opacity: .15; }
            #ecf-v2-page-ideas .ecf-idea-table tr.is-dragging { opacity: .35; }
            #ecf-v2-page-ideas .ecf-idea-table tr.is-drop-before > td { box-shadow: inset 0 2px 0 0 var(--v2-accent); }
            #ecf-v2-page-ideas .ecf-idea-table tr.is-drop-after > td { box-shadow: inset 0 -2px 0 0 var(--v2-accent); }
        </style>
        <div id="ecf-v2-page-ideas" class="v2-page">
            <div class="v2-topbar">
                <div class="v2-crumb"><span class="v2-crumb-cur"><?php esc_html_e('Ideen', 'ecf-framework'); ?></span> <span style="opacity:.5;font-size:11px;margin-left:6px">— <?php esc_html_e('Owner-only Notizbuch', 'ecf-framework'); ?></span></div>
            </div>
            <div class="v2-page-body">
                <div class="v2-content">
                    <div id="ecf-idea-flash" class="v2-sec" style="display:none;padding:10px 14px;margin-bottom:14px"></div>

                    <div class="v2-ph" style="display:flex;justify-content:space-between;align-items:flex-end;gap:14px">
                        <div>
                            <h1><?php esc_html_e('Ideen & Überlegungen', 'ecf-framework'); ?></h1>
                            <p><?php esc_html_e('Privates Notizbuch für Feature-Ideen, Bewertungen und ToDos. Sichtbar nur für dich.', 'ecf-framework'); ?></p>
                        </div>
                        <button type="button" class="v2-btn v2-btn--primary" id="ecf-idea-new-btn"><?php esc_html_e('+ Neue Idee', 'ecf-framework'); ?></button>
                    </div>

                    <!-- New / Edit "form" — div not <form>, submits via REST so it can live inside the main settings <form> without nested-form HTML errors. Hidden by default, toggled via the "Neue Idee" button or "Bearbeiten" on a list row. -->
                    <div class="v2-sec" id="ecf-idea-form-wrap" style="display:none">
                        <div class="v2-sh" id="ecf-idea-form-title"><?php esc_html_e('Neue Idee anlegen', 'ecf-framework'); ?></div>
                        <div id="ecf-idea-form" style="display:grid;gap:10px">
                            <input type="hidden" id="ecf-idea-edit-id" value="">
                            <input type="text" class="v2-si" id="ecf-idea-title" placeholder="<?php esc_attr_e('Titel der Idee', 'ecf-framework'); ?>">
                            <textarea class="v2-si" id="ecf-idea-desc" rows="4" placeholder="<?php esc_attr_e('Beschreibung — was, warum, wie ungefähr', 'ecf-framework'); ?>"></textarea>
                            <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:10px">
                                <label><span style="display:block;font-size:11px;opacity:.7;margin-bottom:4px"><?php esc_html_e('Kategorie', 'ecf-framework'); ?></span>
                                    <select class="v2-si" id="ecf-idea-category">
                                        <?php foreach ($this->owner_note_categories() as $key => $cat): ?>
                                            <option value="<?php echo esc_attr($key); ?>"<?php echo $key === 'feature' ? ' selected' : ''; ?>><?php echo esc_html($cat['label']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label><span style="display:block;font-size:11px;opacity:.7;margin-bottom:4px"><?php esc_html_e('Aufwand', 'ecf-framework'); ?></span>
                                    <input type="text" class="v2-si" id="ecf-idea-time" placeholder="z.B. 6-9h">
                                </label>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
                                <label><span style="display:block;font-size:11px;opacity:.7;margin-bottom:4px"><?php esc_html_e('Version', 'ecf-framework'); ?></span>
                                    <input type="text" class="v2-si" id="ecf-idea-version" placeholder="z.B. 0.5.10">
                                </label>
                                <label><span style="display:block;font-size:11px;opacity:.7;margin-bottom:4px"><?php esc_html_e('Risiko', 'ecf-framework'); ?></span>
                                    <select class="v2-si" id="ecf-idea-risk">
                                        <option value="low"><?php esc_html_e('niedrig', 'ecf-framework'); ?></option>
                                        <option value="medium" selected><?php esc_html_e('mittel', 'ecf-framework'); ?></option>
                                        <option value="high"><?php esc_html_e('hoch', 'ecf-framework'); ?></option>
                                    </select>
                                </label>
                                <label><span style="display:block;font-size:11px;opacity:.7;margin-bottom:4px"><?php esc_html_e('Nutzen', 'ecf-framework'); ?></span>
                                    <select class="v2-si" id="ecf-idea-value">
                                        <option value="low"><?php esc_html_e('niedrig', 'ecf-framework'); ?></option>
                                        <option value="medium" selected><?php esc_html_e('mittel', 'ecf-framework'); ?></option>
                                        <option value="high"><?php esc_html_e('hoch', 'ecf-framework'); ?></option>
                                    </select>
                                </label>
                                <label><span style="display:block;font-size:11px;opacity:.7;margin-bottom:4px"><?php esc_html_e('Status', 'ecf-framework'); ?></span>
                                    <select class="v2-si" id="ecf-idea-status">
                                        <option value="idea" selected><?php esc_html_e('Idee', 'ecf-framework'); ?></option>
                                        <option value="doing"><?php esc_html_e('In Arbeit', 'ecf-framework'); ?></option>
                                        <option value="done"><?php esc_html_e('Erledigt', 'ecf-framework'); ?></option>
                                        <option value="parked"><?php esc_html_e('Geparkt', 'ecf-framework'); ?></option>
                                    </select>
                                </label>
                            </div>
                            <div style="display:flex;gap:10px;justify-content:flex-end">
                                <button type="button" class="v2-btn v2-btn--ghost" id="ecf-idea-cancel" style="display:none"><?php esc_html_e('Abbrechen', 'ecf-framework'); ?></button>
                                <button type="button" class="v2-btn v2-btn--primary" id="ecf-idea-submit"><?php esc_html_e('Idee speichern', 'ecf-framework'); ?></button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing notes list -->
                    <div class="v2-sec" style="margin-top:18px">
                        <div class="v2-sh"><?php esc_html_e('Alle Ideen', 'ecf-framework'); ?> <span style="opacity:.5">(<?php echo (int) count($notes); ?>)</span></div>
                        <?php
                            $cat_counts = [];
                            foreach ($notes as $n) {
                                $k = $n['category'] ?? 'feature';
                                $cat_counts[$k] = ($cat_counts[$k] ?? 0) + 1;
                            }
                        ?>
                        <?php if (count($notes) > 0): ?>
                        <div class="ecf-idea-toolbar">
                            <div class="ecf-idea-filters" style="margin:0;padding:0;border:0">
                                <button type="button" class="ecf-idea-filter ecf-idea-filter--on" data-ecf-filter="all"><?php esc_html_e('Alle', 'ecf-framework'); ?> <span class="ecf-idea-filter__count"><?php echo (int) count($notes); ?></span></button>
                                <?php foreach ($this->owner_note_categories() as $key => $cat): if (empty($cat_counts[$key])) continue; ?>
                                    <button type="button" class="ecf-idea-filter" data-ecf-filter="<?php echo esc_attr($key); ?>" style="--ecf-filter-color:<?php echo esc_attr($cat['color']); ?>"><?php echo esc_html($cat['label']); ?> <span class="ecf-idea-filter__count"><?php echo (int) $cat_counts[$key]; ?></span></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="ecf-idea-viewtoggle">
                                <button type="button" data-ecf-view="table" class="is-on"><?php esc_html_e('Tabelle', 'ecf-framework'); ?></button>
                                <button type="button" data-ecf-view="cards"><?php esc_html_e('Karten', 'ecf-framework'); ?></button>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (empty($notes)): ?>
                            <div class="v2-empty-state">
                                <div class="v2-empty-state-icon">💡</div>
                                <div class="v2-empty-state-title"><?php esc_html_e('Noch keine Ideen', 'ecf-framework'); ?></div>
                                <div class="v2-empty-state-desc"><?php esc_html_e('Leg deine erste Idee oben an. Ich kann später Aufwand/Risiko schätzen, du speicherst es zurück.', 'ecf-framework'); ?></div>
                            </div>
                        <?php else: ?>
                            <div class="ecf-idea-list" data-view="table">
                                <!-- Table view (compact overview) -->
                                <table class="ecf-idea-table">
                                    <thead>
                                        <tr>
                                            <th style="width:24px" title="<?php esc_attr_e('Drag zum Sortieren', 'ecf-framework'); ?>"></th>
                                            <th style="width:90px"><?php esc_html_e('Status', 'ecf-framework'); ?></th>
                                            <th><?php esc_html_e('Titel', 'ecf-framework'); ?></th>
                                            <th style="width:120px"><?php esc_html_e('Kategorie', 'ecf-framework'); ?></th>
                                            <th style="width:140px" title="<?php esc_attr_e('Risiko = Implementierungsrisiko, Nutzen = User-Wert', 'ecf-framework'); ?>"><?php esc_html_e('Risiko / Nutzen', 'ecf-framework'); ?></th>
                                            <th style="width:80px"><?php esc_html_e('Version', 'ecf-framework'); ?></th>
                                            <th style="width:130px"><?php esc_html_e('Aufwand', 'ecf-framework'); ?></th>
                                            <th style="width:90px" title="<?php esc_attr_e('Erstellt-Datum', 'ecf-framework'); ?>"><?php esc_html_e('Erstellt', 'ecf-framework'); ?></th>
                                            <th style="width:140px;text-align:right"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($notes as $note):
                                        $status   = $note['status'] ?? 'idea';
                                        $cat_key  = $note['category'] ?? 'feature';
                                        $cats     = $this->owner_note_categories();
                                        $cat_def  = $cats[$cat_key] ?? $cats['feature'];
                                        $status_color = ['idea' => '#3B82F6', 'doing' => '#F59E0B', 'done' => '#10B981', 'parked' => '#6B7280'][$status] ?? '#6B7280';
                                    ?>
                                    <?php $is_open = !in_array($status, ['done', 'parked'], true); ?>
                                    <tr class="is-<?php echo esc_attr($status); ?>" data-idea-category="<?php echo esc_attr($cat_key); ?>" data-idea-id="<?php echo esc_attr($note['id'] ?? ''); ?>" draggable="<?php echo $is_open ? 'true' : 'false'; ?>">
                                        <td class="ecf-idea-drag-handle" title="<?php echo $is_open ? esc_attr__('Zum Verschieben ziehen', 'ecf-framework') : esc_attr__('Erledigte Einträge werden automatisch nach unten sortiert', 'ecf-framework'); ?>"><?php echo $is_open ? '⋮⋮' : ''; ?></td>
                                        <td>
                                            <span class="ecf-idea-table-status">
                                                <span class="ecf-idea-table-dot <?php echo $status === 'done' ? 'is-done' : ''; ?>" style="background:<?php echo esc_attr($status_color); ?>"></span>
                                                <?php echo esc_html($this->owner_note_status_label($status)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="ecf-idea-table-title" title="<?php echo esc_attr($note['title'] ?? ''); ?>" data-ecf-idea-edit='<?php echo esc_attr(wp_json_encode($note)); ?>'><?php echo esc_html($note['title'] ?? '—'); ?></div>
                                        </td>
                                        <td><span class="ecf-idea-chip" style="background:<?php echo esc_attr($cat_def['color']); ?>"><?php echo esc_html($cat_def['label']); ?></span></td>
                                        <?php
                                            $risk_lvl  = $note['risk']  ?? 'medium';
                                            $value_lvl = $note['value'] ?? 'medium';
                                            $risk_clr  = ['low' => '#10B981', 'medium' => '#F59E0B', 'high' => '#EF4444'][$risk_lvl]  ?? '#6B7280';
                                            $value_clr = ['low' => '#6B7280', 'medium' => '#3B82F6', 'high' => '#10B981'][$value_lvl] ?? '#6B7280';
                                        ?>
                                        <td>
                                            <span class="ecf-idea-prio">
                                                <span style="color:<?php echo esc_attr($risk_clr); ?>" title="<?php esc_attr_e('Risiko', 'ecf-framework'); ?>"><?php echo esc_html($this->owner_note_level_label($risk_lvl)); ?></span>
                                                <span style="opacity:.4;margin:0 4px">/</span>
                                                <span style="color:<?php echo esc_attr($value_clr); ?>" title="<?php esc_attr_e('Nutzen', 'ecf-framework'); ?>"><?php echo esc_html($this->owner_note_level_label($value_lvl)); ?></span>
                                            </span>
                                        </td>
                                        <td class="ecf-idea-version-cell"><?php echo !empty($note['version']) ? 'v' . esc_html(ltrim($note['version'], 'v')) : '<span style="opacity:.3">—</span>'; ?></td>
                                        <td class="ecf-idea-version-cell"><?php echo !empty($note['time']) ? esc_html($note['time']) : '<span style="opacity:.3">—</span>'; ?></td>
                                        <td class="ecf-idea-version-cell" title="<?php echo esc_attr(date_i18n('d.m.Y H:i', $note['created'] ?? time())); ?>"><?php echo esc_html(date_i18n('d.m.Y', $note['created'] ?? time())); ?></td>
                                        <td class="ecf-idea-table-actions">
                                            <button type="button" data-ecf-idea-edit='<?php echo esc_attr(wp_json_encode($note)); ?>'><?php esc_html_e('Bearbeiten', 'ecf-framework'); ?></button>
                                            <button type="button" class="is-danger" data-ecf-idea-delete="<?php echo esc_attr($note['id'] ?? ''); ?>">✕</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <!-- Card view (detail view) -->
                                <?php foreach ($notes as $note):
                                    $status = $note['status'] ?? 'idea';
                                    $risk   = $note['risk']   ?? 'medium';
                                    $value  = $note['value']  ?? 'medium';
                                    $status_color = ['idea' => '#3B82F6', 'doing' => '#F59E0B', 'done' => '#10B981', 'parked' => '#6B7280'][$status] ?? '#6B7280';
                                    $risk_color   = ['low' => '#10B981', 'medium' => '#F59E0B', 'high' => '#EF4444'][$risk]   ?? '#6B7280';
                                    $value_color  = ['low' => '#6B7280', 'medium' => '#3B82F6', 'high' => '#10B981'][$value]  ?? '#6B7280';
                                ?>
                                <?php
                                    $cat_key  = $note['category'] ?? 'feature';
                                    $cats     = $this->owner_note_categories();
                                    $cat_def  = $cats[$cat_key] ?? $cats['feature'];
                                ?>
                                <article class="ecf-idea-card ecf-idea-card--<?php echo esc_attr($status); ?>" data-idea-id="<?php echo esc_attr($note['id'] ?? ''); ?>" data-idea-category="<?php echo esc_attr($cat_key); ?>" style="border-left:3px solid <?php echo esc_attr($cat_def['color']); ?>">
                                    <header class="ecf-idea-card__head">
                                        <h3 class="ecf-idea-card__title"><?php echo esc_html($note['title'] ?? '—'); ?></h3>
                                        <div class="ecf-idea-card__meta">
                                            <span class="ecf-idea-chip" title="<?php esc_attr_e('Kategorie', 'ecf-framework'); ?>" style="background:<?php echo esc_attr($cat_def['color']); ?>"><?php echo esc_html($cat_def['label']); ?></span>
                                            <span class="ecf-idea-chip" style="background:<?php echo esc_attr($status_color); ?>"><?php echo esc_html($this->owner_note_status_label($status)); ?></span>
                                            <span class="ecf-idea-chip" title="<?php esc_attr_e('Risiko', 'ecf-framework'); ?>" style="background:<?php echo esc_attr($risk_color); ?>"><?php esc_html_e('R', 'ecf-framework'); ?>: <?php echo esc_html($this->owner_note_level_label($risk)); ?></span>
                                            <span class="ecf-idea-chip" title="<?php esc_attr_e('Nutzen', 'ecf-framework'); ?>" style="background:<?php echo esc_attr($value_color); ?>"><?php esc_html_e('N', 'ecf-framework'); ?>: <?php echo esc_html($this->owner_note_level_label($value)); ?></span>
                                            <?php if (!empty($note['version'])): ?>
                                                <span class="ecf-idea-chip ecf-idea-chip--version" title="<?php esc_attr_e('Live seit Version', 'ecf-framework'); ?>">v<?php echo esc_html(ltrim($note['version'], 'v')); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </header>
                                    <?php if (!empty($note['time'])): ?>
                                        <div class="ecf-idea-card__effort"><span class="ecf-idea-card__effort-label"><?php esc_html_e('Aufwand:', 'ecf-framework'); ?></span> <?php echo esc_html($note['time']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($note['desc'])): ?>
                                        <div class="ecf-idea-card__desc"><?php echo wp_kses_post(nl2br(esc_html($note['desc']))); ?></div>
                                    <?php endif; ?>
                                    <footer class="ecf-idea-card__foot">
                                        <time class="ecf-idea-card__date" title="<?php echo esc_attr(sprintf(__('Erstellt: %1$s · Aktualisiert: %2$s', 'ecf-framework'), date_i18n('d.m.Y H:i', $note['created'] ?? time()), date_i18n('d.m.Y H:i', $note['updated'] ?? time()))); ?>">
                                            <?php esc_html_e('Erstellt', 'ecf-framework'); ?>: <?php echo esc_html(date_i18n('d.m.Y', $note['created'] ?? time())); ?>
                                            <?php if (($note['updated'] ?? 0) > ($note['created'] ?? 0) + 60): ?>
                                                · <?php esc_html_e('aktualisiert', 'ecf-framework'); ?> <?php echo esc_html(date_i18n('d.m.Y', $note['updated'] ?? time())); ?>
                                            <?php endif; ?>
                                        </time>
                                        <div class="ecf-idea-card__actions">
                                            <button type="button" class="ecf-idea-btn" data-ecf-idea-edit='<?php echo esc_attr(wp_json_encode($note)); ?>'><?php esc_html_e('Bearbeiten', 'ecf-framework'); ?></button>
                                            <button type="button" class="ecf-idea-btn ecf-idea-btn--danger" data-ecf-idea-delete="<?php echo esc_attr($note['id'] ?? ''); ?>"><?php esc_html_e('Löschen', 'ecf-framework'); ?></button>
                                        </div>
                                    </footer>
                                </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var form = document.getElementById('ecf-idea-form');
            if (!form) return;
            var REST_URL   = <?php echo wp_json_encode($rest_url); ?>;
            var REST_NONCE = <?php echo wp_json_encode($rest_nonce); ?>;
            var formWrap  = document.getElementById('ecf-idea-form-wrap');
            var formTitle = document.getElementById('ecf-idea-form-title');
            var bNew      = document.getElementById('ecf-idea-new-btn');
            var fTitle    = document.getElementById('ecf-idea-title');
            var fDesc     = document.getElementById('ecf-idea-desc');
            var fTime     = document.getElementById('ecf-idea-time');
            var fRisk     = document.getElementById('ecf-idea-risk');
            var fValue    = document.getElementById('ecf-idea-value');
            var fStatus   = document.getElementById('ecf-idea-status');
            var fCategory = document.getElementById('ecf-idea-category');
            var fVersion  = document.getElementById('ecf-idea-version');
            var fEditId   = document.getElementById('ecf-idea-edit-id');
            var bSubmit   = document.getElementById('ecf-idea-submit');
            var bCancel   = document.getElementById('ecf-idea-cancel');
            var flash     = document.getElementById('ecf-idea-flash');

            var TITLE_NEW  = '<?php echo esc_js(__('Neue Idee anlegen', 'ecf-framework')); ?>';
            var TITLE_EDIT = '<?php echo esc_js(__('Idee bearbeiten', 'ecf-framework')); ?>';

            var LBL_NEW  = '<?php echo esc_js(__('Idee speichern', 'ecf-framework')); ?>';
            var LBL_EDIT = '<?php echo esc_js(__('Änderungen speichern', 'ecf-framework')); ?>';
            var MSG_CONFIRM = '<?php echo esc_js(__('Diese Idee wirklich löschen?', 'ecf-framework')); ?>';

            function showFlash(msg, ok) {
                if (!flash) return;
                flash.textContent = msg;
                flash.style.background = ok ? '#16331f' : '#33161a';
                flash.style.color      = ok ? '#9be0b0' : '#e09b9b';
                flash.style.display    = '';
                setTimeout(function() { flash.style.display = 'none'; }, 3000);
            }

            function resetForm() {
                fTitle.value    = '';
                fDesc.value     = '';
                fTime.value     = '';
                fRisk.value     = 'medium';
                fValue.value    = 'medium';
                fStatus.value   = 'idea';
                if (fCategory) fCategory.value = 'feature';
                if (fVersion) fVersion.value = '';
                fEditId.value   = '';
                bSubmit.textContent = LBL_NEW;
                if (formTitle) formTitle.textContent = TITLE_NEW;
                if (bCancel) bCancel.style.display = 'none';
            }
            function openForm(forNew) {
                if (forNew) resetForm();
                if (formWrap) formWrap.style.display = '';
                if (bCancel) bCancel.style.display = '';
                if (formWrap) formWrap.scrollIntoView({behavior: 'smooth', block: 'start'});
                fTitle.focus();
            }
            function closeForm() {
                resetForm();
                if (formWrap) formWrap.style.display = 'none';
            }

            function postSave() {
                var title = fTitle.value.trim();
                if (!title) { fTitle.focus(); return; }
                bSubmit.disabled = true;
                fetch(REST_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': REST_NONCE },
                    body: JSON.stringify({
                        title:    title,
                        desc:     fDesc.value,
                        time:     fTime.value,
                        version:  fVersion ? fVersion.value : '',
                        category: fCategory ? fCategory.value : 'feature',
                        risk:     fRisk.value,
                        value:    fValue.value,
                        status:   fStatus.value
                    })
                }).then(function(r) { return r.json(); })
                  .then(function(data) {
                      bSubmit.disabled = false;
                      if (data && data.success) {
                          showFlash('<?php echo esc_js(__('Eintrag gespeichert. Reload für Liste.', 'ecf-framework')); ?>', true);
                          resetForm();
                          setTimeout(function() { window.location.reload(); }, 800);
                      } else {
                          showFlash((data && data.message) || 'Fehler', false);
                      }
                  })
                  .catch(function(err) {
                      bSubmit.disabled = false;
                      showFlash('Netzwerk-Fehler: ' + err, false);
                  });
            }

            function postDelete(id) {
                if (!confirm(MSG_CONFIRM)) return;
                fetch(REST_URL + '/' + encodeURIComponent(id), {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': REST_NONCE }
                }).then(function(r) { return r.json(); })
                  .then(function(data) {
                      if (data && data.success) {
                          showFlash('<?php echo esc_js(__('Eintrag gelöscht.', 'ecf-framework')); ?>', true);
                          setTimeout(function() { window.location.reload(); }, 500);
                      } else {
                          showFlash('Löschen fehlgeschlagen', false);
                      }
                  })
                  .catch(function(err) { showFlash('Netzwerk-Fehler: ' + err, false); });
            }

            bSubmit.addEventListener('click', postSave);
            bCancel.addEventListener('click', closeForm);
            if (bNew) bNew.addEventListener('click', function() { openForm(true); });

            document.querySelectorAll('[data-ecf-idea-edit]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    try {
                        var note = JSON.parse(btn.getAttribute('data-ecf-idea-edit'));
                        fTitle.value    = note.title    || '';
                        fDesc.value     = note.desc     || '';
                        fTime.value     = note.time     || '';
                        fRisk.value     = note.risk     || 'medium';
                        fValue.value    = note.value    || 'medium';
                        fStatus.value   = note.status   || 'idea';
                        if (fCategory) fCategory.value = note.category || 'feature';
                        if (fVersion) fVersion.value = note.version || '';
                        fEditId.value   = note.id       || '';
                        bSubmit.textContent = LBL_EDIT;
                        if (formTitle) formTitle.textContent = TITLE_EDIT;
                        openForm(false);
                    } catch (e) {}
                });
            });

            document.querySelectorAll('[data-ecf-idea-delete]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    postDelete(btn.getAttribute('data-ecf-idea-delete'));
                });
            });

            // Category filter pills — hide cards/rows whose data-idea-category doesn't match
            document.querySelectorAll('[data-ecf-filter]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var f = btn.getAttribute('data-ecf-filter');
                    document.querySelectorAll('[data-ecf-filter]').forEach(function(b) { b.classList.remove('ecf-idea-filter--on'); });
                    btn.classList.add('ecf-idea-filter--on');
                    document.querySelectorAll('[data-idea-category]').forEach(function(el) {
                        el.style.display = (f === 'all' || el.getAttribute('data-idea-category') === f) ? '' : 'none';
                    });
                });
            });

            // Drag & drop reorder for table rows (only open ones — done/parked
            // are non-draggable via attribute). On drop, POST the new order
            // of OPEN ids to /owner-notes/reorder. Closed items keep their
            // server-side position; the open block's positions get rewritten.
            (function() {
                var table = document.querySelector('.ecf-idea-table tbody');
                if (!table) return;
                var dragSrc = null;
                table.addEventListener('dragstart', function(e) {
                    var row = e.target.closest('tr[draggable="true"]');
                    if (!row) { e.preventDefault(); return; }
                    dragSrc = row;
                    row.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    try { e.dataTransfer.setData('text/plain', row.getAttribute('data-idea-id') || ''); } catch (err) {}
                });
                table.addEventListener('dragover', function(e) {
                    if (!dragSrc) return;
                    var row = e.target.closest('tr[draggable="true"]');
                    if (!row || row === dragSrc) return;
                    e.preventDefault();
                    var rect = row.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    table.querySelectorAll('.is-drop-before, .is-drop-after').forEach(function(r) {
                        r.classList.remove('is-drop-before', 'is-drop-after');
                    });
                    row.classList.add(before ? 'is-drop-before' : 'is-drop-after');
                });
                table.addEventListener('dragleave', function(e) {
                    var row = e.target.closest('tr');
                    if (row) row.classList.remove('is-drop-before', 'is-drop-after');
                });
                table.addEventListener('drop', function(e) {
                    if (!dragSrc) return;
                    var target = e.target.closest('tr[draggable="true"]');
                    if (!target || target === dragSrc) { cleanup(); return; }
                    e.preventDefault();
                    var rect = target.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    if (before) target.parentNode.insertBefore(dragSrc, target);
                    else target.parentNode.insertBefore(dragSrc, target.nextSibling);
                    cleanup();
                    persistOrder();
                });
                table.addEventListener('dragend', cleanup);
                function cleanup() {
                    if (dragSrc) dragSrc.classList.remove('is-dragging');
                    table.querySelectorAll('.is-drop-before, .is-drop-after').forEach(function(r) {
                        r.classList.remove('is-drop-before', 'is-drop-after');
                    });
                    dragSrc = null;
                }
                function persistOrder() {
                    var ids = [];
                    table.querySelectorAll('tr[draggable="true"]').forEach(function(r) {
                        var id = r.getAttribute('data-idea-id');
                        if (id) ids.push(id);
                    });
                    fetch(REST_URL + '/reorder', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': REST_NONCE },
                        body: JSON.stringify({ ids: ids })
                    }).then(function(r) { return r.json(); })
                      .then(function(d) { if (d && d.success) showFlash('<?php echo esc_js(__('Reihenfolge gespeichert.', 'ecf-framework')); ?>', true); })
                      .catch(function(err) { showFlash('Reorder failed: ' + err, false); });
                }
            })();

            // View toggle — Table vs Cards (persisted via localStorage)
            var listEl = document.querySelector('.ecf-idea-list');
            var savedView = null;
            try { savedView = localStorage.getItem('ecf_idea_view'); } catch (e) {}
            if (listEl && savedView && (savedView === 'cards' || savedView === 'table')) {
                listEl.setAttribute('data-view', savedView);
                document.querySelectorAll('[data-ecf-view]').forEach(function(b) { b.classList.toggle('is-on', b.getAttribute('data-ecf-view') === savedView); });
            }
            document.querySelectorAll('[data-ecf-view]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var v = btn.getAttribute('data-ecf-view');
                    if (listEl) listEl.setAttribute('data-view', v);
                    document.querySelectorAll('[data-ecf-view]').forEach(function(b) { b.classList.toggle('is-on', b === btn); });
                    try { localStorage.setItem('ecf_idea_view', v); } catch (e) {}
                });
            });
        })();
        </script>
        <?php
    }
}
