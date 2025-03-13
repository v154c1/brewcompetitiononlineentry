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
            background-color: #bfa396;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: "Arial Black", Arial, sans-serif;
        }

        .diploma {
            position: relative;
            width: 210mm; /* A4 width */
            height: 297mm; /* A4 height */
            /*background-image: url("../images/diploma.png");*/
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .text {
            position: absolute;
            left: 12mm;
            top: 120mm;
            display: flex;;
            flex-direction: column;
            justify-content: start;

            .intro {
                font-size: 10mm;

                line-height: 11mm;
                margin-bottom: 20mm;

                .place {
                    font-weight: bold;
                    font-size: 12mm;

                }
            }

            .recipient {
                /*position: absolute;*/
                /*top: 160mm;*/
                font-size: 28px;
                font-style: italic;
                font-weight: bold;
                /*left: 12mm;*/
            }

            .entry {
                font-size: 28px;
                /*font-style: italic;*/
                /*font-weight: bold;*/
            }


        }

        @media print {
            body {
                -webkit-print-color-adjust: exact; /* For Chrome */
                print-color-adjust: exact; /* Standard property */
            }

            .diploma {
                /*background-image: url('../images/diploma.jpg'); !* Re-declare the background *!*/
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
            }
        }

    </style>
</head>
<body>


<?php
//
//print_r($_SESSION);
//?>
<!--</pre>-->
<?php
if (!$admin_role) {
    die('not an admin!');

}

if (!isset($_GET["entry"])) {

    die ("No entry specified");
}

$entry_id = $_GET["entry"];


$brewingTable = $prefix . "brewing";
$brewersTable = $prefix . "brewer";
$scoresTable = $prefix . "judging_scores";
function get_entry($entry_id)
{
    global $connection;
    global $scoresTable;
    global $brewersTable;
    global $brewingTable;
    $db = new MysqliDb($connection);
    $db->join("$scoresTable scores", 'brewing.id = scores.eid', 'LEFT');
    $db->join("$brewersTable brewers", 'brewers.id = brewing.brewBrewerID', 'LEFT');
    $db->where('brewing.id', $entry_id);
//    $db->orderBy("brewing.id", "asc");
    //$db->orderBy("brewStyle", "asc");
    return $db->get("$brewingTable brewing", null, "brewing.id as brewId, brewing.brewName as brewName, brewing.brewStyle as brewStyle, brewers.brewerFirstName, brewers.brewerLastName, scores.scoreEntry, scores.scorePlace, brewing.brewCoBrewer");

}

function get_style_id($styleName)
{
    $pattern = "/(?<=\s|^)[A-Z](?=\s|-|\)|$)/";

    if (preg_match($pattern, $styleName, $matches)) {
        return $matches[0];
    }

    return "X";
}

$entries = get_entry($entry_id);
if (count($entries) == 0) {
    die("Entry not found");
}

$entry = $entries[0];
$brewName = $entry['brewName'];
$score = $entry['scoreEntry'];
$place = $entry['scorePlace'];
$style = $entry['brewStyle'];
$styleId = get_style_id($style);
$brewerName = $entry['brewerFirstName'] . "&nbsp;" . $entry['brewerLastName'];
$cobrewer = str_replace(" ", "&nbsp;", $entry['brewCoBrewer']);


//$categories =
//

$shortStyles = array(
    "A" => "světlé pivo, spodně kvašené",
    "B" => "polotmavé a tmavé pivo, spodně kvašené",
    "C" => "svrchně kvašené pivo, mimo<br>pšenice a stout/porter",
    "D" => "pivo pšeničné",
    "E" => "stout/porter",
    "F" => "nakuřované pivo",
    "Q" => "kyseláče"
);

$styleShort = $shortStyles[$styleId];


//print_r($entry);
?>

<div class="diploma">
    <img src="../images/diploma.png">
    <div class="text">
        <div class="intro">
        <span class="place">
        <?php echo $place; ?>.
        </span>&nbsp;místo v kategorii <?php echo $styleId; ?><br>
            <?php echo $styleShort; ?>
        </div>

        <div class="recipient">
            <?php echo $brewerName ?><?php if ($cobrewer) {
                echo ", $cobrewer";
            } ?>

        </div>
        <div class="entry">za vzorek: <?php echo $brewName; ?></div>
    </div>


</div>


</body>
</html>