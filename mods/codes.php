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
        @media print {
            @page {

                size: a4 portrait;

            }

            body {

                margin-left: 20mm;
                margin-right: 20mm;
                margin-top: 20mm;
                margin-bottom: 10mm;
                width: 170mm;
            }
        }

        .label-container {
            width: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .label {
            width: 85mm;
            height: 50mm;
            break-inside: avoid;
            flex: auto 0 0;
            display: flex;
            flex-direction: row;

            flex-wrap: wrap;
            color: black;
        }

        .label-title {
            font-size: 5mm;
            flex: 100% 1 1;
        }

        .label-number {
            font-size: 20mm;
            flex: auto 1 1;
        }

        .label-qr {
            width: 30mm;
            height: 30mm;
            flex: auto 0 0;
        }

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

<div class="label-container">
    <?php
    if (!$admin_role) {
        die('not an admin!');

    }
    $stylesTable = $prefix . "styles";
    $brewingTable = $prefix . "brewing";
    $scoresTable = $prefix . "judging_scores";
    $brewersTables = $prefix . "brewer";


    $style_sql = strtr('SELECT DISTINCT brewStyle FROM {brewingTable}',
        array(
            '{brewingTable}' => $brewingTable
        )
    );
    $ssql = mysqli_query($connection, $style_sql) or die (mysqli_error($connection));
    $row_ssql = mysqli_fetch_assoc($ssql);
    $totalRows_ssql = mysqli_num_rows($ssql);

    $stmt = mysqli_prepare($connection, strtr('SELECT * FROM {brewingTable} WHERE `{brewingTable}`.`brewStyle` = ? ORDER BY {brewingTable}.id ASC',
        array(
            '{brewingTable}' => $brewingTable,
            '{scoresTable}' => $scoresTable,
            '{brewersTable}' => $brewersTables
        )));
    mysqli_stmt_bind_param($stmt, 's', $style);

    if ($totalRows_ssql > 0) {
    do {

    $style = $row_ssql['brewStyle'];
    //        echo "<h1>$style</h1>";

    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row_sql = mysqli_fetch_assoc($sql);
    $num_fields = mysqli_num_fields($sql);
    $totalRows_sql = mysqli_num_rows($sql);


    if ($totalRows_sql > 0) {

    do {
    ?>
    <div class="label">
        <?php
        echo '<div class="label-title">' . $row_sql['brewStyle'] . '</div>';
        echo '<div class="label-number">' . $row_sql['id'] . '</div>';
        // Get the protocol
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';

        // Get the domain name
        $domain = $_SERVER['HTTP_HOST'];

        // Get path to current script, remove script itself and last slash
        $path = dirname($_SERVER['PHP_SELF']);

        require_once (CLASSES.'qr_code/qrClass.php');
        $qr = new qRClas();

        $qrcode_url = $base_url."index.php?section=evaluation&go=scoresheet&action=add&id=" . $row_sql['id'];
        $qrcode_url = urlencode($qrcode_url);

        $qr->qRCreate($qrcode_url,"100x100","UTF-8");
        $qrcode_link = $qr->url;

        // Get path to parent directory
        //$parentPath = dirname($path);
        //$judgingURL = $protocol . $domain . $parentPath . "/index.php?section=evaluation&go=scoresheet&action=add&id=" . $row_sql['id'];
//        echo "<img class=\"label-qr\" src=\"https://chart.googleapis.com/chart?cht=qr&chs=100x100&chl=" . urlencode($judgingURL) . "\">";
        echo "<img class=\"label-qr\" src=\"$qrcode_link\">";
        echo "</div>\n";
        //        echo "<br><br>";

        } while ($row_sql = mysqli_fetch_assoc($sql));
        }
        ?>

        <!--        </table> -->
        <?php
        } while ($row_ssql = mysqli_fetch_assoc($ssql));
        }
        ?>

    </div>
</body>
</html>
