<?php
/**
 * Official-adjacent PO to MO compiler script using WordPress-compatible logic.
 * This script is for environments where standard gettext tools are missing.
 */

if ($argc < 3) {
    echo "Usage: php compile-mo.php <input.po> <output.mo>\n";
    exit(1);
}

$input = $argv[1];
$output = $argv[2];

if (!file_exists($input)) {
    echo "Input file missing: $input\n";
    exit(1);
}

echo "Compiling $input to $output...\n";

$data = file_get_contents($input);
$items = array();

// Extract entries properly
$parts = preg_split('/(?=msgid)/', $data);
foreach ($parts as $part) {
    if (preg_match('/msgid\s+"(.*)"\s+msgstr\s+"(.*)"/s', $part, $match)) {
        $id = $match[1];
        $str = $match[2];
        if ($id !== "" && $str !== "") {
            $items[$id] = $str;
        }
    }
}

ksort($items);

$num_entries = count($items);
$originals = "";
$translations = "";
$original_offsets = array();
$translation_offsets = array();

$offset_originals = 28 + ($num_entries * 16);
foreach ($items as $id => $str) {
    $original_offsets[] = array(strlen($id), $offset_originals);
    $originals .= $id . "\0";
    $offset_originals += strlen($id) + 1;
}

$offset_translations = $offset_originals;
foreach ($items as $id => $str) {
    $translation_offsets[] = array(strlen($str), $offset_translations);
    $translations .= $str . "\0";
    $offset_translations += strlen($str) + 1;
}

// Magic number for little-endian MO
$magic = 0x950412de;
$revision = 0;
$offset_originals_table = 28;
$offset_translations_table = 28 + ($num_entries * 8);

$header = pack(
    "V7",
    $magic,
    $revision,
    $num_entries,
    $offset_originals_table,
    $offset_translations_table,
    0, // Hashing table size
    0  // Hashing table offset
);

$originals_table = "";
foreach ($original_offsets as $off) {
    $originals_table .= pack("V2", $off[0], $off[1]);
}

$translations_table = "";
foreach ($translation_offsets as $off) {
    $translations_table .= pack("V2", $off[0], $off[1]);
}

$mo_data = $header . $originals_table . $translations_table . $originals . $translations;

if (file_put_contents($output, $mo_data)) {
    echo "Successfully generated $output (" . strlen($mo_data) . " bytes)\n";
} else {
    echo "Failed to write $output\n";
    exit(1);
}
