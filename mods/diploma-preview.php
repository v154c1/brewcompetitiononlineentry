<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');
require_once(INCLUDES . 'url_variables.inc.php');
require_once(MODS . 'cech_common.php');

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$brew_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$brew_id) {
    echo "<h2>Missing entry ID.</h2>";
    die;
}

$brewingTable = $prefix . "brewing";
$scoresTable  = $prefix . "judging_scores";
$brewersTable = $prefix . "brewer";

$db = new MysqliDb($connection);
$db->join($scoresTable  . " score",  "score.eid = brewing.id",          "LEFT");
$db->join($brewersTable . " brewer", "brewer.id = brewing.brewBrewerID", "LEFT");
$db->where('brewing.id', $brew_id);
$entry = $db->getOne($brewingTable . " brewing",
    'brewing.id, brewBrewerFirstName, brewBrewerLastName, brewCoBrewer, brewName, brewStyle, score.scoreEntry, score.scorePlace');

if (!$entry) {
    echo "<h2>Entry not found.</h2>";
    die;
}

$diploma_config = isset($cech_config['diploma']) ? $cech_config['diploma'] : [];
$category_names = isset($diploma_config['category_names']) ? $diploma_config['category_names'] : [];
$templates      = isset($diploma_config['templates'])      ? $diploma_config['templates']      : [];

function matches_template($t, $score_place, $score_entry)
{
    $place = isset($t['place']) ? $t['place'] : '';
    $color = isset($t['color']) ? $t['color'] : '';

    $place_ok = true;
    if ($place === '1')    $place_ok = (string)$score_place === '1';
    elseif ($place === '2')    $place_ok = (string)$score_place === '2';
    elseif ($place === '3')    $place_ok = (string)$score_place === '3';
    elseif ($place === 'any')  $place_ok = !empty($score_place);
    elseif ($place === 'none') $place_ok = empty($score_place);

    $s = (float)$score_entry;
    $color_ok = true;
    if ($color === 'gold')   $color_ok = $s >= 45;
    elseif ($color === 'silver') $color_ok = $s >= 41 && $s < 45;
    elseif ($color === 'bronze') $color_ok = $s >= 36 && $s < 41;

    return $place_ok && $color_ok;
}

$matched = null;
foreach ($templates as $t) {
    if (matches_template($t, $entry['scorePlace'], $entry['scoreEntry'])) {
        $matched = $t;
        break;
    }
}

if (!$matched) {
    echo "<h2>No matching diploma template for this entry.</h2>";
    echo "<p><a href='page.php'>Back</a></p>";
    die;
}

$boxes       = isset($matched['boxes'])      ? $matched['boxes']      : [];
$bg_filename = isset($matched['background']) ? $matched['background'] : '';
$bg_url      = $bg_filename ? $base_url . 'user_images/' . rawurlencode($bg_filename) : '';

function resolve_template($template, $vars)
{
    foreach ($vars as $key => $val) {
        $template = str_replace('${' . $key . '}', $val, $template);
    }
    return $template;
}

$brewer = html_entity_decode($entry['brewBrewerFirstName']) . ' ' . html_entity_decode($entry['brewBrewerLastName']);
if (!empty($entry['brewCoBrewer'])) {
    $brewer .= ', ' . html_entity_decode($entry['brewCoBrewer']);
}

$vars = [
    'brewer'   => $brewer,
    'name'     => html_entity_decode($entry['brewName']),
    'place'    => $entry['scorePlace'],
    'score'    => $entry['scoreEntry'] !== null ? (int)$entry['scoreEntry'] * 2 : '',
    'category' => isset($category_names[$entry['brewStyle']]) ? $category_names[$entry['brewStyle']] : html_entity_decode($entry['brewStyle']),
];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Diplom</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #888; }
        .no-print {
            padding: 10px 20px;
            background: #333;
        }
        .no-print a { color: #fff; margin-right: 10px; }
        #diploma {
            position: relative;
            width: 210mm;
            height: 297mm;
            margin: 20px auto;
            background-color: #fff;
            background-size: cover;
            background-position: center;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .diploma-box {
            position: absolute;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            .no-print { display: none; }
            body { background: none; }
            #diploma { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="javascript:window.print()">Print</a>
    <a href="page.php">Back</a>
</div>

<div id="diploma" style="<?php echo $bg_url ? 'background-image:url(' . htmlspecialchars($bg_url) . ')' : ''; ?>">
    <?php foreach ($boxes as $box):
        $text     = resolve_template($box['template'] ?? '', $vars);

        $valign = $box['valign'] ?? 'middle';
        $align  = $box['align'] ?? 'center';
        $justify = 'center';
        if ($valign === 'top') $justify = 'flex-start';
        if ($valign === 'bottom') $justify = 'flex-end';
        $items = 'center';
        if ($align === 'left') $items = 'flex-start';
        if ($align === 'right') $items = 'flex-end';

        $style    = sprintf(
            'left:%.4f%%;top:%.4f%%;width:%.4f%%;height:%.4f%%;font-size:%dpt;color:%s;text-align:%s;justify-content:%s;align-items:%s;',
            (float)($box['x']        ?? 0),
            (float)($box['y']        ?? 0),
            (float)($box['width']    ?? 60),
            (float)($box['height']   ?? 5),
            (int)  ($box['fontSize'] ?? 18),
            htmlspecialchars($box['color'] ?? '#000000'),
            htmlspecialchars($align),
            $justify,
            $items
        );
    ?>
        <div class="diploma-box" style="<?php echo $style; ?>">
            <?php echo htmlspecialchars($text); ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
