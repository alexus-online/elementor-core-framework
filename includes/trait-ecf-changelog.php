<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Changelog_Trait {
    private function parse_changelog_heading($heading) {
        $heading = trim((string) $heading);
        if ($heading === '') {
            return ['version' => '', 'date' => ''];
        }

        if (preg_match('/^(?:Version\s+)?([0-9]+\.[0-9]+\.[0-9]+)\s*[-(]\s*([0-9]{4}-[0-9]{2}-[0-9]{2})\)?$/i', $heading, $m)) {
            return ['version' => $m[1], 'date' => $m[2]];
        }

        if (preg_match('/^(?:Version\s+)?([0-9]+\.[0-9]+\.[0-9]+)$/i', $heading, $m)) {
            return ['version' => $m[1], 'date' => ''];
        }

        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})$/', $heading, $m)) {
            return ['version' => '', 'date' => $m[1]];
        }

        return ['version' => '', 'date' => $heading];
    }

    private function format_changelog_heading($entry) {
        $version = trim((string) ($entry['version'] ?? ''));
        $date = trim((string) ($entry['date'] ?? ''));

        if ($version !== '' && $date !== '') {
            return sprintf('%s (%s)', $version, $date);
        }
        if ($version !== '') {
            return $version;
        }
        return $date;
    }

    private function localize_changelog_section_title($title) {
        $title = trim((string) $title);
        $map = [
            'Added' => ['en' => 'Added', 'de' => 'Hinzugefügt'],
            'Feature' => ['en' => 'Feature', 'de' => 'Feature'],
            'Changed' => ['en' => 'Changed', 'de' => 'Geändert'],
            'Change' => ['en' => 'Change', 'de' => 'Geändert'],
            'Fixed' => ['en' => 'Fixed', 'de' => 'Behoben'],
            'Removed' => ['en' => 'Removed', 'de' => 'Entfernt'],
            'Security' => ['en' => 'Security', 'de' => 'Sicherheit'],
            'UX' => ['en' => 'UX', 'de' => 'UX'],
        ];

        if (!isset($map[$title])) {
            return $title;
        }

        return $this->is_german() ? $map[$title]['de'] : $map[$title]['en'];
    }

    private function changelog_section_badge_type($title) {
        $title = strtolower(trim((string) $title));

        $map = [
            'added' => 'feature',
            'hinzugefügt' => 'feature',
            'feature' => 'feature',
            'changed' => 'change',
            'change' => 'change',
            'geändert' => 'change',
            'fixed' => 'fix',
            'fix' => 'fix',
            'behoben' => 'fix',
            'removed' => 'removed',
            'entfernt' => 'removed',
            'security' => 'security',
            'sicherheit' => 'security',
            'ux' => 'ux',
        ];

        return $map[$title] ?? 'default';
    }

    private function get_changelog_entries() {
        $path = plugin_dir_path(ECF_FRAMEWORK_FILE) . 'CHANGELOG.md';
        if (!file_exists($path) || !is_readable($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $entries = [];
        $current_index = -1;
        $current_section = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line === '# Changelog') {
                continue;
            }

            if (strpos($line, '## ') === 0) {
                $heading = $this->parse_changelog_heading(substr($line, 3));
                $entries[] = [
                    'version' => $heading['version'],
                    'date' => $heading['date'],
                    'sections' => [],
                ];
                $current_index = count($entries) - 1;
                $current_section = '';
                continue;
            }

            if (strpos($line, '### ') === 0) {
                $current_section = trim(substr($line, 4));
                if ($current_index >= 0 && !isset($entries[$current_index]['sections'][$current_section])) {
                    $entries[$current_index]['sections'][$current_section] = [];
                }
                continue;
            }

            if (strpos($line, '- ') === 0 && $current_index >= 0 && $current_section !== '') {
                $entries[$current_index]['sections'][$current_section][] = trim(substr($line, 2));
            }
        }

        return $entries;
    }

    private function get_localized_changelog_entries() {
        $entries = $this->get_changelog_entries();
        if (empty($entries)) {
            return [];
        }

        $use_german = $this->is_german();
        $localized = [];

        foreach ($entries as $entry) {
            $localized_entry = [
                'version' => $entry['version'] ?? '',
                'date' => $entry['date'] ?? '',
                'heading' => $this->format_changelog_heading($entry),
                'sections' => [],
            ];

            foreach (($entry['sections'] ?? []) as $section_title => $items) {
                $filtered_items = [];
                foreach ($items as $index => $item) {
                    $is_german_line = ($index % 2) === 1;
                    if (($use_german && $is_german_line) || (!$use_german && !$is_german_line)) {
                        $filtered_items[] = $item;
                    }
                }

                if (empty($filtered_items)) {
                    $filtered_items = $items;
                }

                $localized_entry['sections'][$this->localize_changelog_section_title($section_title)] = $filtered_items;
            }

            if (!empty($localized_entry['sections'])) {
                $localized[] = $localized_entry;
            }
        }

        return $localized;
    }
}
