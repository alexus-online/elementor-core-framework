<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema-Version-Migration: wenn Layrix neue Default-Properties oder Klassen
 * hinzufügt (z.B. ecf-layrix-section bekommt display:flex), muss Elementors
 * Class/Variable-Registry mit dem neuen Schema synchron gebracht werden,
 * sonst sieht der User die neuen Defaults nicht.
 *
 * Statt den User manuell „Jetzt synchronisieren" klicken zu lassen, läuft
 * ein einmaliger Catch-up-Sync automatisch beim nächsten Admin-Aufruf —
 * unabhängig vom Auto-Sync-Toggle, weil hier Plugin-Code-Updates nachgezogen
 * werden, nicht User-Setting-Änderungen.
 *
 * Sicherheit: Der Sync nutzt das variant-merge-per-meta-Verfahren (seit
 * 0.6.1.2), das nur Layrix-managed Property-Keys aktualisiert. Eigene User-
 * Properties (background-color, hover etc.) und User-Variants bleiben
 * unangetastet.
 */
trait ECF_Framework_Schema_Migration_Trait {
    /**
     * Aktuelle Schema-Version. Bei jeder Änderung an default_starter_enabled_classes,
     * layrix_class_defaults_schema oder anderen sync-relevanten Defaults bumpen.
     *
     * Format: yyyy-mm-dd.N (N = Increment innerhalb desselben Tages).
     */
    private function current_schema_version(): string {
        return '2026-05-07.1';
    }

    private function synced_schema_version_option_name(): string {
        return 'ecf_layrix_synced_schema_version';
    }

    /**
     * Hook-Eintrag: wird auf admin_init registriert. Prüft ob das aktuelle
     * Plugin-Schema in Elementor synced wurde; wenn nicht, einmaligen Sync
     * im shutdown-Hook auslösen damit die Page-Response nicht blockiert.
     */
    public function maybe_run_schema_migration(): void {
        if (!is_admin()) return;
        // Nur für User mit Berechtigung — nicht jeder Subscriber soll Sync triggern
        if (!current_user_can('manage_options')) return;
        // Elementor muss verfügbar sein
        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) return;

        $current = $this->current_schema_version();
        $synced  = (string) get_option($this->synced_schema_version_option_name(), '');
        if ($current === $synced) return;

        // Erstmal optimistisch flag setzen damit kein zweiter Lauf parallel startet
        update_option($this->synced_schema_version_option_name(), $current, false);

        $plugin = $this;
        add_action('shutdown', static function () use ($plugin, $current) {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            try {
                if (method_exists($plugin, 'sync_native_variables_merge')) {
                    $plugin->sync_native_variables_merge();
                }
                if (method_exists($plugin, 'sync_native_classes_merge')) {
                    $plugin->sync_native_classes_merge();
                }
            } catch (\Throwable $e) {
                // Bei Fehler Schema-Version zurücksetzen, damit's beim nächsten Page-Load erneut versucht wird
                update_option($plugin->synced_schema_version_option_name_public(), '', false);
                error_log('Layrix schema migration failed: ' . $e->getMessage());
            }
        }, 99);
    }

    /**
     * Public Wrapper für den Shutdown-Closure (kann nicht auf private Methode zugreifen).
     */
    public function synced_schema_version_option_name_public(): string {
        return $this->synced_schema_version_option_name();
    }
}
