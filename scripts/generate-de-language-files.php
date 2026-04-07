<?php

$root = dirname(__DIR__);
$translations_file = $root . '/includes/ecf-runtime-de-translations.php';
$po_file = $root . '/languages/ecf-framework-de_DE.po';
$mo_file = $root . '/languages/ecf-framework-de_DE.mo';

if (!file_exists($translations_file)) {
    fwrite(STDERR, "Missing translations file.\n");
    exit(1);
}

$translations = require $translations_file;
if (!is_array($translations) || $translations === []) {
    fwrite(STDERR, "No runtime translations available.\n");
    exit(1);
}

$escape = static function ($value) {
    return str_replace(
        ["\\", "\"", "\n", "\r", "\t"],
        ["\\\\", "\\\"", "\\n", '', "\\t"],
        (string) $value
    );
};

$po = [];
$po[] = 'msgid ""';
$po[] = 'msgstr ""';
$po[] = '"Project-Id-Version: ECF Framework\n"';
$po[] = '"MIME-Version: 1.0\n"';
$po[] = '"Content-Type: text/plain; charset=UTF-8\n"';
$po[] = '"Content-Transfer-Encoding: 8bit\n"';
$po[] = '"Language: de_DE\n"';
$po[] = '"Plural-Forms: nplurals=2; plural=(n != 1);\n"';
$po[] = '';

ksort($translations);
foreach ($translations as $en => $de) {
    $po[] = 'msgid "' . $escape($en) . '"';
    $po[] = 'msgstr "' . $escape($de) . '"';
    $po[] = '';
}

file_put_contents($po_file, implode("\n", $po));

$cmd = 'msgfmt ' . escapeshellarg($po_file) . ' -o ' . escapeshellarg($mo_file) . ' 2>&1';
$output = [];
$code = 0;
exec($cmd, $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "msgfmt failed:\n" . implode("\n", $output) . "\n");
    exit($code);
}

echo "Generated de_DE language files.\n";
