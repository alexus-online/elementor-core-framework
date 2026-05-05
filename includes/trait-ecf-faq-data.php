<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_FAQ_Data_Trait {
    /**
     * Lädt die FAQ-Inhalte aus der Sprach-JSON-Datei.
     * Pfad: assets/data/faq-{locale}.json (Fallback: faq-en.json)
     *
     * Struktur der JSON:
     *   {
     *     "categories": { "key": "Label", ... },
     *     "entries": [
     *       { "id":"...", "category":"...", "q":"...", "a":"<HTML>", "keywords":"...",
     *         "link": { "page":"...", "label":"..." } },
     *       ...
     *     ]
     *   }
     */
    private function load_faq_content(): array {
        static $cache = null;
        if ($cache !== null) return $cache;

        $suffix = 'de';
        if (method_exists($this, 'selected_interface_language')) {
            $suffix = $this->selected_interface_language() === 'en' ? 'en' : 'de';
        }

        $base = dirname(__DIR__) . '/assets/data/faq-';
        $path = $base . $suffix . '.json';
        $fallback = $base . 'en.json';
        $alt_de   = $base . 'de.json';

        $candidates = [$path, $alt_de, $fallback];
        $json = '';
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $json = (string) file_get_contents($candidate);
                if ($json !== '') break;
            }
        }

        if ($json === '') {
            $cache = ['categories' => [], 'entries' => []];
            return $cache;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $cache = ['categories' => [], 'entries' => []];
            return $cache;
        }

        $cache = [
            'categories' => is_array($decoded['categories'] ?? null) ? $decoded['categories'] : [],
            'entries'    => is_array($decoded['entries']    ?? null) ? $decoded['entries']    : [],
        ];
        return $cache;
    }

    private function faq_categories(): array {
        $content = $this->load_faq_content();
        return $content['categories'] ?? [];
    }

    private function faq_entries(): array {
        $content = $this->load_faq_content();
        return $content['entries'] ?? [];
    }
}
