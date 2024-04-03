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
    <!-- Load BCOE&M Custom CSS - Contains Bootstrap overrides and custom classes common to all BCOE&M themes -->
    <link rel="stylesheet" type="text/css" href="<?php echo $css_url . "common.min.css"; ?>"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $theme; ?>"/>

    <script type="text/javascript">
        var username_url = "<?php echo $ajax_url; ?>username.ajax.php";
        var email_url = "<?php echo $ajax_url; ?>valid_email.ajax.php";
        var user_agent_msg = "<?php echo $alert_text_086; ?>";
        var setup = 0;
    </script>

    <!-- Load BCOE&M Custom JS -->
    <script src="<?php echo $js_url; ?>bcoem_custom.min.js"></script>

    <!-- Open Graph Implementation -->
    <?php if (!empty($_SESSION['contestName'])) { ?>
        <meta property="og:title" content="<?php echo $_SESSION['contestName'] ?>"/>
    <?php } ?>
    <?php if (!empty($_SESSION['contestLogo'])) { ?>
        <meta property="og:image" content="<?php echo $base_url . "user_images/" . $_SESSION['contestLogo'] ?>"/>
    <?php } ?>
    <meta property="og:url"
          content="<?php echo "http" . ((!empty($_SERVER['HTTPS'])) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"/>
    <style>
        .goldenDiplom {
            background-color: gold;
        }

        .silverDiplom {
            background-color: silver;

        }

        .bronzeDiplom {
            background-color: saddlebrown;
        }
    </style>
</head>
<body>
<?php
if (!$admin_role) {
    die('not an admin!');

}
$stylesTable = $prefix . "styles";
$brewingTable = $prefix . "brewing";
$scoresTable = $prefix . "judging_scores";
$evalTable = $prefix . "evaluation";
$tablesTable = $prefix . "judging_tables";
$assignTable = $prefix . "judging_assignments";
$brewersTables = $prefix . "brewer";

function get_tables()
{
    global $tablesTable;
    global $stylesTable;
    global $connection;

    $db = new MysqliDb($connection);
    $db->join("$stylesTable styles", "styles.id=tables.tableStyles", "LEFT");
    return $db->get("$tablesTable tables", null, "tables.id as tableId, tableName, styles.brewStyle, styles.id as styleId, styles.brewStyleGroup, styles.brewStyleNum");

}

function get_empty_entries($styleCategory, $styleSubCategory)
{
    global $brewingTable;
    global $connection;
    global $evalTable;

    $db = new MysqliDb($connection);
    $db->where('brewCategory', $styleCategory);
    $db->where('brewSubCategory', $styleSubCategory);
    $db->where('brewPaid', 1);
    $db->where('brewReceived', 1);
    $db->join("$evalTable eval", "brewing.id=eval.eid", "LEFT");
    $db->where('eval.id', null, 'IS');
    return $db->get("$brewingTable brewing", null, "brewing.id as bid");

}

function get_judges($styleID)
{
    global $evalTable;
    global $brewersTables;
    global $connection;

    $db = new MysqliDb($connection);
    $db->join("$brewersTables brewers", "brewers.id=eval.evalJudgeInfo", "LEFT");
    $db->where('eval.evalStyle', $styleID);
    $db->orderBy('brewers.brewerLastName', 'asc');
    return $db->get("$evalTable eval", null, "DISTINCT eval.evalJudgeInfo as jid, brewers.brewerFirstName, brewers.brewerLastName");

}

function get_entries($styleID, $judgeID)
{
    global $evalTable;
    global $connection;

    $db = new MysqliDb($connection);
    $db->orderBy("eval.evalFinalScore", "desc");
    $db->where('eval.evalStyle', $styleID);
    $db->where('eval.evalJudgeInfo', $judgeID);
    return $db->get("$evalTable eval", null, "eval.eid as eid, eval.evalFinalScore");

}


$styles = get_tables();

if (count($styles) > 0) {
    foreach ($styles as $row_ssql) {

        $style = $row_ssql['brewStyle'];
        $tableId = $row_ssql['tableId'];
        $styleCategory = $row_ssql['brewStyleGroup'];
        $styleSubCategory = $row_ssql['brewStyleNum'];

        echo "<h1>$style</h1>";


        $empty_entries = get_empty_entries($styleCategory, $styleSubCategory);

        echo '<br>';
        if (count($empty_entries) > 0) {
            echo '<h3>Vzorky bez hodnocení</h3>';
            echo '<table class="table table-responsive table-striped table-bordered dataTable no-footer"><tr role="row"><th>Číslo vzorku</th></tr>';
            foreach ($empty_entries as $row_psql) {
                echo '<tr><td>' . $row_psql['bid'] . '</td></tr>';
            }
            echo '</table>';
        }

        $judges = get_judges($row_ssql['styleId']);
        echo '<br>';
        if (count($judges) > 0) {
            foreach ($judges as $row_jsql) {
                echo '<h2>' . $row_jsql['brewerLastName'] . ' ' . $row_jsql['brewerFirstName'] . '</h2>';

                $entries = get_entries($row_ssql['styleId'], $row_jsql['jid']);


                echo '<table class="table table-responsive table-striped table-bordered dataTable no-footer"><tr role="row"><th>Číslo vzorku</th><th>score</th></tr>';
                if (count($entries) > 0) {
                    foreach ($entries as $row_esql) {
                        ?>
                        <tr role="row">
                            <td><?php echo $row_esql['eid']; ?></td>
                            <td><?php echo $row_esql['evalFinalScore']; ?></td>
                        </tr>

                        <?php
                    }
                }
                echo '</table>';
            }
        }
    }
}
?>

</body>
</html>
