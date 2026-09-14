<?php
/**
 * collect_sympa_mails.php
 *
 * Agrège plusieurs listes Sympa via RSS.
 * Affiche les derniers mails avec contenu quand accessible.
 * Permet un export CSV et un export SQL INSERT.
 *
 * Aucune connexion DB ici : ce fichier ne fait que générer des exports.
 */

/**
 * Cookie Sympa récupéré après avoir cliqué sur :
 * "Je ne suis pas un spammeur"
 */
$sympaCookie = 'sympa_session=bb28c1a5c25296d90caafbe2714637058c';

$defaultLists = [
    'direction-composantes-formation',
    'responsables-formations',
    'responsables-formations-master',
    'monmaster-gestionnaires',
    'monmaster-consultants',
];

$availableLists = array_values(array_unique($defaultLists));

$rssBaseUrl = 'https://listes.univ-eiffel.fr/wws/rss/latest_arc';
$defaultDays = 10;
$defaultLimit = 5;

$isFetchRequest = array_key_exists('fetch', $_GET);
$requestedLists = array_key_exists('lists', $_GET)
    ? $_GET['lists']
    : ($isFetchRequest ? [] : $availableLists);

if (!is_array($requestedLists)) {
    $requestedLists = [$requestedLists];
}

$lists = array_values(array_intersect(
    $availableLists,
    array_map('strval', $requestedLists)
));

if (!$isFetchRequest && $lists === []) {
    $lists = $availableLists;
}

$days = filter_input(INPUT_GET, 'days', FILTER_VALIDATE_INT, [
    'options' => [
        'default' => $defaultDays,
        'min_range' => 1,
        'max_range' => 365,
    ],
]);

$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, [
    'options' => [
        'default' => $defaultLimit,
        'min_range' => 1,
        'max_range' => 100,
    ],
]);

if ($days === false || $days === null) {
    $days = $defaultDays;
}

if ($limit === false || $limit === null) {
    $limit = $defaultLimit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function sqlValue(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return 'NULL';
    }

    return "'" . str_replace("'", "''", $value) . "'";
}

function getUrlContent(string $url, string $cookie = ''): string
{
    $headers = [
        'User-Agent: Mozilla/5.0',
    ];

    if ($cookie !== '') {
        $headers[] = 'Cookie: ' . $cookie;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 15,
        ],
    ]);

    $html = @file_get_contents($url, false, $context);

    return $html === false ? '' : $html;
}

function extractMailBody(string $html): string
{
    $startMarker = 'X-Body-of-Message';
    $endMarker = 'X-Body-of-Message-End';

    $start = stripos($html, $startMarker);
    if ($start === false) {
        return '';
    }

    $startEnd = strpos($html, '-->', $start);
    if ($startEnd === false) {
        return '';
    }

    $startEnd += 3;

    $end = stripos($html, $endMarker, $startEnd);
    if ($end === false) {
        return '';
    }

    $endStart = strrpos(substr($html, 0, $end), '<!--');
    if ($endStart === false) {
        $endStart = $end;
    }

    return substr($html, $startEnd, $endStart - $startEnd);
}

function buildAbsoluteUrl(string $href, string $mailUrl): string
{
    $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    if ($href === '' || preg_match('~^\s*javascript:~i', $href)) {
        return '#';
    }

    if (preg_match('~^(mailto:|tel:|#)~i', $href)) {
        return $href;
    }

    if (preg_match('~^https?://~i', $href)) {
        return str_replace('/wws/arcsearch_id/', '/wws/arc/', $href);
    }

    if (str_starts_with($href, '//')) {
        return 'https:' . str_replace('/wws/arcsearch_id/', '/wws/arc/', $href);
    }

    if (str_starts_with($href, '/')) {
        $href = 'https://listes.univ-eiffel.fr' . $href;
    } else {
        $href = rtrim(dirname($mailUrl), '/') . '/' . ltrim($href, '/');
    }

    return str_replace('/wws/arcsearch_id/', '/wws/arc/', $href);
}

function domInnerHTML(DOMNode $node): string
{
    $html = '';

    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }

    return $html;
}

function unwrapNode(DOMNode $node): void
{
    $parent = $node->parentNode;

    if (!$parent) {
        return;
    }

    while ($node->firstChild) {
        $parent->insertBefore($node->firstChild, $node);
    }

    $parent->removeChild($node);
}

function isEmptyHtmlNode(DOMNode $node): bool
{
    if (!$node instanceof DOMElement) {
        return false;
    }

    if ($node->tagName === 'br') {
        return false;
    }

    if ($node->getElementsByTagName('a')->length > 0) {
        return false;
    }

    if ($node->getElementsByTagName('img')->length > 0) {
        return false;
    }

    $text = html_entity_decode($node->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text);
    $text = preg_replace('/\s+/u', '', $text);

    return $text === '';
}

function cleanMailHtml(string $html, string $mailUrl): string
{
    if (trim($html) === '') {
        return '';
    }

    // Coupe les anciens mails cités après un séparateur horizontal.
    $html = preg_split('/<hr\b[^>]*>/i', $html)[0] ?? $html;

    // Supprime les blocs dangereux ou inutiles.
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
    $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
    $html = preg_replace('/<embed\b[^>]*>.*?<\/embed>/is', '', $html);

    libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div id="__root__">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    libxml_clear_errors();

    $root = $dom->getElementById('__root__');
    if (!$root) {
        return trim($html);
    }

    $xpath = new DOMXPath($dom);

    // Supprime les commentaires.
    foreach ($xpath->query('//comment()') as $comment) {
        $comment->parentNode?->removeChild($comment);
    }

    // Supprime les images sans src, souvent présentes dans les signatures.
    foreach ($xpath->query('//img[not(@src) or normalize-space(@src) = ""]') as $img) {
        $img->parentNode?->removeChild($img);
    }

    // Corrige et sécurise les liens.
    foreach ($xpath->query('//a') as $a) {
        if (!$a instanceof DOMElement) {
            continue;
        }

        $href = $a->getAttribute('href');
        $text = trim(html_entity_decode($a->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        // Cas fréquent : <a rel="nofollow">https://...</a> sans href.
        if ($href === '' && preg_match('~^https?://\S+$~i', $text)) {
            $href = $text;
        }

        // Supprime les liens totalement vides.
        if ($href === '' && $text === '') {
            $a->parentNode?->removeChild($a);
            continue;
        }

        // Si un <a> n'a pas d'URL exploitable, on garde son texte sans balise.
        if ($href === '') {
            unwrapNode($a);
            continue;
        }

        $a->setAttribute('href', buildAbsoluteUrl($href, $mailUrl));
        $a->setAttribute('target', '_blank');
        $a->setAttribute('rel', 'noopener noreferrer');
    }

    // Nettoie les attributs partout.
    foreach ($xpath->query('//*') as $el) {
        if (!$el instanceof DOMElement) {
            continue;
        }

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);

            if (str_starts_with($name, 'on')) {
                $el->removeAttribute($attr->name);
                continue;
            }

            $allowed = [];

            if ($el->tagName === 'a') {
                $allowed = ['href', 'target', 'rel'];
            }

            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
            }
        }
    }

    // Déplie les balises de présentation inutiles.
    foreach (['span', 'font'] as $tag) {
        $nodes = iterator_to_array($dom->getElementsByTagName($tag));
        foreach (array_reverse($nodes) as $node) {
            unwrapNode($node);
        }
    }

    // Transforme les tables de signature en contenu simple.
    foreach (['table', 'thead', 'tbody', 'tr', 'td', 'th'] as $tag) {
        $nodes = iterator_to_array($dom->getElementsByTagName($tag));
        foreach (array_reverse($nodes) as $node) {
            unwrapNode($node);
        }
    }

    // Supprime récursivement les nœuds vides.
    do {
        $removed = false;

        foreach (['p', 'div', 'ul', 'ol', 'li', 'blockquote', 'strong', 'b', 'em', 'i', 'u'] as $tag) {
            $nodes = iterator_to_array($dom->getElementsByTagName($tag));

            foreach (array_reverse($nodes) as $node) {
                if (isEmptyHtmlNode($node) && $node->parentNode) {
                    $node->parentNode->removeChild($node);
                    $removed = true;
                }
            }
        }
    } while ($removed);

    $html = domInnerHTML($root);

    // Normalisation légère.
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $html = str_replace("\xc2\xa0", ' ', $html);
    $html = preg_replace("/\r\n|\r/", "\n", $html);
    $html = preg_replace('/[ \t]{2,}/', ' ', $html);
    $html = preg_replace('/>\s+</', '><', $html);
    $html = preg_replace("/\n{3,}/", "\n\n", $html);

    return trim($html);
}

function getMailContent(string $url, string $cookie = ''): string
{
    $html = getUrlContent($url, $cookie);

    if ($html === '') {
        return '';
    }

    if (stripos($html, 'Je ne suis pas un spammeur') !== false) {
        return '<p><em>Contenu protégé par l’anti-spam Sympa.</em></p>';
    }

    $body = extractMailBody($html);

    if ($body === '') {
        return '';
    }

    return cleanMailHtml($body, $url);
}

function parseMailTitle(string $title): array
{
    $objet = trim($title);
    $sender = '';

    if (preg_match(
        '/^(.*?)\s-\s([a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,})$/i',
        $title,
        $matches
    )) {
        $objet = trim($matches[1]);
        $sender = trim($matches[2]);
    }

    $objet = preg_replace('/^\[[^\]]+\]\s*/', '', $objet);

    return [
        'objet' => $objet,
        'sender' => $sender,
    ];
}

function htmlToPlainText(string $html): string
{
    $text = $html;

    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = preg_replace('/<\/p>/i', "\n\n", $text);
    $text = preg_replace('/<\/div>/i', "\n", $text);
    $text = preg_replace('/<\/li>/i', "\n", $text);
    $text = preg_replace('/<\/ul>/i', "\n", $text);
    $text = preg_replace('/<\/ol>/i', "\n", $text);

    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text);
    $text = preg_replace("/\r\n|\r/", "\n", $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/^[ \t]+|[ \t]+$/m', '', $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
}

function exportCsv(array $mails): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mails_sympa.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'date',
        'liste',
        'objet',
        'expediteur',
        'lien',
        'contenu',
    ], ';');

    foreach ($mails as $mail) {
        fputcsv($output, [
            $mail['date_sql'] ?? '',
            $mail['list'] ?? '',
            $mail['objet'] ?? '',
            $mail['sender'] ?? '',
            $mail['link'] ?? '',
            htmlToPlainText($mail['content'] ?? ''),
        ], ';');
    }

    fclose($output);
    exit;
}

function exportSql(array $mails): void
{
    header('Content-Type: text/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="mails_sympa.sql"');

    echo "-- Export Sympa vers table mails\n";
    echo "-- Colonnes : objet, date, sender, content, categorie_id\n\n";

    foreach ($mails as $mail) {
        echo "INSERT INTO mails (objet, date, sender, content, categorie_id) VALUES (\n";
        echo "    " . sqlValue($mail['objet'] ?? '') . ",\n";
        echo "    " . sqlValue($mail['date_sql'] ?? date('Y-m-d H:i:s')) . ",\n";
        echo "    " . sqlValue($mail['sender'] ?? '') . ",\n";
        echo "    " . sqlValue($mail['content'] ?? '') . ",\n";
        echo "    1\n";
        echo ");\n\n";
    }

    exit;
}

function fetchMails(array $lists, string $rssBaseUrl, int $days, int $limit, string $cookie): array
{
    $mails = [];

    foreach ($lists as $listName) {
        $rssUrl = sprintf(
            '%s/%s?for=%d',
            $rssBaseUrl,
            rawurlencode($listName),
            $days
        );

        $rssContent = getUrlContent($rssUrl, $cookie);

        if ($rssContent === '') {
            continue;
        }

        $rss = @simplexml_load_string($rssContent);

        if ($rss === false || empty($rss->channel->item)) {
            continue;
        }

        foreach ($rss->channel->item as $item) {
            $title = trim((string) $item->title);
            $link = trim((string) $item->link);
            $pubDateRaw = trim((string) $item->pubDate);

            if ($title === '' || $link === '') {
                continue;
            }

            $mails[$link] = [
                'list' => $listName,
                'title' => $title,
                'link' => $link,
                'pubDateRaw' => $pubDateRaw,
                'timestamp' => strtotime($pubDateRaw) ?: 0,
                'content' => '',
            ];
        }
    }

    $mails = array_values($mails);

    usort($mails, static function ($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    $mails = array_slice($mails, 0, $limit);

    foreach ($mails as &$mail) {
        $parsed = parseMailTitle($mail['title'] ?? '');

        $mail['objet'] = $parsed['objet'];
        $mail['sender'] = $parsed['sender'];
        $mail['date_sql'] = !empty($mail['timestamp'])
            ? date('Y-m-d H:i:s', $mail['timestamp'])
            : date('Y-m-d H:i:s');
        $mail['content'] = getMailContent($mail['link'], $cookie);
    }

    unset($mail);

    return $mails;
}

$mails = fetchMails($lists, $rssBaseUrl, $days, $limit, $sympaCookie);

$currentQuery = [
    'lists' => $lists,
    'days' => $days,
    'limit' => $limit,
    'fetch' => 1,
];

$csvQuery = $currentQuery;
$csvQuery['csv'] = 1;

$sqlQuery = $currentQuery;
$sqlQuery['sql'] = 1;

$csvExportUrl = '?' . http_build_query($csvQuery, '', '&', PHP_QUERY_RFC3986);
$sqlExportUrl = '?' . http_build_query($sqlQuery, '', '&', PHP_QUERY_RFC3986);

if (isset($_GET['csv'])) {
    exportCsv($mails);
}

if (isset($_GET['sql'])) {
    exportSql($mails);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Agrégation RSS Sympa</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 16px;
            line-height: 1.5;
            color: #222;
            background: #f8fafc;
        }

        h1 {
            margin-bottom: 8px;
        }

        .fetch-panel {
            margin: 20px 0;
            padding: 18px;
            border: 1px solid #dbe4ef;
            border-radius: 12px;
            background: #fff;
        }

        .fetch-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 160px 160px;
            gap: 18px;
            align-items: start;
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #334155;
        }

        .list-options {
            display: grid;
            gap: 8px;
        }

        .list-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .number-input {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font: inherit;
        }

        .fetch-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .fetch-button {
            padding: 9px 14px;
            border: 0;
            border-radius: 8px;
            background: #0f766e;
            color: white;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }

        .fetch-button:hover {
            background: #115e59;
        }

        .export-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 12px 0 20px;
        }

        .export-link {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            background: #005bbb;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .export-link:hover {
            background: #004999;
        }

        .mail-list {
            list-style: none;
            padding: 0;
            margin: 24px 0;
        }

        .mail-item {
            margin-bottom: 18px;
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: white;
        }

        .mail-title {
            font-weight: bold;
            color: #005bbb;
            text-decoration: none;
            font-size: 1.05rem;
        }

        .mail-title:hover {
            text-decoration: underline;
        }

        .mail-meta {
            display: block;
            margin-top: 6px;
            color: #666;
            font-size: 0.9rem;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eef2ff;
            color: #334155;
            font-size: 0.8rem;
            margin-right: 8px;
        }

        .mail-content {
            margin-top: 14px;
            padding: 14px;
            border-left: 4px solid #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            font-size: 0.95rem;
            overflow-wrap: break-word;
        }

        .mail-content p,
        .mail-content div {
            margin: 0 0 10px;
        }

        .mail-content ul,
        .mail-content ol {
            margin-top: 8px;
            margin-bottom: 8px;
            padding-left: 24px;
        }

        .mail-content a {
            color: #005bbb;
            font-weight: 600;
        }

        .empty-content {
            color: #94a3b8;
            font-style: italic;
        }

        @media (max-width: 760px) {
            .fetch-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<h1>Derniers messages des listes Sympa</h1>

<form class="fetch-panel" method="get">
    <input type="hidden" name="fetch" value="1">

    <div class="fetch-grid">
        <div>
            <span class="field-label">Listes Sympa</span>

            <div class="list-options">
                <?php foreach ($availableLists as $listName): ?>
                    <label class="list-option">
                        <input
                                type="checkbox"
                                name="lists[]"
                                value="<?= e($listName) ?>"
                                <?= in_array($listName, $lists, true) ? 'checked' : '' ?>
                        >
                        <span><?= e($listName) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <label>
            <span class="field-label">Jours</span>
            <input
                    class="number-input"
                    type="number"
                    name="days"
                    value="<?= (int) $days ?>"
                    min="1"
                    max="365"
                    step="1"
            >
        </label>

        <label>
            <span class="field-label">Limite</span>
            <input
                    class="number-input"
                    type="number"
                    name="limit"
                    value="<?= (int) $limit ?>"
                    min="1"
                    max="100"
                    step="1"
            >
        </label>
    </div>

    <div class="fetch-actions">
        <button class="fetch-button" type="submit">Recuperer les mails</button>
    </div>
</form>

<div class="export-actions">
    <a class="export-link" href="<?= e($csvExportUrl) ?>">Exporter en CSV</a>
    <a class="export-link" href="<?= e($sqlExportUrl) ?>">Exporter en SQL</a>
</div>

<?php if (empty($mails)): ?>

    <p>Aucun message trouvé.</p>

<?php else: ?>

    <p><?= count($mails) ?> message(s) affiché(s).</p>

    <ul class="mail-list">
        <?php foreach ($mails as $mail): ?>
            <?php
            $date = !empty($mail['timestamp'])
                ? date('d/m/Y à H:i', $mail['timestamp'])
                : ($mail['pubDateRaw'] ?? '');
            ?>

            <li class="mail-item">
                <a
                        class="mail-title"
                        href="<?= e($mail['link'] ?? '') ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                >
                    <?= e($mail['objet'] ?? $mail['title'] ?? '') ?>
                </a>

                <span class="mail-meta">
                    <span class="badge"><?= e($mail['list'] ?? '') ?></span>
                    Publié le <?= e($date) ?>
                    <?php if (!empty($mail['sender'])): ?>
                        — <?= e($mail['sender']) ?>
                    <?php endif; ?>
                </span>

                <?php if (!empty($mail['content'])): ?>
                    <div class="mail-content">
                        <?= $mail['content'] ?>
                    </div>
                <?php else: ?>
                    <div class="mail-content empty-content">
                        <p><em>Contenu non récupérable.</em></p>
                        <p>Le message est peut-être protégé par Sympa ou inaccessible depuis le flux RSS.</p>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

<?php endif; ?>

</body>
</html>
