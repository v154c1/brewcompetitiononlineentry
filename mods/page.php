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
$brewersTables = $prefix . "brewer";


$style_sql = strtr('SELECT DISTINCT brewStyle FROM {brewingTable} ORDER BY brewStyle ASC',
    array(
        '{brewingTable}' => $brewingTable
    )
);
$ssql = mysqli_query($connection, $style_sql) or die (mysqli_error($connection));
$row_ssql = mysqli_fetch_assoc($ssql);
$totalRows_ssql = mysqli_num_rows($ssql);

if ($totalRows_ssql > 0) {
    do {

        $style = $row_ssql['brewStyle'];
        echo "<h1>$style</h1>";

//$query_sql = sprintf($query_sql = "SELECT * FROM %s LEFT JOIN %s ON %s.id = %s.eid LEFT JOIN %s ON %s.brewBrewerID = %s.id ORDER BY %s.brewCategorySort", $brewingTable, $scoresTable, $brewingTable, $scoresTable, $brewersTables, $brewingTable, $brewersTables, $brewingTable);
        $query_sql = strtr('SELECT * FROM {brewingTable} LEFT JOIN {scoresTable} ON {brewingTable}.id = {scoresTable}.eid LEFT JOIN {brewersTable} ON {brewingTable}.brewBrewerID = {brewersTable}.id  WHERE `{brewingTable}`.`brewStyle` = \'{style}\' ORDER BY {scoresTable}.scoreEntry DESC',
            array(
                '{brewingTable}' => $brewingTable,
                '{scoresTable}' => $scoresTable,
                '{brewersTable}' => $brewersTables,
                '{style}' => $style
            )
        );

        $sql = mysqli_query($connection, $query_sql) or die (mysqli_error($connection));
        $row_sql = mysqli_fetch_assoc($sql);
        $num_fields = mysqli_num_fields($sql);
        $totalRows_sql = mysqli_num_rows($sql);


        if ($totalRows_sql > 0) {
            ?>
            <table class="table">
            <?php
            echo "<tr><th scope=\"col\">$label_brewer</th><th scope=\"col\">$label_cobrewer</th><th scope=\"col\">$label_entry</th><th scope=\"col\">$label_style</th><th scope=\"col\">$label_score</th></tr>\n";

            do {
                $score = '';
                $diplomClass = '';

                $scoreEntry = $row_sql['scoreEntry'];
                if ($scoreEntry) {
                    $score = $scoreEntry * 2;
                    if ($score >= 90) {
                        $diplomClass = "goldenDiplom";
                    } else if ($score > 80) {
                        $diplomClass = "silverDiplom";
                    } else if ($score > 70) {
                        $diplomClass = "bronzeDiplom";
                    }
                }

//        print_r($row_sql);
                echo "<tr class='$diplomClass' scope=\"row\"'><td>" . $row_sql['brewBrewerFirstName'] . " " . $row_sql['brewBrewerLastName'] . "</td><td>" . $row_sql['brewCoBrewer'] . "</td><td>" . $row_sql['brewName'] . "</td><td>" . $row_sql['brewStyle'] . "</td><td>" . $score . "</td></tr>\n";
//        echo "<br><br>";

            } while ($row_sql = mysqli_fetch_assoc($sql));
        }
        ?>

        </table>
        <?php
    } while ($row_ssql = mysqli_fetch_assoc($ssql));
}
?>


</body>
</html>