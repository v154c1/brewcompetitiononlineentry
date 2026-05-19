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
$templates      = isset($diploma_config['templates'])      ? $diploma_config['templates']      : [];


$matched = find_matching_template($templates, $entry['scorePlace'], $entry['scoreEntry']);

if (!$matched) {
    echo "<h2>No matching diploma template for this entry.</h2>";
    echo "<p><a href='page.php'>Back</a></p>";
    die;
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Diplom</title>
    <style>
        <?php echo render_diploma_css(); ?>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #888; }
        .no-print {
            padding: 10px 20px;
            background: #333;
        }
        .no-print a { color: #fff; margin-right: 10px; }
        .diploma { margin: 20px auto; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            .no-print { display: none; }
            body { background: none; }
            .diploma { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="javascript:window.print()">Print</a>
    <a href="page.php">Back</a>
</div>

<?php echo render_diploma($entry, $diploma_config, $base_url); ?>

</body>
</html>
