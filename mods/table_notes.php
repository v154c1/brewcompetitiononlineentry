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
$staff = FALSE;
if (isset($_GET['staff'])) {
    $staff = TRUE;
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


        // $(document).ready(function () {
        //     console.log($('#entryTable'));
        // $('#entryTable').dataTable({
        //     "order": [[0, "asc"]],
        //     "sortable": "true",
        //     "paging": false,
        //     "searching": true,
        //     "info": false,
        //     "columnDefs": []
        // });
        // });
    </script>
    <style>
        @page {
            size: A4 portrait;
        }

        @media print {
            .no-print {
                display: none;
            }

            .dataTables_filter {
                display: none;
            }

            .page-break-after {
                page-break-after: always;
            }
        }

    </style>
</head>
<body>
<a href="#" class="no-print" onclick="window.print();">Tisk</a>
<a href="?staff" class="no-print">Obsluha</a>
<?php

function showPouring($pouringRaw)
{

    $sep = "<br>";
    $pouringInfo = "";

    if ($pouringRaw && $pouringRaw != "[]") {
        $pouringDecoded = json_decode($pouringRaw, true);
        if ($pouringDecoded) {
            if (array_key_exists('pouring', $pouringDecoded)) {
                $speed = $pouringDecoded['pouring'];
                if ($speed == "Fast") {
                    $pouringInfo = $pouringInfo . "Nalévat rychle" . $sep;
                } else if ($speed == "Slow") {
                    $pouringInfo = $pouringInfo . "Nalévat pomalu" . $sep;
                }
            }
            if (array_key_exists('pouring_rouse', $pouringDecoded)) {
                if ($pouringDecoded['pouring_rouse'] == "Yes") {
                    $pouringInfo = $pouringInfo . "Probudit kvasnice" . $sep;
                }
            }

            if (array_key_exists('pouring_notes', $pouringDecoded)) {
                $notes = $pouringDecoded['pouring_notes'];
                if ($notes) {
                    $pouringInfo = $pouringInfo . $notes;
                }
            }

        }
    }

    return $pouringInfo;
}

function put_line($line, $header = FALSE)
{
    global $fp;

    if ($header) {
        echo "<thead>";
    }
    echo "<tr>";
    foreach ($line as $cell) {
        if ($header) {
            echo "<th >" . $cell . "</th>";
        } else {
            echo "<td>" . $cell . "</td>";
        }
    }
    echo "</tr>";
    if ($header) {
        echo "</thead>";
    }

}

$brewingTable = $prefix . "brewing";
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

    return $db->get("$brewingTable brewing", null, "brewing.id as brewId, brewBrewerLastName, brewBrewerFirstName, brewCoBrewer, brewStyle, brewJudgingNumber, brewPaid, brewReceived, brewInfo, brewComments, brewName, brewABV, brewPouring, brewPossAllergens");
}

$styles = get_styles();

foreach ($styles as $styleRow) {
    $style = $styleRow['brewStyle'];
    $id_prefix = '';
    $pattern = "/(?<=\s|^)[A-Z](?=\s|-|$)/";

    if (preg_match($pattern, $style, $matches)) {
        $id_prefix = $matches[0];
    }
    ?>
    <div class="page-break-after">
        <h3><?php echo $style; ?></h3>


        <table class="table table-responsive table-striped table-bordered dataTable no-footer">
            <?php


            $headers = array();
            $headers[] = "Vzorek";
            $headers[] = "ABV";
            $headers[] = "Info";
            $headers[] = "Komentář";
            $headers[] = "Alergeny";
            $headers[] = "Nalévání";
            $headers[] = "OK";
if ($staff) {
    $headers[] = "Prineseno";
    $headers[] = "Komise";
    $headers[] = "Zpet";
}

            put_line($headers, TRUE);


            echo "<tbody>";

            $entries = get_entries($style);

            foreach ($entries as $entry) {

                $line = array();
                $line[] = $id_prefix.$entry['brewId'];
                $line[] = $entry['brewABV'];
                $line[] = html_entity_decode($entry['brewInfo']);
                $line[] = html_entity_decode($entry['brewComments']);
                $line[] = html_entity_decode($entry['brewPossAllergens']);
                $line[] = showPouring(html_entity_decode($entry['brewPouring']));
                $line[] = $entry['brewPaid']==1 && $entry['brewReceived'] == 1 ? 'OK' : 'N/A';
                if ($staff) {
                    $line[] = "<input type='checkbox'>";
                    $line[] = "<input type='checkbox'>";
                    $line[] = "<input type='checkbox'>";
                }
//                $line[] = $entry['brewReceived'];
                put_line($line);
            }

            ?>


            </tbody>
        </table>
    </div>
    <?php
}
?>
</body>
</html>
