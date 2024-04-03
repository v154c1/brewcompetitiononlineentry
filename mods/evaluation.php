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


<!--<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample">Debug VARS</button>-->
<!--<button class="btn btn-primary" type="button" data-toggle="collapse" data-target=".multi-collapse">Details</button>-->

<!--<pre class="collapse" id="collapseExample">-->
<!---->
<?php
//
//print_r($_SESSION);
//?>
<!--</pre>-->
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


$style_sql = strtr('SELECT DISTINCT {tablesTable}.id as tableId, {tablesTable}.tableName as tableName, {stylesTable}.brewStyle as brewStyle, {stylesTable}.id as styleId, {stylesTable}.brewStyleGroup as brewStyleGroup, {stylesTable}.brewStyleNum as brewStyleNum FROM {tablesTable} LEFT JOIN {stylesTable} ON {tablesTable}.tableStyles = {stylesTable}.id',
    array(
        '{brewingTable}' => $brewingTable,
        '{stylesTable}' => $stylesTable,
        '{tablesTable}' => $tablesTable,
    )
);
$ssql = mysqli_query($connection, $style_sql) or die (mysqli_error($connection));
$row_ssql = mysqli_fetch_assoc($ssql);
$totalRows_ssql = mysqli_num_rows($ssql);

if ($totalRows_ssql > 0) {
    do {

        $style = $row_ssql['brewStyle'];
        $tableId = $row_ssql['tableId'];
        $styleCategory = $row_ssql['brewStyleGroup'];
        $styleSubCategory = $row_ssql['brewStyleNum'];

        echo "<h1>$style</h1>";

        $empty_sql = strtr('SELECT {brewingTable}.id as bid FROM {brewingTable} LEFT  JOIN {evalTable} ON {brewingTable}.id = {evalTable}.eid WHERE {evalTable}.id IS NULL AND {brewingTable}.brewCategory = {styleCategory} AND {brewingTable}.brewSubCategory = \'{styleSubCategory}\' AND {brewingTable}.brewPaid = 1',
            array(
                '{brewingTable}' => $brewingTable,
                '{brewersTable}' => $brewersTables,
                '{evalTable}' => $evalTable,
                '{styleId}' => $row_ssql['styleId'],
                '{styleCategory}' => $styleCategory,
                '{styleSubCategory}' => $styleSubCategory,

            )
        );
//        print($empty_sql);
        $psql = mysqli_query($connection, $empty_sql) or die (mysqli_error($connection));
        $row_psql = mysqli_fetch_assoc($psql);
        $totalRows_psql = mysqli_num_rows($psql);
        echo '<br>';
        if ($totalRows_psql > 0) {
            echo '<h1>Vzorky bez hodnoceni</h1>';
            echo '<table class="table table-responsive table-striped table-bordered dataTable no-footer"><tr role="row"><th>ID</th></tr>';
            do {
                echo '<tr><td>'.$row_psql['bid'].'</td></tr>';
            } while ($row_psql = mysqli_fetch_assoc($psql));
            echo '</table>';
        }


//        print_r($row_ssql);

        $judge_sql = strtr('SELECT DISTINCT {evalTable}.evalJudgeInfo as jid, {brewersTable}.brewerFirstName, {brewersTable}.brewerLastName FROM {evalTable} JOIN {brewersTable} ON {brewersTable}.id = {evalTable}.evalJudgeInfo WHERE {evalTable}.evalStyle = {styleId}',
            array(
                '{brewingTable}' => $brewingTable,
                '{brewersTable}' => $brewersTables,
                '{evalTable}' => $evalTable,
                '{styleId}' => $row_ssql['styleId'],
            )
        );
        $jsql = mysqli_query($connection, $judge_sql) or die (mysqli_error($connection));
        $row_jsql = mysqli_fetch_assoc($jsql);
        $totalRows_jsql = mysqli_num_rows($jsql);
        echo '<br>';
        if ($totalRows_jsql > 0) {
            do {
                echo '<h2>' . $row_jsql['brewerFirstName'] . ' ' . $row_jsql['brewerLastName'] . '</h2>';


                $eval_sql = strtr('SELECT  {evalTable}.eid as eid, {evalTable}.evalFinalScore FROM {evalTable} WHERE {evalTable}.evalJudgeInfo = {judgeId} ORDER BY {evalTable}.evalFinalScore DESC',
                    array(
                        '{brewingTable}' => $brewingTable,
                        '{brewersTable}' => $brewersTables,
                        '{evalTable}' => $evalTable,
                        '{styleId}' => $row_ssql['styleId'],
                        '{judgeId}' => $row_jsql['jid']
                    )
                );
                $esql = mysqli_query($connection, $eval_sql) or die (mysqli_error($connection));
                $row_esql = mysqli_fetch_assoc($esql);
                $totalRows_esql = mysqli_num_rows($esql);
//                echo '<br>';
//                                print_r($row_jsql);
//                echo '<br>';

                echo '<table class="table table-responsive table-striped table-bordered dataTable no-footer"><tr role="row"><th>ID</th><th>score</th></tr>';
                if ($totalRows_esql > 0) {
                    do {
                        ?>
                        <tr role="row">
                            <td><?php echo $row_esql['eid']; ?></td>
                            <td><?php echo $row_esql['evalFinalScore']; ?></td>
                        </tr>

                        <?php
//                        print_r($row_esql);
//                        echo '<br>';

                    } while ($row_esql = mysqli_fetch_assoc($esql));
                }
                echo '</table>';

            } while ($row_jsql = mysqli_fetch_assoc($jsql));
        }


    } while ($row_ssql = mysqli_fetch_assoc($ssql));

}
?>

</body>
</html>
