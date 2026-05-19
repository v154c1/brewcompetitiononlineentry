<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');
require_once(INCLUDES . 'url_variables.inc.php');
require_once(MODS . 'cech_common.php');
require_once(MODS . 'cech-print-common.php');

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$brewingTable = $prefix . "brewing";
$scoresTable  = $prefix . "judging_scores";
$brewersTable = $prefix . "brewer";

$diploma_config = isset($cech_config['diploma']) ? $cech_config['diploma'] : [];


$place_options = [
    ''     => 'All',
    '1'    => '1st place',
    '2'    => '2nd place',
    '3'    => '3rd place',
    'any'  => 'Any placed',
    'none' => 'Not placed',
];
$color_options = [
    ''       => 'All (diploma level)',
    'gold'   => 'Gold (≥45)',
    'silver' => 'Silver (41–44)',
    'bronze' => 'Bronze (36–40)',
];

$selected_place = isset($_GET['place']) ? $_GET['place'] : '';
$selected_color = isset($_GET['color']) ? $_GET['color'] : '';
$selected_style = isset($_GET['style']) ? $_GET['style'] : '';
$show           = isset($_GET['show']);

// Fetch distinct diploma-worthy styles for the style selector
$db_st = new MysqliDb($connection);
$db_st->join($scoresTable . " score", "score.eid = brewing.id", "LEFT");
$db_st->where('(score.scoreEntry > 35 OR (score.scorePlace IS NOT NULL AND score.scorePlace != ""))');
$db_st->orderBy('brewing.brewStyle', 'asc');
$style_rows = $db_st->get($brewingTable . " brewing", null, 'DISTINCT brewing.brewStyle');
$all_styles = array_column($style_rows, 'brewStyle');

$entries = [];
if ($show) {
    $db = new MysqliDb($connection);
    $db->join($scoresTable  . " score",  "score.eid = brewing.id",          "LEFT");
    $db->join($brewersTable . " brewer", "brewer.id = brewing.brewBrewerID", "LEFT");

    // Base: diploma-worthy entries only
    $db->where('(score.scoreEntry > 35 OR (score.scorePlace IS NOT NULL AND score.scorePlace != ""))');

    // Place filter
    if ($selected_place === '1')         $db->where('score.scorePlace', '1');
    elseif ($selected_place === '2')     $db->where('score.scorePlace', '2');
    elseif ($selected_place === '3')     $db->where('score.scorePlace', '3');
    elseif ($selected_place === 'any')   $db->where('(score.scorePlace IS NOT NULL AND score.scorePlace != "")');
    elseif ($selected_place === 'none')  $db->where('(score.scorePlace IS NULL OR score.scorePlace = "")');

    // Color filter
    if ($selected_color === 'gold')          $db->where('score.scoreEntry', 45, '>=');
    elseif ($selected_color === 'silver')    { $db->where('score.scoreEntry', 41, '>='); $db->where('score.scoreEntry', 45, '<'); }
    elseif ($selected_color === 'bronze')    { $db->where('score.scoreEntry', 36, '>='); $db->where('score.scoreEntry', 41, '<'); }

    // Style filter
    if ($selected_style !== '') $db->where('brewing.brewStyle', $selected_style);

    $db->orderBy('brewing.brewStyle', 'asc');
    $db->orderBy('score.scorePlace',  'asc');
    $db->orderBy('score.scoreEntry',  'desc');

    $entries = $db->get($brewingTable . " brewing", null,
        'brewing.id, brewBrewerFirstName, brewBrewerLastName, brewCoBrewer, brewName, brewStyle, score.scoreEntry, score.scorePlace');
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Diplomy – tisk</title>
    <style>
        <?php echo render_diploma_css(); ?>
        * { box-sizing: border-box; }
        body { padding: 20px; background: #888; font-family: Arial, sans-serif; font-size: 14px; }
        .no-print { background: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .no-print h3 { margin-top: 0; }
        .no-print label { font-weight: bold; display: flex; flex-direction: column; gap: 3px; font-size: 12px; }
        .no-print select { padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 14px; }
        .no-print button, .no-print a.btn { display: inline-block; padding: 5px 12px; border: 1px solid #ccc; border-radius: 3px; background: #f8f8f8; cursor: pointer; text-decoration: none; color: #333; margin-right: 4px; }
        .no-print button.primary { background: #337ab7; border-color: #2e6da4; color: #fff; }
        .text-muted { color: #888; }
        .diploma { margin: 20px auto; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { background: none; padding: 0; }
            .no-print { display: none; }
            .diploma { margin: 0; page-break-after: always; }
            .diploma:last-child { page-break-after: avoid; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <h3 style="margin-top:0">Diplomy – tisk</h3>

    <form method="get" style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:12px">
        <input type="hidden" name="show" value="1">
        <label>Styl
            <select name="style">
                <option value="">Vše</option>
                <?php foreach ($all_styles as $s): ?>
                    <option value="<?php echo htmlspecialchars($s); ?>"
                        <?php if ($selected_style === $s) echo 'selected'; ?>>
                        <?php echo htmlspecialchars(html_entity_decode($s)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Umístění
            <select name="place">
                <?php foreach ($place_options as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php if ($selected_place === $val) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($lbl); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Barva
            <select name="color">
                <?php foreach ($color_options as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php if ($selected_color === $val) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($lbl); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="primary">Show</button>
        <?php if ($show && !empty($entries)): ?>
            <button type="button" onclick="window.print()">Print</button>
        <?php endif; ?>
    </form>

    <?php if ($show): ?>
        <?php if (empty($entries)): ?>
            <p class="text-muted">No entries match the selected conditions.</p>
        <?php else: ?>
            <p class="text-muted"><?php echo count($entries); ?> diploma(s) found.</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="cech_admin.php" class="btn">Back</a>
</div>

<?php foreach ($entries as $entry) {
    echo render_diploma($entry, $diploma_config, $base_url);
} ?>

</body>
</html>
