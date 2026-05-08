<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');

require('../paths.php');
require(CONFIG . 'bootstrap.php');
require(INCLUDES . 'url_variables.inc.php');
require(LANG . 'language.lang.php');

$winner_method = $_SESSION['prefsWinnerMethod'];
$style_set = $_SESSION['prefsStyleSet'];
$pro_edition = $_SESSION['prefsProEdition'];
$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $_SESSION['contestName']; ?> - Brew Competition Online Entry &amp; Management</title>
    <?php
    if (CDN) include(INCLUDES . 'load_cdn_libraries.inc.php');
    else include(INCLUDES . 'load_local_libraries.inc.php');
    ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $css_url . "common.min.css"; ?>"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $theme; ?>"/>

    <script type="text/javascript">
        var username_url = "<?php echo $ajax_url; ?>username.ajax.php";
        var email_url = "<?php echo $ajax_url; ?>valid_email.ajax.php";
        var user_agent_msg = "<?php echo $alert_text_086; ?>";
        var setup = 0;
    </script>

    <script src="<?php echo $js_url; ?>bcoem_custom.min.js"></script>

    <?php if (!empty($_SESSION['contestName'])) { ?>
        <meta property="og:title" content="<?php echo $_SESSION['contestName'] ?>"/>
    <?php } ?>
    <?php if (!empty($_SESSION['contestLogo'])) { ?>
        <meta property="og:image" content="<?php echo $base_url . "user_images/" . $_SESSION['contestLogo'] ?>"/>
    <?php } ?>
    <meta property="og:url"
          content="<?php echo "http" . ((!empty($_SERVER['HTTPS'])) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"/>
    <style>
        .goldenDiplom { background-color: gold; }
        .silverDiplom { background-color: silver; }
        .bronzeDiplom { background-color: saddlebrown; }
    </style>
</head>
<body>
<?php
if (!$admin_role) {
    die('not an admin!');
}

$stylesTable   = $prefix . "styles";
$brewingTable  = $prefix . "brewing";
$scoresTable   = $prefix . "judging_scores";
$evalTable     = $prefix . "evaluation";
$tablesTable   = $prefix . "judging_tables";
$assignTable   = $prefix . "judging_assignments";
$brewersTables = $prefix . "brewer";

function get_tables()
{
    global $tablesTable, $connection;
    $db = new MysqliDb($connection);
    $db->orderBy('tableName', 'asc');
    return $db->get($tablesTable, null, "id as tableId, tableName, tableStyles");
}

function parse_style_ids($tableStyles)
{
    if (empty($tableStyles)) return [];
    return array_values(array_filter(array_map('intval', explode(',', $tableStyles))));
}

function get_styles_info($styleIds)
{
    global $stylesTable, $connection;
    if (empty($styleIds)) return [];
    $db = new MysqliDb($connection);
    $db->where('id', $styleIds, 'IN');
    $db->orderBy('brewStyleGroup', 'asc');
    $db->orderBy('brewStyleNum', 'asc');
    return $db->get($stylesTable, null, "id, brewStyle, brewStyleGroup, brewStyleNum");
}

// Returns entries with no evaluation for a single style (by category/subcategory pair)
function get_empty_entries_for_style($styleCategory, $styleSubCategory)
{
    global $brewingTable, $evalTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewCategory', $styleCategory);
    $db->where('brewSubCategory', $styleSubCategory);
    $db->where('brewPaid', 1);
    $db->where('brewReceived', 1);
    $db->join("$evalTable eval", "brewing.id=eval.eid", "LEFT");
    $db->where('eval.id', null, 'IS');
    $db->orderBy("brewing.id", "asc");
    return $db->get("$brewingTable brewing", null, "brewing.id as bid");
}

// Aggregate empty entries across all styles in a table
function get_empty_entries($styles)
{
    $result = [];
    foreach ($styles as $style) {
        $rows = get_empty_entries_for_style($style['brewStyleGroup'], $style['brewStyleNum']);
        $result = array_merge($result, $rows);
    }
    // De-duplicate by entry ID in case an entry somehow matches multiple styles
    $seen = [];
    $deduped = [];
    foreach ($result as $row) {
        if (!isset($seen[$row['bid']])) {
            $seen[$row['bid']] = true;
            $deduped[] = $row;
        }
    }
    usort($deduped, fn($a, $b) => $a['bid'] - $b['bid']);
    return $deduped;
}

// Returns distinct judges who evaluated any entry in the given style IDs
function get_judges($styleIds)
{
    global $evalTable, $brewersTables, $connection;
    if (empty($styleIds)) return [];
    $db = new MysqliDb($connection);
    $db->join("$brewersTables brewers", "brewers.id=eval.evalJudgeInfo", "LEFT");
    $db->where('eval.evalStyle', $styleIds, 'IN');
    $db->orderBy('brewers.brewerLastName', 'asc');
    return $db->get("$evalTable eval", null, "DISTINCT eval.evalJudgeInfo as jid, brewers.brewerFirstName, brewers.brewerLastName");
}

// Returns all entries scored by a given judge across the given style IDs
function get_entries($styleIds, $judgeID)
{
    global $evalTable, $connection;
    if (empty($styleIds)) return [];
    $db = new MysqliDb($connection);
    $db->where('eval.evalStyle', $styleIds, 'IN');
    $db->where('eval.evalJudgeInfo', $judgeID);
    $db->orderBy("eval.evalFinalScore", "desc");
    return $db->get("$evalTable eval", null, "eval.eid as eid, eval.evalFinalScore, eval.evalAromaScore, eval.evalAppearanceScore, eval.evalFlavorScore, eval.evalMouthfeelScore, eval.evalOverallScore");
}


$tables = get_tables();

foreach ($tables as $table) {
    $styleIds = parse_style_ids($table['tableStyles']);
    $styles   = get_styles_info($styleIds);

    $style_names = implode(', ', array_map(function ($s) {
        return $s['brewStyleGroup'] . $s['brewStyleNum'] . ' ' . $s['brewStyle'];
    }, $styles));

    echo "<h1>" . htmlspecialchars($table['tableName']) . "</h1>";
    echo "<p><em>" . htmlspecialchars($style_names) . "</em></p>";

    $empty_entries = get_empty_entries($styles);

    echo '<br>';
    if (count($empty_entries) > 0) {
        echo '<h3>Vzorky bez hodnocení</h3>';
        echo '<table class="table table-responsive table-striped table-bordered dataTable no-footer"><tr role="row"><th>Číslo vzorku</th></tr>';
        foreach ($empty_entries as $row_psql) {
            echo '<tr><td>' . $row_psql['bid'] . '</td></tr>';
        }
        echo '</table>';
    }

    $judges = get_judges($styleIds);
    echo '<br>';
    if (count($judges) > 0) {
        foreach ($judges as $row_jsql) {
            echo '<h2>' . htmlspecialchars($row_jsql['brewerLastName'] . ' ' . $row_jsql['brewerFirstName']) . '</h2>';

            $entries = get_entries($styleIds, $row_jsql['jid']);

            echo '<table class="table table-responsive table-striped table-bordered dataTable no-footer"><tr role="row"><th>Číslo vzorku</th><th>score</th></tr>';
            foreach ($entries as $row_esql) {
                ?>
                <tr role="row">
                    <td><?php echo $row_esql['eid']; ?></td>
                    <td><?php
                        $finalScore = $row_esql['evalFinalScore'];
                        echo $finalScore;
                        $score_sum = $row_esql['evalAromaScore'] + $row_esql['evalAppearanceScore'] + $row_esql['evalFlavorScore'] + $row_esql['evalMouthfeelScore'] + $row_esql['evalOverallScore'];
                        if ($score_sum != $finalScore) {
                            echo '<span style="color:red;font-weight: bold"> (' . $score_sum . ')</span>';
                        }
                    ?></td>
                </tr>
                <?php
            }
            echo '</table>';
        }
    }
}
?>

</body>
</html>
