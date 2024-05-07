<?php
ini_set('display_errors', 1); // Change to 0 for prod; change to 1 for testing.
ini_set('display_startup_errors', 1); // Change to 0 for prod; change to 1 for testing.
error_reporting(E_ALL); // Change to error_reporting(0) for prod; change to E_ALL for testing.

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
    global $brewingTable;
    global $connection;

    $db = new MysqliDb($connection);
    $db->where('brewPaid', 1);
    $db->where('brewReceived', 1);

    return $db->getValue($brewingTable, 'count(*)');
}

function get_evaluations_count()
{
    global $connection;
    global $evalTable;

    $db = new MysqliDb($connection);

    return $db->getValue($evalTable, 'count(distinct eid)');
}

function get_score_count()
{
    global $connection;
    global $scoresTable;

    $db = new MysqliDb($connection);

    return $db->getValue($scoresTable, 'count(distinct eid)');
}


function get_tables()
{
    global $tablesTable;
    global $stylesTable;
    global $connection;

    $db = new MysqliDb($connection);
    $db->join("$stylesTable styles", "styles.id=tables.tableStyles", "LEFT");
    $db->orderBy('styles.brewStyle', 'asc');
    return $db->get("$tablesTable tables", null, "tables.id as tableId, tableName, styles.brewStyle, styles.id as styleId, styles.brewStyleGroup, styles.brewStyleNum");

}

function get_table_entries_count($styleCategory, $styleSubCategory)
{
    global $brewingTable;
    global $connection;
//    global $evalTable;

    $db = new MysqliDb($connection);
    $db->where('brewCategory', $styleCategory);
    $db->where('brewSubCategory', $styleSubCategory);
    $db->where('brewPaid', 1);
    $db->where('brewReceived', 1);

//    return $db->get("$brewingTable entries");
    return $db->getValue($brewingTable, 'count(*)');
}


function get_table_evaluated_entries_count($styleId)
{
    global $brewingTable;
    global $connection;
    global $evalTable;

    $db = new MysqliDb($connection);
    $db->where('evalStyle', $styleId);
//    $db->groupBy('eid');


//    return $db->get("$brewingTable entries");
    return $db->getValue($evalTable, 'count(distinct eid)');
}

function get_table_scoresheet_count($styleId)
{
    global $brewingTable;
    global $connection;
    global $evalTable;

    $db = new MysqliDb($connection);
    $db->where('evalStyle', $styleId);

//    return $db->get("$brewingTable entries");
    return $db->getValue($evalTable, 'count(*)');
}

function get_style_score_count($styleCategory, $styleSubCategory)
{
    global $brewingTable;
    global $connection;
    global $scoresTable;

    $db = new MysqliDb($connection);
    $db->join($scoresTable . " score", "score.eid=brewing.id", "LEFT");
    $db->where('brewing.brewCategory', $styleCategory);
    $db->where('brewing.brewSubCategory', $styleSubCategory);
    $db->where('score.id', null, 'IS NOT');


    return $db->getValue($brewingTable . " brewing", 'count(distinct brewing.id)');
}

function get_duplicate_entries($styleId)
{
    global $brewingTable;
    global $connection;
    global $evalTable;

    $db = new MysqliDb($connection);
    $db->where('evalStyle', $styleId);
    $db->groupBy('eid');
    $db->having('count(*) > 1');


//    return $db->get("$brewingTable entries");
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
    <!-- Load BCOE&M Custom CSS - Contains Bootstrap overrides and custom classes common to all BCOE&M themes -->
    <link rel="stylesheet" type="text/css" href="<?php echo $css_url . "common.min.css"; ?>"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $theme; ?>"/>

    <script type="text/javascript">
        var username_url = "<?php echo $ajax_url; ?>username.ajax.php";
        var email_url = "<?php echo $ajax_url; ?>valid_email.ajax.php";
        var user_agent_msg = "<?php echo $alert_text_086; ?>";
        var setup = 0;


        $(document).ready(function () {
            console.log($('#entryTable'));
            $('#entryTable').dataTable({
                "order": [[0, "asc"]],
                "sortable": true,
                "paging": false,
                "searching": true,
                "info": false,
                "columnDefs": []
            });
        });
    </script>
</head>
<body>

<?php

$styles = get_tables();


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
        <th>Zpracovano</th>
        <th>pocet scoresheetu</th>
        <th>zapsanych score</th>
        <th>Duplikatni vzorky</th>
    </tr>
    <?php
    foreach ($styles as $style) {
        $total_entries = get_table_entries_count($style['brewStyleGroup'], $style['brewStyleNum']);
        $processed_entries = get_table_evaluated_entries_count($style['styleId']);
        $scoresheet_count = get_table_scoresheet_count($style['styleId']);
        $scores_count = get_style_score_count($style['brewStyleGroup'], $style['brewStyleNum']);
        echo "<tr>";

        td($style['tableName']);
        td(strval($processed_entries) . "/" . $total_entries);
        td($scoresheet_count);
        td($scores_count);
//        if ($scoresheet_count != $processed_entries) {
        $dup = get_duplicate_entries($style['styleId']);;
        $dupstr = "";

        foreach ($dup as $entry) {
            $dupstr = $dupstr . $entry['eid'] . '(' . $entry['count'] . '), ';
        }

        td($dupstr);
//        }
        echo "</tr>\n";

    }

    //    <tr></tr>


    ?>

</table>
<script>
    $(document).ready(() => {
        window.setTimeout(() => location.reload(), 5000);
    })


</script>
</body>
</html>