<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_How_It_Works_Data_Trait {
    /**
     * Lädt die "Wie funktioniert's"-Inhalte aus der Sprach-JSON-Datei.
     * Pfad: assets/data/how-it-works-{locale}.json (Fallback: how-it-works-en.json)
     */
    private function load_how_it_works_content(): array {
        static $cache = null;
        if ($cache !== null) return $cache;

        $suffix = 'de';
        if (method_exists($this, 'selected_interface_language')) {
            $suffix = $this->selected_interface_language() === 'en' ? 'en' : 'de';
        }

        $base = dirname(__DIR__) . '/assets/data/how-it-works-';
        $candidates = [$base . $suffix . '.json', $base . 'de.json', $base . 'en.json'];
        $json = '';
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $json = (string) file_get_contents($path);
                if ($json !== '') break;
            }
        }

        if ($json === '') {
            $cache = [];
            return $cache;
        }

        $decoded = json_decode($json, true);
        $cache = is_array($decoded) ? $decoded : [];
        return $cache;
    }
}
