<?php

trait ECF_Framework_Admin_Page_Sections_Trait {
    private function render_variables_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="variables">
            <?php if ($show_elementor_status_cards): ?>
                <div class="ecf-card ecf-class-limit-card ecf-class-limit-card--<?php echo esc_attr($elementor_variable_limit_status); ?>">
                    <div class="ecf-class-limit-card__eyebrow"><?php echo esc_html($this->t('Elementor Variables', 'Elementor Variablen')); ?></div>
                    <div class="ecf-class-limit-card__hero">
                        <div class="ecf-class-limit-card__headline">
                            <span class="ecf-class-limit-card__usage">
                                <span id="ecf-total-variables"><?php echo esc_html((string) $native_variable_counts['total']); ?></span>
                                <span class="ecf-class-limit-card__slash">/</span>
                                <span id="ecf-limit-variables"><?php echo esc_html((string) $elementor_variable_limit); ?></span>
                            </span>
                            <span><?php echo esc_html($this->t('variables currently found in Elementor', 'Variablen aktuell in Elementor gefunden')); ?></span>
                        </div>
                        <div class="ecf-class-limit-card__percent">
                            <strong><?php echo esc_html((string) round((($native_variable_counts['total'] ?? 0) / max(1, $elementor_variable_limit)) * 100)); ?>%</strong>
                            <span><?php echo esc_html($this->t('of current Elementor limit', 'des aktuell erkannten Elementor-Limits')); ?></span>
                        </div>
                    </div>
                    <ul class="ecf-class-limit-card__details ecf-class-limit-card__details--variables">
                        <li>
                            <span><?php echo esc_html($this->t('ECF', 'ECF')); ?></span>
                            <strong><span id="ecf-total-ecf-variables"><?php echo esc_html((string) $native_variable_counts['ecf']); ?></span></strong>
                        </li>
                        <li>
                            <span><?php echo esc_html($this->t('Foreign', 'Fremd')); ?></span>
                            <strong><span id="ecf-total-foreign-variables"><?php echo esc_html((string) $native_variable_counts['foreign']); ?></span></strong>
                        </li>
                        <li>
                            <span><?php echo esc_html($this->t('Total', 'Gesamt')); ?></span>
                            <strong><span id="ecf-total-variables-inline"><?php echo esc_html((string) $native_variable_counts['total']); ?></span></strong>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="ecf-card ecf-global-search-card">
                <div class="ecf-global-search">
                    <label class="ecf-global-search__field">
                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                        <input type="search" id="ecf-global-search-input" placeholder="<?php echo esc_attr($this->t('Search variables…', 'Variablen durchsuchen…')); ?>" autocomplete="off">
                    </label>
                    <div class="ecf-global-search__results" id="ecf-global-search-results" hidden></div>
                </div>
            </div>
            <div class="ecf-modal" data-ecf-search-edit-modal hidden>
                <div class="ecf-modal__backdrop" data-ecf-search-edit-close></div>
                <div class="ecf-modal__dialog ecf-search-edit-modal" role="dialog" aria-modal="true" aria-labelledby="ecf-search-edit-title">
                    <div class="ecf-modal__header">
                        <div>
                            <h2 id="ecf-search-edit-title"><?php echo esc_html($this->t('Edit variable', 'Variable bearbeiten')); ?></h2>
                            <p data-ecf-search-edit-subtitle><?php echo esc_html($this->t('Adjust foreign Elementor variables directly from the search results.', 'Passe fremde Elementor-Variablen direkt aus der Suche an.')); ?></p>
                        </div>
                        <button type="button" class="ecf-modal__close" data-ecf-search-edit-close aria-label="<?php echo esc_attr($this->t('Close', 'Schließen')); ?>">×</button>
                    </div>
                    <div class="ecf-modal__body">
                        <div class="ecf-search-edit-note" data-ecf-search-edit-note hidden></div>
                        <div class="ecf-search-edit-tech" data-ecf-search-edit-tech hidden></div>
                        <input type="hidden" data-ecf-search-edit-id>
                        <div class="ecf-form-grid ecf-form-grid--two">
                            <label>
                                <span><?php echo esc_html($this->t('Variable name', 'Variablenname')); ?></span>
                                <input type="text" data-ecf-search-edit-label>
                            </label>
                            <label>
                                <span><?php echo $this->tip_hover_label($this->t('Type', 'Typ'), 'Choose Color for color values, Size for lengths like px/rem/clamp(...), and Text only for real text strings.', 'Wähle Farbe für Farbwerte, Größe für Längen wie px/rem/clamp(...), und Text nur für echte Text-Strings.'); ?></span>
                                <select data-ecf-search-edit-type>
                                    <option value="global-color-variable"><?php echo esc_html($this->t('Color', 'Farbe')); ?></option>
                                    <option value="global-size-variable"><?php echo esc_html($this->t('Size', 'Größe')); ?></option>
                                    <option value="global-string-variable"><?php echo esc_html($this->t('Text', 'Text')); ?></option>
                                </select>
                                <small class="ecf-search-edit-help" data-ecf-search-edit-type-help></small>
                            </label>
                            <label class="ecf-search-edit-color" data-ecf-search-edit-color-row>
                                <span><?php echo esc_html($this->t('Color', 'Farbe')); ?></span>
                                <input type="color" data-ecf-search-edit-color value="#3b82f6">
                            </label>
                            <label class="ecf-search-edit-value">
                                <span><?php echo $this->tip_hover_label($this->t('Value', 'Wert'), 'For Size, enter a simple number plus format like 24 + px. If the variable uses clamp(...), edit the Minimum and Maximum px values below instead.', 'Für Größe gibst du einen einfachen Zahlenwert plus Format wie 24 + px ein. Wenn die Variable clamp(...) nutzt, bearbeite stattdessen unten Minimum und Maximum in px.'); ?></span>
                                <div class="ecf-search-edit-clamp-fields" data-ecf-search-edit-clamp-fields hidden>
                                    <label>
                                        <span><?php echo $this->tip_hover_label($this->t('Minimum (px)', 'Minimum (px)'), 'Smallest size of the clamp value, shown here in px for easier editing.', 'Kleinste Größe des clamp-Werts, hier zur einfacheren Bearbeitung in px dargestellt.'); ?></span>
                                        <input type="number" step="0.01" data-ecf-search-edit-clamp-min>
                                    </label>
                                    <label>
                                        <span><?php echo $this->tip_hover_label($this->t('Maximum (px)', 'Maximum (px)'), 'Largest size of the clamp value, shown here in px for easier editing.', 'Größte Größe des clamp-Werts, hier zur einfacheren Bearbeitung in px dargestellt.'); ?></span>
                                        <input type="number" step="0.01" data-ecf-search-edit-clamp-max>
                                    </label>
                                </div>
                                <div class="ecf-search-edit-value-fields">
                                    <input type="text" data-ecf-search-edit-value>
                                    <select data-ecf-search-edit-format hidden>
                                        <option value="px">px</option>
                                        <option value="rem">rem</option>
                                        <option value="em">em</option>
                                        <option value="ch">ch</option>
                                        <option value="%">%</option>
                                        <option value="vw">vw</option>
                                        <option value="vh">vh</option>
                                        <option value="fx">f(x)</option>
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="ecf-modal__footer">
                        <button type="button" class="ecf-btn ecf-btn--ghost" data-ecf-search-edit-close><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span><?php echo esc_html($this->t('Cancel', 'Abbrechen')); ?></span></button>
                        <button type="button" class="ecf-btn ecf-btn--primary" data-ecf-search-edit-save><span class="dashicons dashicons-saved" aria-hidden="true"></span><span><?php echo esc_html($this->t('Save', 'Speichern')); ?></span></button>
                    </div>
                </div>
            </div>
            <div class="ecf-grid">
                <div class="ecf-card" id="ecf-vars-ecf">
                    <div class="ecf-vargroup-header">
                        <h2><?php echo esc_html($this->t('ECF Variables', 'ECF Variablen')); ?> <span class="ecf-badge" id="ecf-badge-ecf">–</span></h2>
                        <div class="ecf-vargroup-tools">
                            <div class="ecf-vargroup-actions">
                                <button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-select-all" data-group="ecf">
                                    <span class="ecf-select-all__icon" aria-hidden="true"></span>
                                    <span><?php echo esc_html($this->t('Select all', 'Alle auswählen')); ?></span>
                                </button>
                                <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--sm ecf-delete-selected" data-group="ecf" aria-label="<?php echo esc_attr($this->t('Delete selected', 'Auswahl löschen')); ?>" title="<?php echo esc_attr($this->t('Delete selected', 'Auswahl löschen')); ?>">
                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="ecf-varlist-ecf" class="ecf-varlist"><p class="ecf-loading"><?php echo esc_html($this->t('Loading…', 'Lade…')); ?></p></div>
                </div>
                <div class="ecf-card" id="ecf-vars-foreign">
                    <div class="ecf-vargroup-header">
                        <h2><?php echo esc_html($this->t('Foreign Variables', 'Fremde Variablen')); ?> <span class="ecf-badge" id="ecf-badge-foreign">–</span></h2>
                        <div class="ecf-vargroup-tools">
                            <div class="ecf-vargroup-actions">
                                <button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-select-all" data-group="foreign">
                                    <span class="ecf-select-all__icon" aria-hidden="true"></span>
                                    <span><?php echo esc_html($this->t('Select all', 'Alle auswählen')); ?></span>
                                </button>
                                <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--sm ecf-delete-selected" data-group="foreign" aria-label="<?php echo esc_attr($this->t('Delete selected', 'Auswahl löschen')); ?>" title="<?php echo esc_attr($this->t('Delete selected', 'Auswahl löschen')); ?>">
                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="ecf-varlist-foreign" class="ecf-varlist"><p class="ecf-loading"><?php echo esc_html($this->t('Loading…', 'Lade…')); ?></p></div>
                </div>
            </div>
            <p style="color:#6b7280;font-size:12px;margin-top:12px;"><?php echo esc_html($this->t('Changes take effect immediately in Elementor. The cache is cleared automatically; open Elementor tabs should be reloaded once.', 'Änderungen werden sofort in Elementor wirksam. Der Cache wird automatisch geleert; offene Elementor-Tabs bitte einmal neu laden.')); ?></p>
        </div>
        <?php
    }

    private function render_tokens_panel($settings) {
        ?>
        <div class="ecf-panel" data-panel="tokens">
            <div class="ecf-grid">
                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Colors', 'Farben')); ?></h2>
                    <?php $this->render_rows('colors', $settings['colors']); ?>
                </div>
                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Radius', 'Radius')); ?></h2>
                    <?php $this->render_root_font_size_select($settings, false); ?>
                    <?php $this->render_rows('radius', $settings['radius']); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_shadows_panel($settings) {
        ?>
        <div class="ecf-panel" data-panel="shadows">
            <div class="ecf-shadow-layout">
                <div class="ecf-shadow-sidebar">
                    <div class="ecf-card">
                        <h2><?php echo esc_html($this->t('Box Shadow - Vars', 'Box Shadow - Vars')); ?></h2>
                        <p style="color:#6b7280;font-size:13px;margin:0 0 12px"><?php echo wp_kses($this->t('Values in CSS box-shadow syntax, e.g. <code>0 4px 16px rgba(0,0,0,0.1)</code>.', 'Werte als CSS box-shadow-Syntax, z.B. <code>0 4px 16px rgba(0,0,0,0.1)</code>.'), ['code' => []]); ?></p>
                        <?php $this->render_rows('shadows', $settings['shadows']); ?>
                    </div>
                </div>
                <div class="ecf-card ecf-shadow-preview-card"
                     data-ecf-shadow-preview
                     data-active-shadow="<?php echo esc_attr(sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?>"
                     data-preview-word="<?php echo esc_attr($this->t('Shadow', 'Schatten')); ?>"
                     data-preview-helper="<?php echo esc_attr($this->t('Click a shadow token to inspect it in detail.', 'Klicke auf einen Schatten-Token, um ihn im Detail zu prüfen.')); ?>">
                    <div class="ecf-shadow-preview-header">
                        <div>
                            <h2><?php echo esc_html($this->t('Live Box Shadow Preview', 'Live Box Shadow Vorschau')); ?></h2>
                            <p><?php echo esc_html($this->t('Preview of your shadow tokens.', 'Vorschau deiner Schatten-Tokens.')); ?></p>
                        </div>
                    </div>
                    <div class="ecf-shadow-focus" data-ecf-shadow-focus>
                        <div class="ecf-shadow-focus__meta">
                            <span class="ecf-preview-pill"><?php echo esc_html($this->t('Preview', 'Vorschau')); ?></span>
                            <strong data-ecf-shadow-token><?php echo esc_html('--ecf-shadow-' . sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?></strong>
                            <p data-ecf-shadow-helper><?php echo esc_html($this->t('Klicke auf einen Schatten-Token, um ihn im Detail zu prüfen.', 'Klicke auf einen Schatten-Token, um ihn im Detail zu prüfen.')); ?></p>
                        </div>
                        <div class="ecf-shadow-focus__sample">
                            <div class="ecf-shadow-focus__surface" data-ecf-shadow-surface style="box-shadow:<?php echo esc_attr($settings['shadows'][0]['value'] ?? '0 1px 2px rgba(0,0,0,0.05)'); ?>;">
                                <span class="ecf-shadow-preview-label" data-ecf-shadow-label><?php echo esc_html('--ecf-shadow-' . sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?></span>
                                <strong data-ecf-shadow-name><?php echo esc_html(ucfirst(sanitize_key($settings['shadows'][0]['name'] ?? 'xs'))); ?></strong>
                                <small data-ecf-shadow-css><?php echo esc_html($settings['shadows'][0]['value'] ?? '0 1px 2px rgba(0,0,0,0.05)'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="ecf-shadow-preview-list" data-ecf-shadow-preview-list>
                        <?php foreach ($settings['shadows'] as $index => $row): ?>
                            <?php $shadow_name = sanitize_key($row['name']); ?>
                            <button type="button" class="ecf-shadow-row<?php echo $index === 0 ? ' is-active' : ''; ?>" data-ecf-shadow-step="<?php echo esc_attr($shadow_name); ?>">
                                <div class="ecf-shadow-row__token"><?php echo esc_html('--ecf-shadow-' . $shadow_name); ?></div>
                                <div class="ecf-shadow-row__value"><code><?php echo esc_html($row['value']); ?></code></div>
                                <div class="ecf-shadow-row__sample">
                                    <div class="ecf-shadow-row__mini" style="box-shadow:<?php echo esc_attr($row['value']); ?>;"></div>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_utilities_panel($args) {
        extract($args, EXTR_SKIP);
        $class_usage_percent = $elementor_class_limit > 0 ? (int) round(($elementor_total_class_count / $elementor_class_limit) * 100) : 0;
        $class_usage_percent = max(0, min(100, $class_usage_percent));
        $starter_tab_icons = [
            'all' => 'dashicons-screenoptions',
            'website_sections' => 'dashicons-admin-site-alt3',
            'layout_content' => 'dashicons-layout',
            'interaction' => 'dashicons-button',
            'custom' => 'dashicons-edit',
        ];
        $utility_tab_icons = [
            'all' => 'dashicons-screenoptions',
            'typography' => 'dashicons-editor-textcolor',
            'text' => 'dashicons-editor-paragraph',
            'layout' => 'dashicons-layout',
            'accessibility' => 'dashicons-universal-access',
        ];
        $starter_library_features = [
            $this->t('Starter classes for common page elements like header, hero, buttons or footer.', 'Starter-Klassen für gängige Seitenelemente wie Header, Hero, Buttons oder Footer.'),
            $this->t('Consistent, semantic naming instead of improvised labels per page.', 'Einheitliches, semantisches Benennungssystem statt spontaner Namen pro Seite.'),
            $this->t('Keeps naming and Elementor sync manageable while staying below the 100-class limit.', 'Hält Benennung und Elementor-Sync überschaubar und berücksichtigt das 100er-Klassenlimit.'),
        ];
        $utility_library_features = [
            $this->t('Curated helper styles for headings, text and a few safe layout patterns.', 'Kuratiertes Set an Helfer-Stilen für Heading-, Text- und einige sichere Layout-Muster.'),
            $this->t('Useful when you want a small reusable utility layer without reviving the old full utility flood.', 'Sinnvoll, wenn du eine kleine wiederverwendbare Utility-Ebene willst, ohne das alte Vollsystem zurückzubringen.'),
            $this->t('Utilities also count toward Elementor’s 100 global classes and should stay intentionally compact.', 'Utilities zählen ebenfalls in Elementors 100 globale Klassen hinein und sollten deshalb bewusst kompakt bleiben.'),
        ];
        $bem_generator_presets = [
            'header' => [
                'label' => $this->t('Header', 'Header'),
                'category' => 'navigation',
                'help' => $this->t('For brand, navigation and actions in the top area.', 'Für Brand, Navigation und Actions im oberen Bereich.'),
                'elements' => ['inner', 'brand', 'nav', 'actions'],
                'modifiers' => ['sticky', 'dark', 'transparent'],
            ],
            'hero' => [
                'label' => $this->t('Hero', 'Hero'),
                'category' => 'hero',
                'help' => $this->t('For the main intro section with copy, media and CTAs.', 'Für den zentralen Einstiegsbereich mit Text, Media und CTAs.'),
                'elements' => ['content', 'eyebrow', 'title', 'text', 'media', 'actions'],
                'modifiers' => ['dark', 'accent', 'split'],
            ],
            'content' => [
                'label' => $this->t('Content', 'Content'),
                'category' => 'content',
                'help' => $this->t('For normal content blocks like text, media, lists or side content.', 'Für normale Inhaltsblöcke wie Text, Media, Listen oder Nebeninhalte.'),
                'elements' => ['title', 'text', 'media', 'meta', 'list', 'item', 'actions'],
                'modifiers' => ['highlight', 'compact', 'wide'],
            ],
            'section' => [
                'label' => $this->t('Section', 'Section'),
                'category' => 'sections',
                'help' => $this->t('For larger page sections and themed wrappers.', 'Für größere Seitenabschnitte und thematische Wrapper.'),
                'elements' => ['inner', 'header', 'body', 'footer'],
                'modifiers' => ['dark', 'accent', 'spacious'],
            ],
            'card' => [
                'label' => $this->t('Card', 'Card'),
                'category' => 'cards',
                'help' => $this->t('For grouped content surfaces like cards, teasers or tiles.', 'Für gruppierte Inhaltsflächen wie Cards, Teaser oder Kacheln.'),
                'elements' => ['media', 'body', 'title', 'text', 'meta', 'actions'],
                'modifiers' => ['featured', 'compact', 'outlined'],
            ],
            'button' => [
                'label' => $this->t('Button', 'Button'),
                'category' => 'buttons',
                'help' => $this->t('For CTA buttons with icons, labels and variants.', 'Für CTA-Buttons mit Icons, Labels und Varianten.'),
                'elements' => ['icon', 'label'],
                'modifiers' => ['primary', 'secondary', 'ghost', 'large'],
            ],
            'form' => [
                'label' => $this->t('Form', 'Formular'),
                'category' => 'forms',
                'help' => $this->t('For forms, groups, fields and actions.', 'Für Formulare, Gruppen, Felder und Actions.'),
                'elements' => ['group', 'field', 'label', 'hint', 'actions'],
                'modifiers' => ['inline', 'compact', 'stacked'],
            ],
            'footer' => [
                'label' => $this->t('Footer', 'Footer'),
                'category' => 'footer',
                'help' => $this->t('For the lower website area with columns, links and meta info.', 'Für den unteren Website-Bereich mit Spalten, Links und Meta-Infos.'),
                'elements' => ['inner', 'brand', 'nav', 'meta', 'actions'],
                'modifiers' => ['dark', 'minimal', 'split'],
            ],
            'custom' => [
                'label' => $this->t('Custom section', 'Eigener Abschnitt'),
                'category' => 'custom',
                'help' => $this->t('Use your own block name and build a small BEM family around it.', 'Nutze deinen eigenen Blocknamen und baue darum eine kleine BEM-Familie auf.'),
                'elements' => ['title', 'text', 'media', 'actions'],
                'modifiers' => ['primary', 'secondary', 'dark'],
            ],
        ];
        $starter_library_tooltip = '• ' . implode("\n• ", $starter_library_features);
        $utility_library_tooltip = '• ' . implode("\n• ", $utility_library_features);
        $custom_class_suggestions = [
            $this->t('Marketing', 'Marketing') => ['banner', 'cta', 'promo', 'offer'],
            $this->t('Content', 'Inhalt') => ['teaser', 'feature', 'highlight', 'story'],
            $this->t('Trust', 'Vertrauen') => ['testimonial', 'review', 'logos', 'proof'],
            $this->t('Commerce', 'Commerce') => ['pricing', 'plan', 'faq', 'contact'],
        ];
        ?>
        <div class="ecf-panel" data-panel="utilities">
            <div class="ecf-grid">
                <div class="ecf-card ecf-starter-classes"
                     data-ecf-starter-classes
                    data-ecf-class-current="<?php echo esc_attr((string) $elementor_total_class_count); ?>"
                     data-ecf-class-limit="<?php echo esc_attr((string) $elementor_class_limit); ?>"
                     data-ecf-existing-labels="<?php echo esc_attr(wp_json_encode($elementor_existing_class_labels)); ?>">
                    <div class="ecf-vargroup-header">
                        <h2><?php echo esc_html($this->t('Class library', 'Klassenbibliothek')); ?></h2>
                    </div>
                    <p class="ecf-muted-copy"><?php echo esc_html($this->t('Use starter classes for semantic naming and utility classes for a compact curated helper set. Both count toward Elementor’s 100-class limit.', 'Nutze Starter-Klassen für semantische Benennung und Utility-Klassen für ein kompaktes kuratiertes Helfer-Set. Beides zählt in Elementors 100er-Klassenlimit.')); ?></p>
                    <div class="ecf-class-limit-card ecf-class-limit-card--<?php echo esc_attr($elementor_class_limit_status); ?> ecf-starter-classes__status" data-ecf-starter-status>
                        <div class="ecf-class-limit-card__eyebrow"><?php echo esc_html($this->t('Class usage overview', 'Klassen-Übersicht')); ?></div>
                        <div class="ecf-class-limit-card__hero">
                            <div class="ecf-class-limit-card__headline">
                                <span class="ecf-class-limit-card__usage">
                                    <span data-ecf-starter-projected><?php echo esc_html((string) $elementor_total_class_count); ?></span>
                                    <span><?php echo esc_html($this->t('of', 'von')); ?></span>
                                    <span data-ecf-starter-limit><?php echo esc_html((string) $elementor_class_limit); ?></span>
                                </span>
                                <span><?php echo esc_html($this->t('classes used', 'Klassen verwendet')); ?></span>
                            </div>
                            <div class="ecf-class-limit-card__percent">
                                <strong data-ecf-starter-percent><?php echo esc_html((string) $class_usage_percent); ?></strong>
                                <span><?php echo esc_html($this->t('% of limit', '% zu Limit')); ?></span>
                            </div>
                        </div>
                        <div class="ecf-class-limit-card__progress" aria-hidden="true">
                            <span data-ecf-starter-progress style="width:<?php echo esc_attr((string) $class_usage_percent); ?>%"></span>
                        </div>
                        <ul class="ecf-class-limit-card__details">
                            <li>
                                <span><?php echo esc_html($this->t('Elementor', 'Elementor')); ?></span>
                                <strong><span data-ecf-starter-current><?php echo esc_html((string) $elementor_total_class_count); ?></span> <?php echo esc_html($this->t('classes', 'Klassen')); ?></strong>
                            </li>
                            <li>
                                <span><?php echo esc_html($this->t('Plugin', 'Plugin')); ?></span>
                                <strong><span data-ecf-starter-selected>0</span> <?php echo esc_html($this->t('classes', 'Klassen')); ?></strong>
                            </li>
                            <li>
                                <span><?php echo esc_html($this->t('After sync:', 'Nach Sync:')); ?></span>
                                <strong><span data-ecf-starter-projected-inline><?php echo esc_html((string) $elementor_total_class_count); ?></span> / <?php echo esc_html((string) $elementor_class_limit); ?></strong>
                            </li>
                        </ul>
                    </div>
                    <div class="ecf-var-tabs ecf-class-tier-tabs" data-ecf-class-tier-tabs>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-class-tier="all">
                            <?php echo esc_html($this->t('All', 'Alle')); ?>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="basic">
                            <?php echo esc_html($this->t('Basic', 'Basic')); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-basic-count>0</span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="extras">
                            <?php echo esc_html($this->t('Extras', 'Extras')); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-extras-count>0</span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="custom">
                            <?php echo esc_html($this->t('Custom', 'Custom')); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-custom-count>0</span>
                        </button>
                    </div>
                    <div class="ecf-var-tabs ecf-library-tabs" data-ecf-library-tabs>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-library-tab="starter" data-ecf-help="<?php echo esc_attr($class_library_help_texts['starter']); ?>" title="<?php echo esc_attr($starter_library_tooltip); ?>"><?php echo esc_html($this->t('Advanced classes', 'Advanced-Klassen')); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-library-tab="utility" data-ecf-help="<?php echo esc_attr($class_library_help_texts['utility']); ?>" title="<?php echo esc_attr($utility_library_tooltip); ?>"><?php echo esc_html($this->t('Utility classes', 'Utility-Klassen')); ?></button>
                    </div>
                    <p class="ecf-tab-help" data-ecf-library-help><?php echo esc_html($class_library_help_texts['starter']); ?></p>
                    <?php wp_nonce_field('ecf_class_library_sync', '_ecf_class_library_sync_nonce'); ?>
                    <div class="ecf-library-section" data-ecf-library-section="starter">
                    <div class="ecf-class-filterbar" data-ecf-starter-filterbar>
                        <label class="ecf-class-filterbar__field">
                            <span class="ecf-class-filterbar__label"><?php echo esc_html($this->t('Bereich', 'Bereich')); ?></span>
                            <select data-ecf-starter-select title="<?php echo esc_attr($this->t('Filter the starter classes by area.', 'Filtere die Starter-Klassen nach Bereich.')); ?>">
                                <?php foreach ($starter_class_tabs as $tab_key => $tab): ?>
                                    <option value="<?php echo esc_attr($tab_key); ?>" <?php selected($tab_key, 'all'); ?>><?php echo esc_html($tab['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="ecf-class-library-actions">
                        <button type="button" class="ecf-btn ecf-btn--secondary ecf-class-select-all" data-ecf-class-select-all>
                            <span class="ecf-select-all__icon" data-ecf-class-select-all-icon aria-hidden="true"></span>
                            <span data-ecf-class-select-all-label><?php echo esc_html($this->t('Select all', 'Alle wählen')); ?></span>
                        </button>
                        <button type="button" class="ecf-btn ecf-btn--primary" data-ecf-class-sync-button data-ecf-class-sync-url="<?php echo esc_url(admin_url('admin-post.php?action=ecf_class_library_sync')); ?>">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <span><?php echo esc_html($this->t('Sync with Elementor', 'Mit Elementor synchronisieren')); ?></span>
                        </button>
                    </div>
                    <p class="ecf-class-library-actions__hint"><?php echo esc_html($this->t('Start the sync to apply the currently selected classes to Elementor.', 'Starte die Synchronisation, um die aktuell ausgewählten Klassen nach Elementor zu übernehmen.')); ?></p>
                    <div class="ecf-global-search ecf-class-search-card">
                        <label class="ecf-global-search__field">
                            <span class="dashicons dashicons-search" aria-hidden="true"></span>
                            <input type="search" data-ecf-class-search placeholder="<?php echo esc_attr($this->t('Search classes…', 'Klassen durchsuchen…')); ?>" autocomplete="off">
                        </label>
                    </div>
                    <div class="ecf-bem-generator" data-ecf-bem-generator data-ecf-bem-presets="<?php echo esc_attr(wp_json_encode($bem_generator_presets)); ?>">
                        <div class="ecf-vargroup-header">
                            <h3><?php echo esc_html($this->t('BEM class generator', 'BEM-Klassengenerator')); ?></h3>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('Choose a section, add elements or modifiers, and generate clean ECF BEM names for your own classes.', 'Wähle einen Bereich, ergänze Elemente oder Modifier und erzeuge saubere ECF-BEM-Namen für eigene Klassen.')); ?></p>
                        <div class="ecf-bem-generator__grid">
                            <label class="ecf-class-filterbar__field">
                                <span class="ecf-class-filterbar__label"><?php echo esc_html($this->t('Area', 'Bereich')); ?></span>
                                <select data-ecf-bem-preset>
                                    <?php foreach ($bem_generator_presets as $preset_key => $preset): ?>
                                        <option value="<?php echo esc_attr($preset_key); ?>"><?php echo esc_html($preset['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-class-filterbar__field" data-ecf-bem-block-field>
                                <span class="ecf-class-filterbar__label"><?php echo esc_html($this->t('Own block name', 'Eigener Blockname')); ?></span>
                                <input type="text" class="ecf-input" data-ecf-bem-block placeholder="<?php echo esc_attr($this->t('optional, e.g. testimonials', 'optional, z.B. testimonials')); ?>">
                            </label>
                            <label class="ecf-class-filterbar__field">
                                <span class="ecf-class-filterbar__label"><?php echo esc_html($this->t('Additional elements', 'Weitere Elemente')); ?></span>
                                <input type="text" class="ecf-input" data-ecf-bem-extra-elements placeholder="<?php echo esc_attr($this->t('e.g. subtitle, badge', 'z.B. subtitle, badge')); ?>">
                            </label>
                            <label class="ecf-class-filterbar__field">
                                <span class="ecf-class-filterbar__label"><?php echo esc_html($this->t('Additional modifiers', 'Weitere Modifier')); ?></span>
                                <input type="text" class="ecf-input" data-ecf-bem-extra-modifiers placeholder="<?php echo esc_attr($this->t('e.g. dark, compact', 'z.B. dark, compact')); ?>">
                            </label>
                        </div>
                        <p class="ecf-class-library-actions__hint" data-ecf-bem-help></p>
                        <div class="ecf-bem-generator__pickers">
                            <div class="ecf-bem-generator__picker">
                                <strong><?php echo esc_html($this->t('Elements', 'Elemente')); ?></strong>
                                <div class="ecf-bem-generator__options" data-ecf-bem-elements></div>
                            </div>
                            <div class="ecf-bem-generator__picker">
                                <strong><?php echo esc_html($this->t('Modifiers', 'Modifier')); ?></strong>
                                <div class="ecf-bem-generator__options" data-ecf-bem-modifiers></div>
                            </div>
                        </div>
                        <div class="ecf-bem-generator__preview">
                            <strong><?php echo esc_html($this->t('Preview', 'Vorschau')); ?></strong>
                            <div class="ecf-bem-generator__preview-list" data-ecf-bem-preview></div>
                        </div>
                        <div class="ecf-class-library-actions ecf-class-library-actions--generator">
                            <button type="button" class="ecf-btn ecf-btn--secondary" data-ecf-bem-reset>
                                <span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
                                <span><?php echo esc_html($this->t('Reset', 'Zurücksetzen')); ?></span>
                            </button>
                            <button type="button" class="ecf-btn ecf-btn--primary" data-ecf-bem-add>
                                <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                                <span><?php echo esc_html($this->t('Add as custom classes', 'Als eigene Klassen hinzufügen')); ?></span>
                            </button>
                        </div>
                        <p class="ecf-class-library-actions__hint" data-ecf-bem-feedback></p>
                    </div>
                    <div class="ecf-starter-class-list">
                        <?php foreach ($starter_class_library as $tier => $classes): ?>
                            <?php foreach ($classes as $class): ?>
                                <?php $class_name = $class['name']; ?>
                                <?php $class_tab = $this->starter_class_tab_for_category($class['category']); ?>
                                <label class="ecf-starter-class-item"
                                       data-ecf-starter-item
                                       data-tier="<?php echo esc_attr($tier); ?>"
                                       data-category="<?php echo esc_attr($class['category']); ?>"
                                       data-tabgroup="<?php echo esc_attr($class_tab); ?>"
                                       data-class-name="<?php echo esc_attr($class_name); ?>"
                                       title="<?php echo esc_attr($this->starter_class_tooltip($class_name, $class['category'], $tier)); ?>">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr($this->option_name); ?>[starter_classes][enabled][<?php echo esc_attr($class_name); ?>]"
                                           value="1"
                                           class="ecf-starter-class-toggle"
                                           <?php checked(!empty($settings['starter_classes']['enabled'][$class_name])); ?>>
                                    <span class="ecf-starter-class-item__badge ecf-starter-class-item__badge--<?php echo esc_attr($tier); ?>"><?php echo esc_html(ucfirst($tier)); ?></span>
                                    <span class="ecf-starter-class-item__name"><?php echo esc_html($class_name); ?></span>
                                    <span class="ecf-starter-class-item__meta"><?php echo esc_html($starter_class_categories[$class['category']] ?? ucfirst($class['category'])); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-starter-custom" data-ecf-starter-custom-section>
                        <div class="ecf-vargroup-header">
                            <h3><?php echo esc_html($this->t('Custom classes', 'Eigene Klassen')); ?></h3>
                        </div>
                        <div class="ecf-custom-suggestions" data-ecf-custom-suggestions>
                            <p class="ecf-class-library-actions__hint"><?php echo esc_html($this->t('Suggestions for quick, clean custom names. Click a chip to insert it into a free row.', 'Vorschläge für schnelle, saubere eigene Namen. Klick auf einen Chip, um ihn in eine freie Zeile zu setzen.')); ?></p>
                            <?php foreach ($custom_class_suggestions as $suggestion_group => $suggestions): ?>
                                <div class="ecf-custom-suggestions__group">
                                    <strong><?php echo esc_html($suggestion_group); ?></strong>
                                    <div class="ecf-custom-suggestions__chips">
                                        <?php foreach ($suggestions as $suggestion): ?>
                                            <button type="button" class="ecf-custom-suggestion-chip" data-ecf-custom-suggestion="<?php echo esc_attr('ecf-' . $suggestion); ?>">
                                                <?php echo esc_html('ecf-' . $suggestion); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="ecf-starter-custom-rows" data-ecf-starter-custom-rows>
                            <?php foreach (($settings['starter_classes']['custom'] ?? []) as $index => $row): ?>
                                <div class="ecf-starter-custom-row">
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[starter_classes][custom][<?php echo esc_attr((string) $index); ?>][enabled]" value="1" class="ecf-custom-starter-enabled" <?php checked(!empty($row['enabled'])); ?>>
                                        <span><?php echo esc_html($this->t('Active', 'Aktiv')); ?></span>
                                    </label>
                                    <input type="text" name="<?php echo esc_attr($this->option_name); ?>[starter_classes][custom][<?php echo esc_attr((string) $index); ?>][name]" value="<?php echo esc_attr($row['name'] ?? ''); ?>" placeholder="ecf-banner" class="ecf-custom-starter-name">
                                    <select name="<?php echo esc_attr($this->option_name); ?>[starter_classes][custom][<?php echo esc_attr((string) $index); ?>][category]" class="ecf-custom-starter-category">
                                        <?php foreach ($starter_class_categories as $category_key => $category_label): ?>
                                            <?php if ($category_key === 'all') continue; ?>
                                            <option value="<?php echo esc_attr($category_key); ?>" <?php selected(($row['category'] ?? 'custom'), $category_key); ?>><?php echo esc_html($category_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="ecf-row-controls ecf-row-controls--bottom">
                            <button type="button" class="ecf-step-btn" data-ecf-starter-custom-add title="<?php echo esc_attr($this->t('Add', 'Hinzufügen')); ?>">+</button>
                            <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-starter-custom-remove title="<?php echo esc_attr($this->t('Remove last', 'Letzten entfernen')); ?>">−</button>
                        </div>
                    </div>
                    </div>
                    <div class="ecf-library-section" data-ecf-library-section="utility" hidden>
                        <div class="ecf-vargroup-header">
                            <h3><?php echo esc_html($this->t('Curated utility set', 'Kuratiertes Utility-Set')); ?></h3>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('Small optional helpers for text styles, alignment, and a few safe layout utilities. They are intentionally limited so the class system stays manageable.', 'Kleine optionale Helfer für Textstile, Ausrichtung und einige sichere Layout-Utilities. Sie sind bewusst begrenzt, damit das Klassensystem überschaubar bleibt.')); ?></p>
                        <div class="ecf-var-tabs ecf-starter-class-tabs" data-ecf-utility-tabs>
                            <?php foreach ($utility_class_categories as $category_key => $category_label): ?>
                                <button type="button" class="ecf-var-tab<?php echo $category_key === 'all' ? ' is-active' : ''; ?>" data-ecf-utility-tab="<?php echo esc_attr($category_key); ?>" data-ecf-help="<?php echo esc_attr($utility_class_help_texts[$category_key] ?? ''); ?>" title="<?php echo esc_attr($utility_class_help_texts[$category_key] ?? ''); ?>">
                                    <span class="dashicons <?php echo esc_attr($utility_tab_icons[$category_key] ?? 'dashicons-category'); ?>" aria-hidden="true"></span>
                                    <?php echo esc_html($category_label); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="ecf-tab-help" data-ecf-category-help="utility"><?php echo esc_html($utility_class_help_texts['all']); ?></p>
                        <div class="ecf-class-library-actions">
                            <button type="button" class="ecf-btn ecf-btn--secondary ecf-class-select-all" data-ecf-class-select-all>
                                <span class="ecf-select-all__icon" data-ecf-class-select-all-icon aria-hidden="true"></span>
                                <span data-ecf-class-select-all-label><?php echo esc_html($this->t('Select all', 'Alle wählen')); ?></span>
                            </button>
                            <button type="button" class="ecf-btn ecf-btn--primary" data-ecf-class-sync-button data-ecf-class-sync-url="<?php echo esc_url(admin_url('admin-post.php?action=ecf_class_library_sync')); ?>">
                                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                <span><?php echo esc_html($this->t('Sync with Elementor', 'Mit Elementor synchronisieren')); ?></span>
                            </button>
                        </div>
                        <p class="ecf-class-library-actions__hint"><?php echo esc_html($this->t('Sync only the utility classes that are currently enabled here.', 'Synchronisiere nur die Utility-Klassen, die hier aktuell aktiviert sind.')); ?></p>
                        <div class="ecf-global-search ecf-class-search-card">
                            <label class="ecf-global-search__field">
                                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                                <input type="search" data-ecf-class-search placeholder="<?php echo esc_attr($this->t('Search classes…', 'Klassen durchsuchen…')); ?>" autocomplete="off">
                            </label>
                        </div>
                        <div class="ecf-starter-class-list">
                            <?php foreach ($utility_class_library as $category_key => $classes): ?>
                                <?php foreach ($classes as $class): ?>
                                    <?php $class_name = $class['name']; ?>
                                    <label class="ecf-starter-class-item ecf-utility-class-item"
                                           data-ecf-utility-item
                                           data-category="<?php echo esc_attr($category_key); ?>"
                                           data-class-name="<?php echo esc_attr($class_name); ?>"
                                           title="<?php echo esc_attr($this->utility_class_tooltip($class_name, $category_key)); ?>">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr($this->option_name); ?>[utility_classes][enabled][<?php echo esc_attr($class_name); ?>]"
                                               value="1"
                                               class="ecf-utility-class-toggle"
                                               <?php checked(!empty($settings['utility_classes']['enabled'][$class_name])); ?>>
                                        <span class="ecf-starter-class-item__badge ecf-starter-class-item__badge--utility"><?php echo esc_html($this->t('Utility', 'Utility')); ?></span>
                                        <span class="ecf-starter-class-item__name"><?php echo esc_html($class_name); ?></span>
                                        <span class="ecf-starter-class-item__meta"><?php echo esc_html($utility_class_categories[$category_key] ?? ucfirst($category_key)); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_components_panel($args) {
        extract($args, EXTR_SKIP);
        $boxed_width_parts = $this->parse_css_size_parts($settings['elementor_boxed_width'] ?? '1140px');
        $content_width_parts = $this->parse_css_size_parts($settings['content_max_width'] ?? '72ch');
        $boxed_format_options = [
            'px'     => ['label' => 'px',  'tip' => $this->t('Simple pixel value. Example: 1140 becomes 1140px.', 'Einfacher Pixelwert. Beispiel: 1140 wird zu 1140px.')],
            '%'      => ['label' => '%',   'tip' => $this->t('Percentage value. Example: 90 becomes 90%.', 'Prozentwert. Beispiel: 90 wird zu 90%.')],
            'rem'    => ['label' => 'rem', 'tip' => $this->t('Root-based unit. Example: 72 becomes 72rem.', 'Root-basierte Einheit. Beispiel: 72 wird zu 72rem.')],
            'em'     => ['label' => 'em',  'tip' => $this->t('Element-based unit. Example: 72 becomes 72em.', 'Element-basierte Einheit. Beispiel: 72 wird zu 72em.')],
            'vw'     => ['label' => 'vw',  'tip' => $this->t('Viewport width unit. Example: 90 becomes 90vw.', 'Viewport-Breiten-Einheit. Beispiel: 90 wird zu 90vw.')],
            'vh'     => ['label' => 'vh',  'tip' => $this->t('Viewport height unit. Example: 80 becomes 80vh.', 'Viewport-Höhen-Einheit. Beispiel: 80 wird zu 80vh.')],
            'custom' => ['label' => 'f(x)', 'tip' => $this->t('Full CSS expression. Use values like min(100% - 2rem, 1140px), calc(...) or clamp(...).', 'Voller CSS-Ausdruck. Nutze Werte wie min(100% - 2rem, 1140px), calc(...) oder clamp(...).')],
        ];
        $content_format_options = [
            'px'     => ['label' => 'px',  'tip' => $this->t('Simple pixel value. Good for strict content widths like 720px.', 'Einfacher Pixelwert. Gut für feste Inhaltsbreiten wie 720px.')],
            'ch'     => ['label' => 'ch',  'tip' => $this->t('Character-based width. Great for readable text columns like 65ch or 72ch.', 'Zeichenbasierte Breite. Ideal für lesbare Textspalten wie 65ch oder 72ch.')],
            '%'      => ['label' => '%',   'tip' => $this->t('Percentage value if the content width should stay fluid.', 'Prozentwert, wenn die Inhaltsbreite fluid bleiben soll.')],
            'rem'    => ['label' => 'rem', 'tip' => $this->t('Root-based unit. Useful if content width should scale with your root font size.', 'Root-basierte Einheit. Nützlich, wenn die Inhaltsbreite mit der Root Font Size mitskalieren soll.')],
            'em'     => ['label' => 'em',  'tip' => $this->t('Element-based unit. Rarely needed, but possible for content wrappers.', 'Element-basierte Einheit. Selten nötig, aber für Content-Wrapper möglich.')],
            'vw'     => ['label' => 'vw',  'tip' => $this->t('Viewport width unit. Useful for fluid readable widths.', 'Viewport-Breiten-Einheit. Nützlich für fluide Lesebreiten.')],
            'vh'     => ['label' => 'vh',  'tip' => $this->t('Viewport height unit. Usually uncommon here, but available if needed.', 'Viewport-Höhen-Einheit. Hier meist unüblich, aber bei Bedarf verfügbar.')],
            'custom' => ['label' => 'f(x)', 'tip' => $this->t('Full CSS expression. Use values like min(72ch, 100% - 2rem), calc(...) or clamp(...).', 'Voller CSS-Ausdruck. Nutze Werte wie min(72ch, 100% - 2rem), calc(...) oder clamp(...).')],
        ];
        $boxed_selected_format = isset($boxed_format_options[$boxed_width_parts['format']]) ? $boxed_width_parts['format'] : 'px';
        $content_selected_format = isset($content_format_options[$content_width_parts['format']]) ? $content_width_parts['format'] : 'ch';
        $elementor_limit_snapshot = [
            'classes_total' => $this->get_native_global_class_total_count(),
            'classes_limit' => $this->get_native_global_class_limit(),
            'variables_total' => (int) ($this->get_native_variable_counts()['total'] ?? 0),
            'variables_limit' => $this->get_native_global_variable_limit(),
        ];
        $elementor_debug_snapshot = $this->get_elementor_debug_snapshot();
        ?>
        <div class="ecf-panel" data-panel="components">
            <div class="ecf-grid">
                <div class="ecf-card">
                    <div class="ecf-general-settings__header">
                        <h2><?php echo esc_html($this->t('General Settings', 'Allgemeine Einstellungen')); ?></h2>
                        <div class="ecf-format-picker__tooltip ecf-format-picker__tooltip--header" data-ecf-format-tooltip hidden><?php echo esc_html($boxed_format_options[$boxed_selected_format]['tip']); ?></div>
                    </div>
                    <div class="ecf-var-tabs ecf-general-tabs" data-ecf-general-tabs>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="favorites" data-ecf-new-key="general-favorites" title="<?php echo esc_attr($this->t('Pinned quick settings from Website and Plugin basics.', 'Angeheftete Schnelleinstellungen aus Website- und Plugin-Grundlagen.')); ?>"><span class="dashicons dashicons-heart" aria-hidden="true"></span><?php echo esc_html($this->t('Favorites', 'Favoriten')); ?><span class="ecf-new-dot" data-ecf-new-badge hidden data-tip="<?php echo esc_attr($this->t('New: Pin important settings here and edit them directly without leaving this tab.', 'Neu: Wichtige Einstellungen können jetzt hier angeheftet und direkt bearbeitet werden.')); ?>" aria-label="<?php echo esc_attr($this->t('New: Pin important settings here and edit them directly without leaving this tab.', 'Neu: Wichtige Einstellungen können jetzt hier angeheftet und direkt bearbeitet werden.')); ?>"></span></button>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-general-tab="system" title="<?php echo esc_attr($this->t('System-wide base settings like the root rem size.', 'Systemweite Grundeinstellungen wie die globale rem-Basis.')); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php echo esc_html($this->t('System', 'System')); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="layout" title="<?php echo esc_attr($this->t('Global layout widths for readable content and boxed containers.', 'Globale Layout-Breiten für lesbaren Content und zentrierte Container.')); ?>"><span class="dashicons dashicons-screenoptions" aria-hidden="true"></span><?php echo esc_html($this->t('Layout', 'Layout')); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="colors" title="<?php echo esc_attr($this->t('Global base colors for text, background, links and focus states.', 'Globale Basisfarben für Text, Hintergrund, Links und Fokuszustände.')); ?>"><span class="dashicons dashicons-art" aria-hidden="true"></span><?php echo esc_html($this->t('Colors', 'Farben')); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="typography" title="<?php echo esc_attr($this->t('Global body font family and base typography choices.', 'Globale Body-Schriftfamilie und grundlegende Typografie-Auswahl.')); ?>"><span class="dashicons dashicons-editor-textcolor" aria-hidden="true"></span><?php echo esc_html($this->t('Type', 'Typo')); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="behavior" title="<?php echo esc_attr($this->t('Editor helpers and variable-filter behavior inside Elementor.', 'Editor-Helfer und das Filterverhalten der Variablen in Elementor.')); ?>"><span class="dashicons dashicons-controls-repeat" aria-hidden="true"></span><?php echo esc_html($this->t('Behavior', 'Verhalten')); ?></button>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="favorites" hidden>
                        <?php $this->render_general_favorites_section($settings); ?>
                    </div>
                    <div class="ecf-general-section is-active" data-ecf-general-section="system">
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <?php $this->render_root_font_size_select($settings, true); ?>
                            <label class="ecf-form-grid__checkbox" data-ecf-general-field="github_update_checks_enabled">
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[github_update_checks_enabled]" value="1" <?php checked(!empty($settings['github_update_checks_enabled'])); ?>>
                                <span class="ecf-general-label-with-favorite">
                                    <?php echo $this->tip_hover_label($this->t('GitHub update checks', 'GitHub-Update-Prüfungen'), 'Allows ECF to check GitHub for plugin updates. This sends your server to GitHub only for update metadata and downloads.', 'Erlaubt ECF, GitHub auf Plugin-Updates zu prüfen. Dabei verbindet sich dein Server nur für Update-Metadaten und Downloads mit GitHub.'); ?>
                                    <?php $this->render_general_setting_favorite_toggle($settings, 'github_update_checks_enabled'); ?>
                                </span>
                            </label>
                        </div>
                        <div class="ecf-system-limit-card" id="ecf-elementor-limits">
                            <div class="ecf-system-limit-card__header">
                                <strong><?php echo esc_html($this->t('Updater & privacy', 'Updater & Datenschutz')); ?></strong>
                            </div>
                            <div class="ecf-system-limit-card__grid">
                                <div class="ecf-system-limit-card__item">
                                    <span class="ecf-system-limit-card__label"><?php echo esc_html($this->t('Remote service', 'Externer Dienst')); ?></span>
                                    <strong>GitHub</strong>
                                </div>
                                <div class="ecf-system-limit-card__item">
                                    <span class="ecf-system-limit-card__label"><?php echo esc_html($this->t('Current status', 'Aktueller Status')); ?></span>
                                    <strong><?php echo esc_html(!empty($settings['github_update_checks_enabled']) ? $this->t('Enabled', 'Aktiv') : $this->t('Disabled', 'Inaktiv')); ?></strong>
                                </div>
                            </div>
                            <p class="ecf-system-limit-card__hint"><?php echo esc_html($this->t('When enabled, ECF can contact api.github.com and codeload.github.com from your server to check update metadata and download plugin updates. If an ECF_GITHUB_TOKEN is configured, it is sent only to GitHub for authenticated update requests.', 'Wenn aktiv, kann ECF von deinem Server aus api.github.com und codeload.github.com kontaktieren, um Update-Metadaten zu prüfen und Plugin-Updates zu laden. Wenn ein ECF_GITHUB_TOKEN konfiguriert ist, wird er nur für authentifizierte Update-Anfragen an GitHub gesendet.')); ?></p>
                        </div>
                        <div class="ecf-system-limit-card">
                            <div class="ecf-system-limit-card__header">
                                <strong><?php echo esc_html($this->t('Elementor limits', 'Elementor-Limits')); ?></strong>
                                <button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--compact" data-ecf-reload-page>
                                    <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                    <span><?php echo esc_html($this->t('Reload', 'Neu einlesen')); ?></span>
                                </button>
                            </div>
                            <div class="ecf-system-limit-card__grid">
                                <div class="ecf-system-limit-card__item">
                                    <span class="ecf-system-limit-card__label"><?php echo esc_html($this->t('Global Classes', 'Globale Klassen')); ?></span>
                                    <strong><?php echo esc_html((string) $elementor_limit_snapshot['classes_total']); ?> / <?php echo esc_html((string) $elementor_limit_snapshot['classes_limit']); ?></strong>
                                </div>
                                <div class="ecf-system-limit-card__item">
                                    <span class="ecf-system-limit-card__label"><?php echo esc_html($this->t('Variables', 'Variablen')); ?></span>
                                    <strong><?php echo esc_html((string) $elementor_limit_snapshot['variables_total']); ?> / <?php echo esc_html((string) $elementor_limit_snapshot['variables_limit']); ?></strong>
                                </div>
                            </div>
                            <p class="ecf-system-limit-card__hint"><?php echo esc_html($this->t('Detected from the installed Elementor version on this website. Use Reload to fetch the current values again.', 'Erkannt aus installiertem Elementor auf dieser Website. Mit Neu einlesen holst du die aktuellen Werte erneut.')); ?></p>
                        </div>
                        <details class="ecf-system-debug-card" data-ecf-new-key="system-debug">
                            <summary class="ecf-system-debug-card__summary">
                                <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
                                <span><?php echo esc_html($this->t('Debug', 'Debug')); ?></span>
                                <span class="ecf-new-dot" data-ecf-new-badge hidden data-tip="<?php echo esc_attr($this->t('New: Debug shows Elementor Core and Pro detection, active modules and the detected class and variable limits.', 'Neu: Debug zeigt die Elementor-Core-/Pro-Erkennung, aktive Module sowie die erkannten Klassen- und Variablen-Limits.')); ?>" aria-label="<?php echo esc_attr($this->t('New: Debug shows Elementor Core and Pro detection, active modules and the detected class and variable limits.', 'Neu: Debug zeigt die Elementor-Core-/Pro-Erkennung, aktive Module sowie die erkannten Klassen- und Variablen-Limits.')); ?>"></span>
                                <span class="dashicons dashicons-arrow-down-alt2 ecf-system-debug-card__arrow" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-system-debug-card__grid">
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label($this->t('Elementor Core recognized', 'Elementor Core erkannt'), 'Checks whether the Elementor core plugin is loaded and available to ECF on this website.', 'Prüft, ob das Elementor-Core-Plugin auf dieser Website geladen ist und ECF zur Verfügung steht.'); ?></span>
                                    <strong><?php echo esc_html($elementor_debug_snapshot['core_recognized'] ? $this->t('Yes', 'Ja') : $this->t('No', 'Nein')); ?></strong>
                                    <?php if ($elementor_debug_snapshot['core_version'] !== ''): ?>
                                        <small><?php echo esc_html(sprintf($this->t('Version %s', 'Version %s'), $elementor_debug_snapshot['core_version'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label($this->t('Elementor Pro recognized', 'Elementor Pro erkannt'), 'Checks whether Elementor Pro is loaded. Some variables, sync and editor features can depend on it.', 'Prüft, ob Elementor Pro geladen ist. Einige Variablen-, Sync- und Editor-Funktionen können davon abhängen.'); ?></span>
                                    <strong><?php echo esc_html($elementor_debug_snapshot['pro_recognized'] ? $this->t('Yes', 'Ja') : $this->t('No', 'Nein')); ?></strong>
                                    <?php if ($elementor_debug_snapshot['pro_version'] !== ''): ?>
                                        <small><?php echo esc_html(sprintf($this->t('Version %s', 'Version %s'), $elementor_debug_snapshot['pro_version'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label($this->t('Variables module active', 'Variables-Modul aktiv'), 'Checks whether Elementor\'s Variables module is available, which is required for ECF variable sync and picker integration.', 'Prüft, ob Elementors Variables-Modul verfügbar ist. Es wird für den ECF-Variablen-Sync und die Picker-Integration benötigt.'); ?></span>
                                    <strong><?php echo esc_html($elementor_debug_snapshot['variables_active'] ? $this->t('Yes', 'Ja') : $this->t('No', 'Nein')); ?></strong>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label($this->t('Global Classes active', 'Global Classes aktiv'), 'Checks whether Elementor\'s Global Classes module is available for ECF class sync.', 'Prüft, ob Elementors Global-Classes-Modul für den ECF-Klassen-Sync verfügbar ist.'); ?></span>
                                    <strong><?php echo esc_html($elementor_debug_snapshot['global_classes_active'] ? $this->t('Yes', 'Ja') : $this->t('No', 'Nein')); ?></strong>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label($this->t('Design System Sync active', 'Design System Sync aktiv'), 'Checks whether Elementor\'s Design System Sync module is available. This can affect caches and synchronization behavior.', 'Prüft, ob Elementors Design-System-Sync-Modul verfügbar ist. Das kann Caches und das Synchronisationsverhalten beeinflussen.'); ?></span>
                                    <strong><?php echo esc_html($elementor_debug_snapshot['design_system_sync_active'] ? $this->t('Yes', 'Ja') : $this->t('No', 'Nein')); ?></strong>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label($this->t('Detected limits', 'Erkannte Limits'), 'Shows the class and variable limits ECF currently assumes from the installed Elementor setup.', 'Zeigt die Klassen- und Variablen-Limits, von denen ECF aktuell anhand des installierten Elementor-Setups ausgeht.'); ?></span>
                                    <strong><?php echo esc_html(sprintf($this->t('%1$s classes / %2$s variables', '%1$s Klassen / %2$s Variablen'), (string) $elementor_debug_snapshot['classes_limit'], (string) $elementor_debug_snapshot['variables_limit'])); ?></strong>
                                    <small><?php echo esc_html(sprintf($this->t('Source: classes via %1$s, variables via %2$s', 'Quelle: Klassen über %1$s, Variablen über %2$s'), $elementor_debug_snapshot['classes_limit_source'], $elementor_debug_snapshot['variables_limit_source'])); ?></small>
                                </div>
                            </div>
                            <p class="ecf-system-debug-card__hint"><?php echo esc_html($this->t('Useful for checking whether Elementor and its design-system modules are available before debugging sync or editor issues.', 'Hilfreich, um vor der Fehlersuche bei Sync oder Editor-Problemen zu prüfen, ob Elementor und seine Design-System-Module verfügbar sind.')); ?></p>
                        </details>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('Controls the global rem base for typography, spacing, and radius tokens. Change this only if you want the whole sizing system to shift.', 'Steuert die globale rem-Basis für Typografie-, Abstands- und Radius-Tokens. Ändere das nur, wenn sich das gesamte Größensystem verschieben soll.')); ?></p>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="layout" hidden>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <label data-ecf-general-field="content_max_width">
                                <span class="ecf-general-label-with-favorite">
                                    <?php echo $this->tip_hover_label($this->t('Content Max Width', 'Content Max Width'), 'Creates the CSS token --ecf-content-max-width for readable text/content wrappers. ch works especially well for article-like content widths.', 'Erstellt den CSS-Token --ecf-content-max-width für lesbare Text-/Content-Wrapper. ch eignet sich besonders gut für artikelartige Inhaltsbreiten.'); ?>
                                    <?php $this->render_general_setting_favorite_toggle($settings, 'content_max_width'); ?>
                                </span>
                                <div class="ecf-inline-size-input">
                                    <input type="text" name="<?php echo esc_attr($this->option_name); ?>[content_max_width_value]" value="<?php echo esc_attr($content_width_parts['value']); ?>" placeholder="72 oder min(72ch, 100% - 2rem)" title="<?php echo esc_attr($this->t('Enter either a simple value like 72 or, with f(x), a full CSS expression such as min(72ch, 100% - 2rem).', 'Gib entweder einen einfachen Wert wie 72 ein oder mit f(x) einen kompletten CSS-Ausdruck wie min(72ch, 100% - 2rem).')); ?>">
                                    <div class="ecf-format-picker" data-ecf-format-picker>
                                        <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[content_max_width_format]" value="<?php echo esc_attr($content_selected_format); ?>" data-ecf-format-input>
                                        <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false" title="<?php echo esc_attr($this->t('Choose the unit for simple values. ch is usually best for readable text widths. Use f(x) for full CSS expressions.', 'Wähle die Einheit für einfache Werte. ch ist meist am besten für lesbare Textbreiten. Nutze f(x) für komplette CSS-Ausdrücke.')); ?>">
                                            <span data-ecf-format-current><?php echo esc_html($content_format_options[$content_selected_format]['label']); ?></span>
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </button>
                                        <div class="ecf-format-picker__menu" data-ecf-format-menu hidden>
                                            <div class="ecf-format-picker__options">
                                                <?php foreach ($content_format_options as $format_value => $format_config): ?>
                                                    <button type="button"
                                                            class="ecf-format-picker__option<?php echo $format_value === $content_selected_format ? ' is-active' : ''; ?>"
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
                            </label>
                            <label data-ecf-general-field="elementor_boxed_width">
                                <span class="ecf-general-label-with-favorite">
                                    <?php echo $this->tip_hover_label($this->t('Elementor Boxed Width', 'Elementor Boxed Width'), 'Creates the CSS token --ecf-container-boxed and the helper class .ecf-container-boxed. Choose a format like px, %, rem or switch to f(x) for values like min(...), calc(...) or clamp(...).', 'Erstellt den CSS-Token --ecf-container-boxed und die Helferklasse .ecf-container-boxed. Wähle ein Format wie px, %, rem oder nutze f(x) für Werte wie min(...), calc(...) oder clamp(...).'); ?>
                                    <?php $this->render_general_setting_favorite_toggle($settings, 'elementor_boxed_width'); ?>
                                </span>
                                <div class="ecf-inline-size-input">
                                    <input type="text" name="<?php echo esc_attr($this->option_name); ?>[elementor_boxed_width_value]" value="<?php echo esc_attr($boxed_width_parts['value']); ?>" placeholder="1140 oder clamp(20rem, 80vw, 1140px)" title="<?php echo esc_attr($this->t('Enter either a plain value like 1140 or, with f(x), a full CSS expression such as clamp(20rem, 80vw, 1140px).', 'Gib entweder einen einfachen Wert wie 1140 ein oder mit f(x) einen kompletten CSS-Ausdruck wie clamp(20rem, 80vw, 1140px).')); ?>">
                                    <div class="ecf-format-picker" data-ecf-format-picker>
                                        <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[elementor_boxed_width_format]" value="<?php echo esc_attr($boxed_selected_format); ?>" data-ecf-format-input>
                                        <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false" title="<?php echo esc_attr($this->t('Choose the unit for simple values. Use f(x) for complete CSS expressions like min(...), calc(...) or clamp(...).', 'Wähle die Einheit für einfache Werte. Nutze f(x) für komplette CSS-Ausdrücke wie min(...), calc(...) oder clamp(...).')); ?>">
                                            <span data-ecf-format-current><?php echo esc_html($boxed_format_options[$boxed_selected_format]['label']); ?></span>
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </button>
                                        <div class="ecf-format-picker__menu" data-ecf-format-menu hidden>
                                            <div class="ecf-format-picker__options">
                                                <?php foreach ($boxed_format_options as $format_value => $format_config): ?>
                                                    <button type="button"
                                                            class="ecf-format-picker__option<?php echo $format_value === $boxed_selected_format ? ' is-active' : ''; ?>"
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
                            </label>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('Use Content Max Width for readable text wrappers and Elementor Boxed Width for wider centered layout containers.', 'Nutze Content Max Width für lesbare Text-Wrapper und Elementor Boxed Width für breitere zentrierte Layout-Container.')); ?></p>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="colors" hidden>
                        <div class="ecf-form-grid ecf-form-grid--two">
                            <?php $this->render_general_color_field($settings, 'base_text_color', 'Base Text Color', 'Basis-Textfarbe', 'Default body text color for the whole site.', 'Standard-Textfarbe für den Fließtext der ganzen Website.'); ?>
                            <?php $this->render_general_color_field($settings, 'base_background_color', 'Base Background Color', 'Basis-Hintergrundfarbe', 'Default page background for the website.', 'Standard-Seitenhintergrund für die Website.'); ?>
                            <?php $this->render_general_color_field($settings, 'link_color', 'Link Color', 'Link-Farbe', 'Default color for normal links.', 'Standardfarbe für normale Links.'); ?>
                            <?php $this->render_general_color_field($settings, 'focus_color', 'Focus Color', 'Fokus-Farbe', 'Visible color for keyboard focus outlines and focus rings.', 'Sichtbare Farbe für Tastatur-Fokusrahmen und Focus-Rings.'); ?>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('These colors affect the site basis directly and are also exposed as global ECF tokens.', 'Diese Farben wirken direkt auf die Website-Basis und werden zusätzlich als globale ECF-Tokens ausgegeben.')); ?></p>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="typography" hidden>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <?php $this->render_base_font_family_field($settings); ?>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('Use this as the global body font. Upload local fonts in Typography > Local Fonts and then select them here by name.', 'Nutze das als globale Body-Schrift. Lade lokale Schriften unter Typografie > Lokale Schriften hoch und wähle sie danach hier namentlich aus.')); ?></p>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="behavior" hidden>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <label class="ecf-form-grid__checkbox" data-ecf-general-field="show_elementor_status_cards">
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[show_elementor_status_cards]" value="1" <?php checked(!empty($settings['show_elementor_status_cards'])); ?>>
                                <span class="ecf-general-label-with-favorite"><?php echo $this->tip_hover_label($this->t('Show status cards in Variables & Sync', 'Statuskarten in Variablen & Sync anzeigen'), 'Shows small overview cards in the Variables and Sync areas so you can see current Elementor usage and limits at a glance.', 'Zeigt kleine Übersichtskarten in den Bereichen Variablen und Sync, damit du die aktuelle Elementor-Nutzung und Limits schneller auf einen Blick siehst.'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'show_elementor_status_cards'); ?></span>
                            </label>
                            <label class="ecf-form-grid__checkbox" data-ecf-general-field="elementor_variable_type_filter">
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter'])); ?>>
                                <span class="ecf-general-label-with-favorite"><?php echo $this->tip_hover_label($this->t('Limit Elementor variables by field type', 'Elementor-Variablen nach Feldtyp begrenzen'), 'Only shows matching variables in Elementor fields. Example: color fields get color variables, spacing fields get spacing variables.', 'Zeigt in Elementor-Feldern nur passende Variablen an. Beispiel: Farbfelder bekommen Farbvariablen, Abstands-Felder bekommen Abstandsvariablen.'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'elementor_variable_type_filter'); ?></span>
                            </label>
                            <details class="ecf-filter-scope-box">
                                <summary class="ecf-filter-scope-box__summary">
                                    <div class="ecf-filter-scope-box__title"><?php echo $this->tip_hover_label($this->t('Filter for', 'Filtern für'), 'Choose which variable groups should be filtered by matching Elementor field types.', 'Wähle, welche Variablengruppen nach passenden Elementor-Feldtypen gefiltert werden sollen.'); ?></div>
                                    <span class="dashicons dashicons-arrow-down-alt2 ecf-filter-scope-box__arrow" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-form-grid ecf-form-grid--two ecf-filter-scope-grid">
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][color]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['color'])); ?>>
                                        <span><?php echo $this->tip_hover_label($this->t('Colors', 'Farben'), 'Filters color variables like brand, text, border or background colors to color-compatible Elementor fields.', 'Filtert Farbvariablen wie Brand-, Text-, Rand- oder Hintergrundfarben in farbkompatible Elementor-Felder.'); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][text]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['text'])); ?>>
                                        <span><?php echo $this->tip_hover_label($this->t('Typography', 'Typografie'), 'Filters typography variables like text sizes so they appear only in matching typography-related Elementor controls.', 'Filtert Typografie-Variablen wie Textgrößen, damit sie nur in passenden typography-bezogenen Elementor-Feldern erscheinen.'); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][space]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['space'])); ?>>
                                        <span><?php echo $this->tip_hover_label($this->t('Spacing', 'Abstände'), 'Filters spacing variables like gaps, padding or margins into spacing-compatible Elementor fields.', 'Filtert Abstands-Variablen wie Gaps, Padding oder Margins in abstandskompatible Elementor-Felder.'); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][radius]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['radius'])); ?>>
                                        <span><?php echo $this->tip_hover_label($this->t('Radius', 'Radius'), 'Filters border-radius style variables into matching radius fields.', 'Filtert Border-Radius-Variablen in passende Radius-Felder.'); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][shadow]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['shadow'])); ?>>
                                        <span><?php echo $this->tip_hover_label($this->t('Shadows', 'Schatten'), 'Filters shadow variables into matching box-shadow or shadow-related Elementor fields.', 'Filtert Schatten-Variablen in passende Box-Shadow- oder schattenbezogene Elementor-Felder.'); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][string]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['string'])); ?>>
                                        <span><?php echo $this->tip_hover_label($this->t('Other text values', 'Sonstige Textwerte'), 'Filters remaining string-based values that are neither size nor color, for example free text-like CSS values.', 'Filtert übrige stringbasierte Werte, die weder Größe noch Farbe sind, zum Beispiel freie textartige CSS-Werte.'); ?></span>
                                    </label>
                                </div>
                            </details>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('Controls helper cards and the optional variable filtering in the Elementor editor.', 'Steuert die Info-Karten und die optionale Variablen-Filterung im Elementor-Editor.')); ?></p>
                    </div>
                    <p class="ecf-muted-copy"><?php echo esc_html($this->t('When enabled, the Elementor editor should later be able to show only matching variables for colors, sizes, and other fitting field types.', 'Wenn aktiv, soll der Elementor-Editor später nur passende Variablen für Farben, Größen und andere geeignete Feldtypen anzeigen können.')); ?></p>
                    <div class="ecf-root-font-impact"
                         data-ecf-root-font-impact
                         data-type-step="<?php echo esc_attr($settings['typography']['scale']['base_index'] ?? 'm'); ?>"
                         data-spacing-step="<?php echo esc_attr($settings['spacing']['base_index'] ?? 'm'); ?>"
                         data-radius-name="<?php echo esc_attr(sanitize_key($radius_root_preview['name'] ?? 'm')); ?>"
                         data-label-type="<?php echo esc_attr($this->t('Typography token', 'Typografie-Token')); ?>"
                         data-label-spacing="<?php echo esc_attr($this->t('Spacing token', 'Abstands-Token')); ?>"
                         data-label-radius="<?php echo esc_attr($this->t('Radius token', 'Radius-Token')); ?>"
                         data-label-min="<?php echo esc_attr($this->t('Minimum', 'Minimum')); ?>"
                         data-label-max="<?php echo esc_attr($this->t('Maximum', 'Maximum')); ?>"
                         data-preview-type-word="<?php echo esc_attr($this->t('Typography', 'Typografie')); ?>"
                         data-label-base="<?php echo esc_attr($this->t('Current rem base', 'Aktuelle rem-Basis')); ?>">
                        <div class="ecf-root-font-impact__header">
                            <strong><?php echo esc_html($this->t('Visible effect of the root font size', 'Sichtbare Auswirkung der Root Font Size')); ?></strong>
                            <span data-ecf-root-font-base><?php echo esc_html(sprintf($this->t('Currently: %spx = 1rem', 'Aktuell: %spx = 1rem'), $root_base_px)); ?></span>
                        </div>
                        <div class="ecf-root-font-impact__items">
                            <div class="ecf-root-font-impact__item">
                                <span><?php echo esc_html($this->t('Typography token', 'Typografie-Token')); ?></span>
                                <div class="ecf-root-font-impact__token-row">
                                    <code data-ecf-root-type-token><?php echo esc_html('--ecf-text-' . ($type_root_preview['step'] ?? ($settings['typography']['scale']['base_index'] ?? 'm'))); ?></code>
                                    <button type="button" class="ecf-root-font-impact__copy-toggle" data-ecf-root-copy-toggle="<?php echo esc_attr($this->t('Toggle clamp output', 'Clamp-Ausgabe einblenden')); ?>">
                                        <span class="dashicons dashicons-editor-code"></span>
                                    </button>
                                </div>
                                <button type="button" class="ecf-root-font-impact__copy-pop" data-ecf-root-type-copy></button>
                                <div class="ecf-root-font-impact__range">
                                    <div class="ecf-root-font-impact__metric">
                                        <span data-ecf-root-type-min-label><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span>
                                        <strong data-ecf-root-type-min><?php echo esc_html(($type_root_preview['min_px'] ?? $type_root_preview['minPx'] ?? '') . 'px'); ?></strong>
                                        <em data-ecf-root-type-min-preview><?php echo esc_html($this->t('Typography', 'Typografie')); ?></em>
                                    </div>
                                    <div class="ecf-root-font-impact__metric">
                                        <span data-ecf-root-type-max-label><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span>
                                        <strong data-ecf-root-type-max><?php echo esc_html(($type_root_preview['max_px'] ?? $type_root_preview['maxPx'] ?? '') . 'px'); ?></strong>
                                        <em data-ecf-root-type-max-preview><?php echo esc_html($this->t('Typography', 'Typografie')); ?></em>
                                    </div>
                                </div>
                            </div>
                            <div class="ecf-root-font-impact__item">
                                <span><?php echo esc_html($this->t('Spacing token', 'Abstands-Token')); ?></span>
                                <div class="ecf-root-font-impact__token-row">
                                    <code data-ecf-root-spacing-token><?php echo esc_html('--ecf-' . sanitize_key($settings['spacing']['prefix'] ?? 'space') . '-' . ($spacing_root_preview['step'] ?? ($settings['spacing']['base_index'] ?? 'm'))); ?></code>
                                    <button type="button" class="ecf-root-font-impact__copy-toggle" data-ecf-root-copy-toggle="<?php echo esc_attr($this->t('Toggle clamp output', 'Clamp-Ausgabe einblenden')); ?>">
                                        <span class="dashicons dashicons-editor-code"></span>
                                    </button>
                                </div>
                                <button type="button" class="ecf-root-font-impact__copy-pop" data-ecf-root-spacing-copy></button>
                                <div class="ecf-root-font-impact__range">
                                    <div class="ecf-root-font-impact__metric">
                                        <span data-ecf-root-spacing-min-label><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span>
                                        <strong data-ecf-root-spacing-min><?php echo esc_html(($spacing_root_preview['min_px'] ?? $spacing_root_preview['minPx'] ?? '') . 'px'); ?></strong>
                                        <div class="ecf-root-font-impact__bar"><div class="ecf-root-font-impact__bar-fill" data-ecf-root-spacing-min-bar></div></div>
                                    </div>
                                    <div class="ecf-root-font-impact__metric">
                                        <span data-ecf-root-spacing-max-label><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span>
                                        <strong data-ecf-root-spacing-max><?php echo esc_html(($spacing_root_preview['max_px'] ?? $spacing_root_preview['maxPx'] ?? '') . 'px'); ?></strong>
                                        <div class="ecf-root-font-impact__bar"><div class="ecf-root-font-impact__bar-fill" data-ecf-root-spacing-max-bar></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="ecf-root-font-impact__item">
                                <span><?php echo esc_html($this->t('Radius token', 'Radius-Token')); ?></span>
                                <div class="ecf-root-font-impact__token-row">
                                    <code data-ecf-root-radius-token><?php echo esc_html('--ecf-radius-' . sanitize_key($radius_root_preview['name'] ?? 'm')); ?></code>
                                    <button type="button" class="ecf-root-font-impact__copy-toggle" data-ecf-root-copy-toggle="<?php echo esc_attr($this->t('Toggle clamp output', 'Clamp-Ausgabe einblenden')); ?>">
                                        <span class="dashicons dashicons-editor-code"></span>
                                    </button>
                                </div>
                                <button type="button" class="ecf-root-font-impact__copy-pop" data-ecf-root-radius-copy></button>
                                <div class="ecf-root-font-impact__range">
                                    <div class="ecf-root-font-impact__metric">
                                        <span data-ecf-root-radius-min-label><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span>
                                        <strong data-ecf-root-radius-min><?php echo esc_html($this->format_preview_number($radius_root_preview['min'] ?? 0) . 'px'); ?></strong>
                                        <div class="ecf-root-font-impact__radius-preview" data-ecf-root-radius-min-preview></div>
                                    </div>
                                    <div class="ecf-root-font-impact__metric">
                                        <span data-ecf-root-radius-max-label><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span>
                                        <strong data-ecf-root-radius-max><?php echo esc_html($this->format_preview_number($radius_root_preview['max'] ?? ($radius_root_preview['min'] ?? 0)) . 'px'); ?></strong>
                                        <div class="ecf-root-font-impact__radius-preview" data-ecf-root-radius-max-preview></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_typography_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="typography">
            <div class="ecf-typography-layout">
                <div class="ecf-typography-sidebar">
                    <div class="ecf-card">
                        <h2><?php echo esc_html($this->t('Type Scale', 'Schriftskala')); ?></h2>
                        <?php $this->render_root_font_size_select($settings, false); ?>
                        <div class="ecf-form-grid ecf-form-grid--compact">
                            <label><?php echo $this->tip_hover_label($this->t('Min Font Size (px)', 'Min Font Size (px)'), 'Font size at the smallest viewport (mobile). The base step gets this size.', 'Schriftgröße beim kleinsten Viewport (Mobil). Die Basisstufe bekommt diese Größe.'); ?>
                                <input type="number" step="0.01" name="<?php echo $this->option_name; ?>[typography][scale][min_base]" value="<?php echo esc_attr($settings['typography']['scale']['min_base'] ?? 16); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Min Scale Ratio', 'Min Scale Ratio'), 'Multiplier between steps at mobile size. E.g. 1.125 means each step is 12.5% larger.', 'Faktor zwischen den Stufen auf Mobilgeräten. Z.B. 1.125 bedeutet jede Stufe ist 12,5% größer.'); ?>
                                <?php $this->render_scale_ratio_select($this->option_name . '[typography][scale][min_ratio]', $settings['typography']['scale']['min_ratio'] ?? ($settings['typography']['scale']['ratio'] ?? 1.125)); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Max Font Size (px)', 'Max Font Size (px)'), 'Font size at the largest viewport (desktop). The base step gets this size.', 'Schriftgröße beim größten Viewport (Desktop). Die Basisstufe bekommt diese Größe.'); ?>
                                <input type="number" step="0.01" name="<?php echo $this->option_name; ?>[typography][scale][max_base]" value="<?php echo esc_attr($settings['typography']['scale']['max_base'] ?? 18); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Max Scale Ratio', 'Max Scale Ratio'), 'Multiplier between steps at desktop size. A higher ratio creates more contrast between sizes.', 'Faktor zwischen den Stufen auf dem Desktop. Ein höherer Wert erzeugt mehr Kontrast zwischen den Größen.'); ?>
                                <?php $this->render_scale_ratio_select($this->option_name . '[typography][scale][max_ratio]', $settings['typography']['scale']['max_ratio'] ?? ($settings['typography']['scale']['ratio'] ?? 1.25)); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Base step', 'Basisstufe'), 'The step that equals your base font size. Steps above are larger, steps below are smaller.', 'Die Stufe, die deiner Basis-Schriftgröße entspricht. Stufen darüber sind größer, darunter kleiner.'); ?>
                                <select name="<?php echo $this->option_name; ?>[typography][scale][base_index]">
                                    <?php foreach ($settings['typography']['scale']['steps'] as $step): ?>
                                        <option value="<?php echo esc_attr($step); ?>" <?php selected($settings['typography']['scale']['base_index'], $step); ?>><?php echo esc_html($step); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-check ecf-check--inline">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[typography][scale][fluid]" value="1" <?php checked(!empty($settings['typography']['scale']['fluid'])); ?>>
                                <span><?php echo $this->tip_hover_label('Fluid (clamp)', 'Generates clamp() values that smoothly scale between min and max viewport width.', 'Generiert clamp()-Werte, die zwischen Min- und Max-Viewport flüssig skalieren.'); ?></span>
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Min viewport (px)', 'Min. Viewport (px)'), 'Viewport width at which the minimum font sizes apply (typically 375px for mobile).', 'Viewport-Breite, bei der die minimalen Schriftgrößen gelten (typischerweise 375px für Mobil).'); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[typography][scale][min_vw]" value="<?php echo esc_attr($settings['typography']['scale']['min_vw']); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Max viewport (px)', 'Max. Viewport (px)'), 'Viewport width at which the maximum font sizes apply (typically 1280px for desktop).', 'Viewport-Breite, bei der die maximalen Schriftgrößen gelten (typischerweise 1280px für Desktop).'); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[typography][scale][max_vw]" value="<?php echo esc_attr($settings['typography']['scale']['max_vw']); ?>">
                            </label>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('The preview updates live while you edit the scale settings.', 'Die Vorschau aktualisiert sich live, während du die Skala bearbeitest.')); ?></p>
                    </div>
                    <div class="ecf-card">
                        <h2><?php echo esc_html($this->t('Font Families', 'Schriftarten')); ?></h2>
                        <?php $this->render_rows('typography_fonts', $settings['typography']['fonts'], $this->option_name.'[typography][fonts]'); ?>
                    </div>
                    <div class="ecf-card" data-ecf-local-fonts-section>
                        <h2><?php echo esc_html($this->t('Local Font Files', 'Lokale Schriftdateien')); ?></h2>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('Upload font files into the WordPress media library and then use their family names in your Primary, Secondary or Mono stacks.', 'Lade Schriftdateien in die WordPress-Mediathek hoch und nutze anschließend deren Familiennamen in deinen Primary-, Secondary- oder Mono-Stacks.')); ?></p>
                        <?php $this->render_local_font_rows($settings['typography']['local_fonts'] ?? [], $this->option_name.'[typography][local_fonts]'); ?>
                    </div>
                </div>
                <div class="ecf-card ecf-typography-preview-card"
                     data-ecf-type-scale-preview
                     data-steps="<?php echo esc_attr(wp_json_encode($settings['typography']['scale']['steps'])); ?>"
                     data-active-step="<?php echo esc_attr($settings['typography']['scale']['base_index']); ?>"
                     data-preview-label-min="<?php echo esc_attr($this->t('Minimum', 'Minimum')); ?>"
                     data-preview-label-max="<?php echo esc_attr($this->t('Maximum', 'Maximum')); ?>"
                     data-preview-label-fixed="<?php echo esc_attr($this->t('Static', 'Statisch')); ?>"
                     data-preview-label-fluid="<?php echo esc_attr($this->t('Fluid', 'Fluid')); ?>"
                     data-preview-word="<?php echo esc_attr($this->t('Typography', 'Typografie')); ?>"
                     data-preview-helper="<?php echo esc_attr($this->t('Click a scale step to inspect it in detail.', 'Klicke auf eine Schriftstufe, um sie im Detail zu prüfen.')); ?>"
                     data-preview-font="<?php echo esc_attr($type_preview_font); ?>">
                    <div class="ecf-typography-preview-header">
                        <div>
                            <h2><?php echo esc_html($this->t('Live Type Preview', 'Live-Schriftvorschau')); ?></h2>
                            <p><?php echo esc_html($this->t('Preview for your generated Elementor text variables.', 'Vorschau deiner generierten Elementor-Textvariablen.')); ?></p>
                        </div>
                        <div class="ecf-preview-toolbar">
                            <button type="button" class="ecf-preview-toggle" data-ecf-preview-view="min"><i class="dashicons dashicons-smartphone"></i><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></button>
                            <button type="button" class="ecf-preview-toggle is-active" data-ecf-preview-view="fluid"><?php echo esc_html($this->t('Fluid', 'Fluid')); ?></button>
                            <button type="button" class="ecf-preview-toggle" data-ecf-preview-view="max"><i class="dashicons dashicons-desktop"></i><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></button>
                        </div>
                    </div>
                    <div class="ecf-typography-focus" data-ecf-type-scale-focus>
                        <div class="ecf-typography-focus__meta">
                            <span class="ecf-preview-pill" data-ecf-preview-mode><?php echo esc_html(!empty($settings['typography']['scale']['fluid']) ? 'Fluid' : $this->t('Static', 'Statisch')); ?></span>
                            <strong data-ecf-focus-token>--ecf-text-<?php echo esc_html($settings['typography']['scale']['base_index']); ?></strong>
                            <p data-ecf-focus-helper><?php echo esc_html($this->t('Klicke auf eine Schriftstufe, um sie im Detail zu prüfen.', 'Klicke auf eine Schriftstufe, um sie im Detail zu prüfen.')); ?></p>
                        </div>
                        <div class="ecf-typography-focus__sample">
                            <div class="ecf-typography-focus__word" data-ecf-focus-word><?php echo esc_html($this->t('Typography', 'Typografie')); ?></div>
                            <div class="ecf-typography-focus__stats">
                                <div>
                                    <span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span>
                                    <div class="ecf-clamp-metric">
                                        <strong data-ecf-focus-min><?php echo esc_html($base_type_preview['min_px'] ?? '16'); ?>px</strong>
                                        <button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="<?php echo esc_attr($this->t('Show clamp value', 'Clamp-Wert anzeigen')); ?>"><span class="dashicons dashicons-editor-code"></span></button>
                                    </div>
                                    <button type="button" class="ecf-clamp-popover" data-ecf-focus-min-copy><?php echo esc_html($base_type_preview['css_value'] ?? ''); ?></button>
                                </div>
                                <div>
                                    <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span>
                                    <div class="ecf-clamp-metric">
                                        <strong data-ecf-focus-max><?php echo esc_html($base_type_preview['max_px'] ?? '16'); ?>px</strong>
                                        <button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="<?php echo esc_attr($this->t('Show clamp value', 'Clamp-Wert anzeigen')); ?>"><span class="dashicons dashicons-editor-code"></span></button>
                                    </div>
                                    <button type="button" class="ecf-clamp-popover" data-ecf-focus-max-copy><?php echo esc_html($base_type_preview['css_value'] ?? ''); ?></button>
                                </div>
                            </div>
                                <div class="ecf-typography-focus__sizes">
                                <div class="ecf-typography-focus__size-line">
                                    <strong data-ecf-focus-min-line><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></strong>
                                    <span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span>
                                </div>
                                <div class="ecf-typography-focus__size-line ecf-typography-focus__size-line--max">
                                    <strong data-ecf-focus-max-line><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></strong>
                                    <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="ecf-scale-steps-container">
                        <?php foreach ($settings['typography']['scale']['steps'] as $step): ?>
                        <input type="hidden" class="ecf-scale-step-input" name="<?php echo esc_attr($this->option_name); ?>[typography][scale][steps][]" value="<?php echo esc_attr($step); ?>">
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-step-controls ecf-step-controls--top">
                        <button type="button" class="ecf-step-btn" data-ecf-add-step="smaller" title="<?php echo esc_attr($this->t('Add smaller step', 'Kleinere Stufe hinzufügen')); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-remove-step="smaller" title="<?php echo esc_attr($this->t('Remove smallest step', 'Kleinste Stufe entfernen')); ?>">−</button>
                    </div>
                    <div class="ecf-typography-preview-list" data-ecf-type-scale-preview-list>
                        <?php foreach ($type_scale_preview as $item): ?>
                            <button type="button" class="ecf-type-row<?php echo $item['step'] === $settings['typography']['scale']['base_index'] ? ' is-active' : ''; ?>" data-ecf-step="<?php echo esc_attr($item['step']); ?>" style="<?php echo esc_attr('--ecf-preview-size:' . $item['css_value'] . ';'); ?>">
                                <div class="ecf-type-row__token"><?php echo esc_html($item['token']); ?><span class="ecf-copy-pill" data-copy="<?php echo esc_attr($item['token']); ?>"><?php echo esc_html($this->t('Copy', 'Kopieren')); ?></span></div>
                                <div class="ecf-type-row__meta">
                                    <div><span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span><strong><?php echo esc_html($item['min_px']); ?>px</strong></div>
                                    <div><span><i class="dashicons dashicons-desktop"></i><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span><strong><?php echo esc_html($item['max_px']); ?>px</strong></div>
                                </div>
                                <div class="ecf-type-row__sample">
                                    <div class="ecf-type-row__sample-line">
                                        <strong style="font-size:<?php echo esc_attr($item['min_px']); ?>px;"><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></strong>
                                        <span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span>
                                    </div>
                                    <div class="ecf-type-row__sample-line ecf-type-row__sample-line--max">
                                        <strong style="font-size:<?php echo esc_attr($item['max_px']); ?>px;"><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></strong>
                                        <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span>
                                    </div>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-step-controls ecf-step-controls--bottom">
                        <button type="button" class="ecf-step-btn" data-ecf-add-step="larger" title="<?php echo esc_attr($this->t('Add larger step', 'Größere Stufe hinzufügen')); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-remove-step="larger" title="<?php echo esc_attr($this->t('Remove largest step', 'Größte Stufe entfernen')); ?>">−</button>
                    </div>
                </div>
            </div>
            <div class="ecf-grid">
                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Font Weights', 'Schriftstärken')); ?></h2>
                    <?php $this->render_rows('typography_weights', $settings['typography']['weights'], $this->option_name.'[typography][weights]'); ?>
                </div>
                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Line Heights', 'Zeilenhöhen')); ?></h2>
                    <?php $this->render_rows('typography_leading', $settings['typography']['leading'], $this->option_name.'[typography][leading]'); ?>
                </div>
                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Letter Spacing', 'Buchstabenabstand')); ?></h2>
                    <?php $this->render_rows('typography_tracking', $settings['typography']['tracking'], $this->option_name.'[typography][tracking]'); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_spacing_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="spacing">
            <div class="ecf-spacing-layout">
                <div class="ecf-spacing-sidebar">
                    <div class="ecf-card">
                        <h2><?php echo esc_html($this->t('Base Settings', 'Basiseinstellungen')); ?></h2>
                        <?php $this->render_root_font_size_select($settings, false); ?>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <label><?php echo $this->tip_hover_label($this->t('Naming Convention', 'Bezeichnung'), 'Prefix used for CSS tokens, e.g. "space" → --ecf-space-m. Change to rename all tokens.', 'Präfix für CSS-Tokens, z.B. "space" → --ecf-space-m. Ändern um alle Tokens umzubenennen.'); ?>
                                <input type="text" name="<?php echo $this->option_name; ?>[spacing][prefix]" value="<?php echo esc_attr($settings['spacing']['prefix'] ?? 'space'); ?>" placeholder="space">
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Min Size (px)', 'Min. Größe (px)'), 'Base spacing size at the smallest viewport (mobile). All other steps scale relative to this.', 'Basis-Abstandsgröße beim kleinsten Viewport (Mobil). Alle anderen Stufen skalieren relativ dazu.'); ?>
                                <input type="number" step="0.1" name="<?php echo $this->option_name; ?>[spacing][min_base]" value="<?php echo esc_attr($settings['spacing']['min_base'] ?? 14); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Min Scale Ratio', 'Min. Skalierung'), 'Multiplier between spacing steps on mobile. 1.25 means each step is 25% larger than the previous.', 'Faktor zwischen Abstandsstufen auf Mobil. 1.25 bedeutet jede Stufe ist 25% größer als die vorherige.'); ?>
                                <?php $this->render_scale_ratio_select($this->option_name.'[spacing][min_ratio]', $settings['spacing']['min_ratio'] ?? 1.2); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Max Size (px)', 'Max. Größe (px)'), 'Base spacing size at the largest viewport (desktop). Typically slightly larger than the min size.', 'Basis-Abstandsgröße beim größten Viewport (Desktop). Typischerweise etwas größer als Min.'); ?>
                                <input type="number" step="0.1" name="<?php echo $this->option_name; ?>[spacing][max_base]" value="<?php echo esc_attr($settings['spacing']['max_base'] ?? $settings['spacing']['base'] ?? 16); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Max Scale Ratio', 'Max. Skalierung'), 'Multiplier between spacing steps on desktop. A higher ratio creates more visual contrast between sizes.', 'Faktor zwischen Abstandsstufen auf Desktop. Ein höherer Wert erzeugt mehr visuellen Kontrast.'); ?>
                                <?php $this->render_scale_ratio_select($this->option_name.'[spacing][max_ratio]', $settings['spacing']['max_ratio'] ?? $settings['spacing']['ratio_up'] ?? 1.25); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Base Step', 'Basis-Stufe'), 'The step that equals your base spacing size. Steps above are larger, steps below are smaller.', 'Die Stufe, die deiner Basis-Abstandsgröße entspricht. Stufen darüber sind größer, darunter kleiner.'); ?>
                                <select name="<?php echo $this->option_name; ?>[spacing][base_index]">
                                    <?php foreach ($settings['spacing']['steps'] as $step): ?>
                                        <option value="<?php echo esc_attr($step); ?>" <?php selected($settings['spacing']['base_index'], $step); ?>><?php echo esc_html($step); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-form-grid__checkbox">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[spacing][fluid]" value="1" <?php checked(!empty($settings['spacing']['fluid'])); ?>>
                                <?php echo $this->tip_hover_label($this->t('Fluid (clamp)', 'Fluid (clamp)'), 'Generates clamp() values that smoothly scale between min and max viewport widths.', 'Generiert clamp()-Werte, die zwischen Min- und Max-Viewport flüssig skalieren.'); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Min Viewport (px)', 'Min. Viewport (px)'), 'Screen width at which minimum spacing sizes apply. Usually 375px (iPhone).', 'Bildschirmbreite, bei der die minimalen Abstände gelten. Normalerweise 375px (iPhone).'); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[spacing][min_vw]" value="<?php echo esc_attr($settings['spacing']['min_vw']); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label($this->t('Max Viewport (px)', 'Max. Viewport (px)'), 'Screen width at which maximum spacing sizes apply. Usually 1280px (desktop).', 'Bildschirmbreite, bei der die maximalen Abstände gelten. Normalerweise 1280px (Desktop).'); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[spacing][max_vw]" value="<?php echo esc_attr($settings['spacing']['max_vw']); ?>">
                            </label>
                        </div>
                    </div>
                    <div class="ecf-card" style="margin-top:14px;">
                        <h2><?php echo esc_html($this->t('Container Widths', 'Container-Breiten')); ?></h2>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <?php foreach (['sm','md','lg','xl'] as $size): ?>
                                <label><?php echo esc_html(strtoupper($size)); ?>
                                    <input type="text" name="<?php echo $this->option_name; ?>[container][<?php echo esc_attr($size); ?>]" value="<?php echo esc_attr($settings['container'][$size]); ?>">
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="ecf-card ecf-spacing-preview-card"
                     data-ecf-spacing-preview
                     data-steps="<?php echo esc_attr(wp_json_encode($settings['spacing']['steps'])); ?>"
                     data-active-step="<?php echo esc_attr($settings['spacing']['base_index']); ?>"
                     data-preview-label-min="<?php echo esc_attr($this->t('Minimum', 'Minimum')); ?>"
                     data-preview-label-max="<?php echo esc_attr($this->t('Maximum', 'Maximum')); ?>">
                    <div class="ecf-spacing-preview-header">
                        <div>
                            <h2><?php echo esc_html($this->t('Live Spacing Preview', 'Live-Abstandsvorschau')); ?></h2>
                            <p><?php echo esc_html($this->t('Preview of your generated spacing tokens.', 'Vorschau deiner generierten Abstands-Tokens.')); ?></p>
                        </div>
                    </div>
                    <div id="ecf-spacing-steps-container">
                        <?php foreach ($settings['spacing']['steps'] as $step): ?>
                        <input type="hidden" class="ecf-spacing-step-input" name="<?php echo esc_attr($this->option_name); ?>[spacing][steps][]" value="<?php echo esc_attr($step); ?>">
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-step-controls ecf-step-controls--top">
                        <button type="button" class="ecf-step-btn ecf-spacing-step-btn" data-ecf-spacing-add="smaller" title="<?php echo esc_attr($this->t('Add smaller step', 'Kleinere Stufe hinzufügen')); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-spacing-step-btn" data-ecf-spacing-remove="smaller" title="<?php echo esc_attr($this->t('Remove smallest step', 'Kleinste Stufe entfernen')); ?>">−</button>
                    </div>
                    <div class="ecf-spacing-preview-list" data-ecf-spacing-preview-list>
                        <?php
                        $max_val = max(array_column($spacing_preview, 'max'));
                        foreach ($spacing_preview as $item):
                            $bar_pct = $max_val > 0 ? round(($item['max'] / $max_val) * 100, 1) : 0;
                        ?>
                        <div class="ecf-space-row<?php echo $item['is_base'] ? ' is-base' : ''; ?>" data-ecf-space-step="<?php echo esc_attr($item['step']); ?>">
                            <div class="ecf-space-row__token"><?php echo esc_html($item['token']); ?><span class="ecf-copy-pill" data-copy="<?php echo esc_attr($item['token']); ?>"><?php echo esc_html($this->t('Copy', 'Kopieren')); ?></span></div>
                            <div class="ecf-space-row__meta">
                                <div><span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html($this->t('Minimum', 'Minimum')); ?></span><strong><?php echo esc_html($item['min_px']); ?>px</strong></div>
                                <div><span><i class="dashicons dashicons-desktop"></i><?php echo esc_html($this->t('Maximum', 'Maximum')); ?></span><strong><?php echo esc_html($item['max_px']); ?>px</strong></div>
                            </div>
                            <div class="ecf-space-row__bar">
                                <div class="ecf-space-row__bar-fill" style="width:<?php echo esc_attr($bar_pct); ?>%;height:<?php echo esc_attr(min(40, max(4, round($item['max'])))); ?>px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-step-controls ecf-step-controls--bottom">
                        <button type="button" class="ecf-step-btn ecf-spacing-step-btn" data-ecf-spacing-add="larger" title="<?php echo esc_attr($this->t('Add larger step', 'Größere Stufe hinzufügen')); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-spacing-step-btn" data-ecf-spacing-remove="larger" title="<?php echo esc_attr($this->t('Remove largest step', 'Größte Stufe entfernen')); ?>">−</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_sync_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="sync">
            <div class="ecf-grid">
                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Native Elementor Sync', 'Nativer Elementor Sync')); ?></h2>
                    <p class="ecf-sync-status">
                        <?php
                        echo esc_html(
                            sprintf(
                                $this->t(
                                    'Currently found in Elementor: %1$d ECF variables and %2$d Global Classes.',
                                    'Aktuell in Elementor gefunden: %1$d ECF-Variablen und %2$d globale Klassen.'
                                ),
                                $cleanup_variable_count,
                                $cleanup_class_count
                            )
                        );
                        ?>
                    </p>
                    <?php if ($show_elementor_status_cards): ?>
                        <div class="ecf-class-limit-card ecf-class-limit-card--compact ecf-class-limit-card--<?php echo esc_attr($elementor_class_limit_status); ?>" data-ecf-class-usage-card="compact" data-ecf-class-limit="<?php echo esc_attr((string) $elementor_class_limit); ?>">
                            <strong><?php echo esc_html($this->t('Elementor Global Classes', 'Elementor Globale Klassen')); ?></strong>
                            <p>
                                <?php if ($this->is_backend_german()): ?>
                                    Elementor nutzt aktuell
                                    <span class="ecf-total-global-classes-compact"><?php echo esc_html((string) $elementor_total_class_count); ?></span>
                                    von
                                    <span class="ecf-limit-global-classes-compact"><?php echo esc_html((string) $elementor_class_limit); ?></span>
                                    globale Klassen. Neue Klassen können nur angelegt werden, solange noch freie Plätze vorhanden sind.
                                <?php else: ?>
                                    Elementor currently uses
                                    <span class="ecf-total-global-classes-compact"><?php echo esc_html((string) $elementor_total_class_count); ?></span>
                                    of
                                    <span class="ecf-limit-global-classes-compact"><?php echo esc_html((string) $elementor_class_limit); ?></span>
                                    Global Classes. New classes can only be created while free slots remain.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <p style="color:#9ca3af;font-size:13px;margin:0 0 12px;"><?php echo wp_kses($this->t('Works in <strong>merge mode</strong> — ECF adds to existing Elementor variables and Global Classes without overwriting.', 'Arbeitet im <strong>Merge-Modus</strong> — ECF ergänzt bestehende Elementor-Variablen und globale Klassen ohne Überschreiben.'), ['strong' => []]); ?></p>
                    <p style="color:#6b7280;font-size:13px;margin:0 0 16px;"><?php echo esc_html($this->t('Synced:', 'Synchronisiert:')); ?>
                        <br>• <?php echo esc_html($this->t('Variables', 'Variablen')); ?>: <code>ecf-color-*</code>, <code>ecf-space-*</code>, <code>ecf-radius-*</code>, <code>ecf-text-*</code>
                        <br>• <?php echo esc_html($this->t('Global Classes', 'Globale Klassen')); ?>: <?php echo esc_html($this->t('selected starter classes and selected utility classes', 'ausgewählte Starter-Klassen und ausgewählte Utility-Klassen')); ?>
                    </p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=ecf_native_sync')); ?>">
                            <?php wp_nonce_field('ecf_native_sync'); ?>
                            <input type="hidden" name="action" value="ecf_native_sync">
                            <button type="submit" class="ecf-btn ecf-btn--primary"><span class="dashicons dashicons-update" aria-hidden="true"></span><span><?php echo esc_html($this->t('Sync to Elementor (Merge)', 'Mit Elementor synchronisieren (Merge)')); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf($this->t('Do you really want to remove %1$d ECF Global Classes from Elementor so they can be synced again as empty classes?', 'Möchtest du wirklich %1$d ECF-Klassen aus Elementor entfernen, damit sie danach wieder als leere Klassen synchronisiert werden können?'), $cleanup_class_count)); ?>');">
                            <?php wp_nonce_field('ecf_class_cleanup'); ?>
                            <input type="hidden" name="action" value="ecf_class_cleanup">
                            <button type="submit" class="ecf-btn ecf-btn--ghost" <?php disabled($cleanup_class_count === 0); ?> title="<?php echo esc_attr($cleanup_class_count === 0 ? $this->t('No ECF classes found in Elementor.', 'Keine ECF-Klassen in Elementor gefunden.') : sprintf($this->t('Removes %1$d ECF classes from Elementor without touching variables.', 'Entfernt %1$d ECF-Klassen aus Elementor, ohne Variablen anzufassen.'), $cleanup_class_count)); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span><?php echo esc_html($this->t('Cleanup ECF Classes', 'ECF-Klassen bereinigen')); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf($this->t('Do you really want to remove %1$d ECF variables and %2$d Global Classes from Elementor?', 'Möchtest du wirklich %1$d ECF-Variablen und %2$d globale Klassen aus Elementor entfernen?'), $cleanup_variable_count, $cleanup_class_count)); ?>');">
                            <?php wp_nonce_field('ecf_native_cleanup'); ?>
                            <input type="hidden" name="action" value="ecf_native_cleanup">
                            <button type="submit" class="ecf-btn ecf-btn--danger" <?php disabled($cleanup_total_count === 0); ?> title="<?php echo esc_attr($cleanup_total_count === 0 ? $this->t('No ECF variables or classes found in Elementor.', 'Keine ECF-Variablen oder Klassen in Elementor gefunden.') : sprintf($this->t('Removes %1$d variables and %2$d classes from Elementor.', 'Entfernt %1$d Variablen und %2$d Klassen aus Elementor.'), $cleanup_variable_count, $cleanup_class_count)); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span><?php echo esc_html($this->t('Cleanup ECF from Elementor', 'ECF aus Elementor entfernen')); ?></span></button>
                        </form>
                    </div>
                </div>

                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Export / Import', 'Export / Import')); ?></h2>
                    <p style="color:#9ca3af;font-size:13px;margin:0 0 16px;"><?php echo esc_html($this->t('Export settings as JSON or import from another installation.', 'Einstellungen als JSON exportieren oder von einer anderen Installation importieren.')); ?></p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('ecf_export'); ?>
                            <input type="hidden" name="action" value="ecf_export">
                            <button type="submit" class="ecf-btn ecf-btn--ghost"><span class="dashicons dashicons-download" aria-hidden="true"></span><span><?php echo esc_html($this->t('Export JSON', 'JSON exportieren')); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px;">
                            <?php wp_nonce_field('ecf_import'); ?>
                            <input type="hidden" name="action" value="ecf_import">
                            <input type="file" name="ecf_import_file" accept=".json" required style="color:#9ca3af;font-size:13px;">
                            <button type="submit" class="ecf-btn ecf-btn--ghost"><span class="dashicons dashicons-upload" aria-hidden="true"></span><span><?php echo esc_html($this->t('Import', 'Importieren')); ?></span></button>
                        </form>
                    </div>
                </div>

                <div class="ecf-card">
                    <h2><?php echo esc_html($this->t('Elementor Editor Panel', 'Elementor Editor Panel')); ?></h2>
                    <p style="color:#9ca3af;font-size:13px;"><?php echo wp_kses($this->t('In the Elementor editor, find the <strong>ECF Framework</strong> section under the <strong>Advanced</strong> tab of any element.', 'Im Elementor-Editor findest du im Tab <strong>Advanced</strong> jedes Elements den Bereich <strong>ECF Framework</strong>.'), ['strong' => []]); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_changelog_panel($changelog_entries) {
        ?>
        <div class="ecf-panel" data-panel="changelog">
            <div class="ecf-grid ecf-grid--single">
                <div class="ecf-card">
                    <div class="ecf-changelog-header">
                        <div>
                            <h2><?php echo esc_html($this->t('Plugin Changelog', 'Plugin-Changelog')); ?></h2>
                            <p><?php echo esc_html($this->t('All relevant plugin changes are documented here in the current backend language.', 'Alle relevanten Plugin-Änderungen werden hier in der aktuellen Backend-Sprache dokumentiert.')); ?></p>
                        </div>
                    </div>
                    <?php if (empty($changelog_entries)): ?>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('No changelog entries found.', 'Keine Changelog-Einträge gefunden.')); ?></p>
                    <?php else: ?>
                        <div class="ecf-changelog-list">
                            <?php foreach ($changelog_entries as $entry): ?>
                                <section class="ecf-changelog-entry">
                                    <h3><?php echo esc_html($entry['heading']); ?></h3>
                                    <?php foreach (($entry['sections'] ?? []) as $section_title => $items): ?>
                                        <div class="ecf-changelog-section">
                                            <strong class="ecf-changelog-badge ecf-changelog-badge--<?php echo esc_attr($this->changelog_section_badge_type($section_title)); ?>"><?php echo esc_html($section_title); ?></strong>
                                            <ul>
                                                <?php foreach ($items as $item): ?>
                                                    <li><?php echo esc_html($item); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_changelog_modal($changelog_entries) {
        ?>
        <div class="ecf-modal" data-ecf-changelog-modal hidden>
            <div class="ecf-modal__backdrop" data-ecf-close-changelog-modal></div>
            <div class="ecf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ecf-changelog-modal-title">
                <div class="ecf-modal__header">
                    <div>
                        <h2 id="ecf-changelog-modal-title"><?php echo esc_html($this->t('Version Changelog', 'Versions-Changelog')); ?></h2>
                        <p><?php echo esc_html($this->t('Quick view of the latest documented plugin changes.', 'Schnellansicht der zuletzt dokumentierten Plugin-Änderungen.')); ?></p>
                    </div>
                    <button type="button" class="ecf-modal__close" data-ecf-close-changelog-modal aria-label="<?php echo esc_attr($this->t('Close', 'Schließen')); ?>">×</button>
                </div>
                <div class="ecf-modal__body">
                    <?php if (empty($changelog_entries)): ?>
                        <p class="ecf-muted-copy"><?php echo esc_html($this->t('No changelog entries found.', 'Keine Changelog-Einträge gefunden.')); ?></p>
                    <?php else: ?>
                        <div class="ecf-changelog-list">
                            <?php foreach ($changelog_entries as $entry): ?>
                                <section class="ecf-changelog-entry">
                                    <h3><?php echo esc_html($entry['heading']); ?></h3>
                                    <?php foreach (($entry['sections'] ?? []) as $section_title => $items): ?>
                                        <div class="ecf-changelog-section">
                                            <strong class="ecf-changelog-badge ecf-changelog-badge--<?php echo esc_attr($this->changelog_section_badge_type($section_title)); ?>"><?php echo esc_html($section_title); ?></strong>
                                            <ul>
                                                <?php foreach ($items as $item): ?>
                                                    <li><?php echo esc_html($item); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_row_templates($starter_class_categories) {
        ?>
        <script type="text/template" id="ecf-row-template-color">
            <div class="ecf-row ecf-row--color">
                <input type="text" class="ecf-color-field" value="#000000" placeholder="#000000" />
                <input type="hidden" class="ecf-color-value-input" name="__VALUE__" value="#000000" />
                <input type="text" name="__NAME__" value="" placeholder="<?php echo esc_attr($this->t('class name', 'Klassenname')); ?>" />
                <input type="text" class="ecf-color-value-display" value="#000000" spellcheck="false" autocomplete="off" />
                <select class="ecf-color-format-select" name="__FORMAT__">
                    <option value="hex">HEX</option>
                    <option value="hexa">HEXA</option>
                    <option value="rgb">RGB</option>
                    <option value="rgba">RGBA</option>
                    <option value="hsl">HSL</option>
                    <option value="hsla">HSLA</option>
                </select>
                <button type="button" class="button ecf-remove-row">×</button>
            </div>
        </script>

        <script type="text/template" id="ecf-row-template-minmax">
            <div class="ecf-row ecf-row--minmax">
                <input type="text" name="__NAME__" value="" placeholder="<?php echo esc_attr($this->t('class name', 'Klassenname')); ?>" />
                <input type="text" name="__MIN__" value="" placeholder="min" />
                <input type="text" name="__MAX__" value="" placeholder="max" />
                <button type="button" class="button ecf-remove-row">×</button>
            </div>
        </script>

        <script type="text/template" id="ecf-row-template-default">
            <div class="ecf-row">
                <input type="text" name="__NAME__" value="" placeholder="<?php echo esc_attr($this->t('class name', 'Klassenname')); ?>" />
                <input type="text" name="__VALUE__" value="" placeholder="value" />
                <button type="button" class="button ecf-remove-row">×</button>
            </div>
        </script>
        <script type="text/template" id="ecf-starter-custom-row-template">
            <div class="ecf-starter-custom-row">
                <label class="ecf-form-grid__checkbox">
                    <input type="checkbox" name="__ENABLED__" value="1" class="ecf-custom-starter-enabled" checked>
                    <span><?php echo esc_html($this->t('Active', 'Aktiv')); ?></span>
                </label>
                <input type="text" name="__NAME__" value="" placeholder="ecf-banner" class="ecf-custom-starter-name">
                <select name="__CATEGORY__" class="ecf-custom-starter-category">
                    <?php foreach ($starter_class_categories as $category_key => $category_label): ?>
                        <?php if ($category_key === 'all') continue; ?>
                        <option value="<?php echo esc_attr($category_key); ?>"><?php echo esc_html($category_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </script>
        <?php
    }
}
