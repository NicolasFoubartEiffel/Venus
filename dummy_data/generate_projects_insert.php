<?php

$csvFile = __DIR__ . '/test.csv';
$creator = 'NOM Prenom';

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Impossible d'ouvrir le fichier CSV\n");
}

/**
 * Force une chaîne en UTF-8
 */
function to_utf8(?string $value): ?string {
    if ($value === null) return null;

    // Détection auto + fallback latin1
    if (!mb_detect_encoding($value, 'UTF-8', true)) {
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
    return $value;
}

$header = fgetcsv($handle, 0, ';'); // skip header

$sql = "INSERT INTO projects (title, description, link, link_2, contact, creator)\nVALUES\n";

$rows = [];

while (($row = fgetcsv($handle, 0, ';')) !== false) {

    // adapte l'ordre si besoin
    [$title, $link, $link2, $contact, $description] = array_pad($row, 5, null);

    $escape = function ($v) {
        if ($v === null) return 'NULL';

        $v = trim($v);
        if ($v === '') return 'NULL';

        $v = to_utf8($v);
        return "'" . str_replace("'", "''", $v) . "'";  
    };

    $rows[] = "(" .
        $escape($title) . ", " .
        $escape($link) . ", " .
        $escape($link2) . ", " .
        $escape($contact) . ", " .
        $escape($description) . ", " .
        "'" . $creator . "'" .
        ")";
}

fclose($handle);

echo $sql . implode(",\n", $rows) . ";\n";
