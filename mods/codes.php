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

                /*size: a4 portrait;*/
                size: 35mm 25mm;

            }

            body {
                margin: 0;
                /*margin-left: 20mm;*/
                /*margin-right: 20mm;*/
                /*margin-top: 20mm;*/
                /*margin-bottom: 10mm;*/
                /*width: 170mm;*/
            }
        }

        .label-container {
            width: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .label {
            padding: 2mm;
            width: 35mm;
            height: 25mm;
            /*width: 85mm;*/
            /*height: 50mm;*/
            break-inside: avoid;
            break-after: page;
            flex: auto 0 0;
            display: flex;
            flex-direction: row;

            flex-wrap: wrap;
            color: black;
            border: none !important;
        }

        .label:last-of-type {
            break-after: auto;
        }

        /*.label-title {*/
        /*    font-size: 5mm;*/
        /*    flex: 100% 1 1;*/
        /*    overflow: hidden;*/
        /*    text-overflow: ellipsis;*/
        /*}*/

        .label-number {
            font-size: 9mm;
            flex: auto 1 1;
            height: 20mm;
            display: flex;
            justify-content: center;
            flex-direction: column;
        }

        /*.label-qr {*/
        /*    width: 25mm;*/
        /*    height: 25mm;*/
        /*    flex: auto 0 0;*/
        /*}*/

        /*.goldenDiplom {*/
        /*    background-color: gold;*/
        /*}*/

        /*.silverDiplom {*/
        /*    background-color: silver;*/

        /*}*/

        /*.bronzeDiplom {*/
        /*    background-color: saddlebrown;*/
        /*}*/
    </style>
</head>
<body>


<div class="label-container">
    <?php
    if (!$admin_role) {
        die('not an admin!');

    }
    $stylesTable = $prefix . "styles";
    $brewingTable = $prefix . "brewing";
    $scoresTable = $prefix . "judging_scores";
    $brewersTables = $prefix . "brewer";


    function get_styles()
    {
        global $brewingTable;
        global $connection;
        $db = new MysqliDb($connection);
        $db->orderBy("brewStyle", "asc");
        return $db->get("$brewingTable brewing", null, "DISTINCT brewStyle");

    }

    function get_entries($style)
    {
        global $brewingTable;
        global $connection;

        $db = new MysqliDb($connection);
        $db->where('brewStyle', $style);
//        $db->where('brewPaid', 1);
        $db->orderBy("brewing.id", "asc");

        return $db->get("$brewingTable brewing", null, "brewing.id as brewId, brewStyle");
    }

    $styles = get_styles();

    foreach ($styles

    as $row_ssql) {

    $style = $row_ssql['brewStyle'];
    $id_prefix = '';
    $pattern = "/(?<=\s|^)[A-Z](?=\s|-|\)|$)/";

    if (preg_match($pattern, $style, $matches)) {
        $id_prefix = $matches[0];
    }

    $entries = get_entries($style);

    foreach ($entries

    as $row_sql) {
    ?>
    <div class="label">
        <?php

//        echo '<div class="label-title">' . $row_sql['brewStyle'] . '</div>';
        echo '<div class="label-number">' . $id_prefix . '-', $row_sql['brewId'] . '</div>';

//        require_once(CLASSES . 'qr_code/qrClass.php');
//        $qr = new qRClas();
//
//        $qrcode_url = $base_url . "index.php?section=evaluation&go=scoresheet&action=add&id=" . $row_sql['brewId'];
//        $qrcode_url = urlencode($qrcode_url);
//
//        $qr->qRCreate($qrcode_url, "100x100", "UTF-8");
//        $qrcode_link = $qr->url;
//
//        echo "<img class=\"label-qr\" src=\"$qrcode_link\">";
        echo "</div>\n";
        }
        ?>

        <?php
        }
        ?>

    </div>
</body>
</html>
