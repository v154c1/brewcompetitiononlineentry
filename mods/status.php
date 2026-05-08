<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');
$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$stylesTable = $prefix . "styles";
$brewingTable = $prefix . "brewing";
$scoresTable = $prefix . "judging_scores";
$evalTable = $prefix . "evaluation";
$tablesTable = $prefix . "judging_tables";
$assignTable = $prefix . "judging_assignments";
$brewersTables = $prefix . "brewer";


function get_entries_count()
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewPaid', 1);
    $db->where('brewReceived', 1);
    return $db->getValue($brewingTable, 'count(*)');
}

function get_evaluations_count()
{
    global $evalTable, $connection;
    $db = new MysqliDb($connection);
    return $db->getValue($evalTable, 'count(distinct eid)');
}

function get_score_count()
{
    global $scoresTable, $connection;
    $db = new MysqliDb($connection);
    return $db->getValue($scoresTable, 'count(distinct eid)');
}

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
    $ids = array_values(array_filter(array_map('intval', explode(',', $tableStyles))));
    return $ids;
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

// Count entries for a single style (by category/subcategory)
function get_entries_count_for_style($brewStyleGroup, $brewStyleNum)
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewCategory', $brewStyleGroup);
    $db->where('brewSubCategory', $brewStyleNum);
    $db->where('brewPaid', 1);
    $db->where('brewReceived', 1);
    return (int)$db->getValue($brewingTable, 'count(*)');
}

// Count entries across all styles in a table
function get_table_entries_count($styles)
{
    $total = 0;
    foreach ($styles as $style) {
        $total += get_entries_count_for_style($style['brewStyleGroup'], $style['brewStyleNum']);
    }
    return $total;
}

// Count distinct evaluated entries across all style IDs in a table
function get_table_evaluated_entries_count($styleIds)
{
    global $evalTable, $connection;
    if (empty($styleIds)) return 0;
    $db = new MysqliDb($connection);
    $db->where('evalStyle', $styleIds, 'IN');
    return (int)$db->getValue($evalTable, 'count(distinct eid)');
}

// Count total scoresheets across all style IDs in a table
function get_table_scoresheet_count($styleIds)
{
    global $evalTable, $connection;
    if (empty($styleIds)) return 0;
    $db = new MysqliDb($connection);
    $db->where('evalStyle', $styleIds, 'IN');
    return (int)$db->getValue($evalTable, 'count(*)');
}

// Count entries with at least one score, across all styles in a table
function get_table_score_count($styles)
{
    global $brewingTable, $scoresTable, $connection;
    if (empty($styles)) return 0;
    $total = 0;
    foreach ($styles as $style) {
        $db = new MysqliDb($connection);
        $db->join($scoresTable . " score", "score.eid=brewing.id", "LEFT");
        $db->where('brewing.brewCategory', $style['brewStyleGroup']);
        $db->where('brewing.brewSubCategory', $style['brewStyleNum']);
        $db->where('score.id', null, 'IS NOT');
        $total += (int)$db->getValue($brewingTable . " brewing", 'count(distinct brewing.id)');
    }
    return $total;
}

// Find entries with duplicate scoresheets across all style IDs in a table
function get_duplicate_entries($styleIds)
{
    global $evalTable, $connection;
    if (empty($styleIds)) return [];
    $db = new MysqliDb($connection);
    $db->where('evalStyle', $styleIds, 'IN');
    $db->groupBy('eid');
    $db->having('count(*) > 1');
    return $db->get($evalTable, null, 'eid, count(*) as count');
}

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
</head>
<body>

<?php

$tables = get_tables();

function td($val)
{
    echo "<TD>" . $val . "</TD>";
}

?>

<style>
    .overview TR TH {
        padding-right: 2em;
    }
</style>

<h2>Stav zpracovani</h2>
<h3>Prehled</h3>
<table class="overview">
    <tr>
        <th>Celkem vzorku</th>
        <td><?php echo get_entries_count() ?></td>
    </tr>
    <tr>
        <th>Ohodnoceno vzorku</th>
        <td><?php echo get_evaluations_count() ?></td>
    </tr>
    <tr>
        <th>Zapsanych score</th>
        <td><?php echo get_score_count() ?></td>
    </tr>
</table>

<h3>Stoly</h3>
<table class="table table-responsive table-striped table-bordered no-footer">
    <tr>
        <th>Stul</th>
        <th>Styly</th>
        <th>Zpracovano</th>
        <th>Pocet scoresheetu</th>
        <th>Zapsanych score</th>
        <th>Duplikatni vzorky</th>
    </tr>
    <?php
    foreach ($tables as $table) {
        $styleIds = parse_style_ids($table['tableStyles']);
        $styles = get_styles_info($styleIds);

        $total_entries = get_table_entries_count($styles);
        $processed_entries = get_table_evaluated_entries_count($styleIds);
        $scoresheet_count = get_table_scoresheet_count($styleIds);
        $scores_count = get_table_score_count($styles);
        $dup = get_duplicate_entries($styleIds);

        $style_names = implode(', ', array_map(function ($s) {
            return $s['brewStyleGroup'] . $s['brewStyleNum'] . ' ' . $s['brewStyle'];
        }, $styles));

        $dupstr = '';
        foreach ($dup as $entry) {
            $dupstr .= $entry['eid'] . '(' . $entry['count'] . '), ';
        }

        echo "<tr>";
        td($table['tableName']);
        td($style_names ?: '<em>nezarazeno</em>');
        td(strval($processed_entries) . "/" . $total_entries);
        td($scoresheet_count);
        td($scores_count);
        td($dupstr);
        echo "</tr>\n";
    }
    ?>
</table>

<script>
    $(document).ready(() => {
        window.setTimeout(() => location.reload(), 5000);
    });
</script>
</body>
</html>
