<?php
ini_set('display_errors', 1); // Change to 0 for prod; change to 1 for testing.
ini_set('display_startup_errors', 1); // Change to 0 for prod; change to 1 for testing.
error_reporting(E_ALL); // Change to error_reporting(0) for prod; change to E_ALL for testing.


if (!function_exists('fputcsv')) {
    echo "<h1>Missing function fputcsv!";
    die;

}
require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');
$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$output_csv = FALSE;
$fp = null;
if (isset($_GET['csv'])) {
    $output_csv = TRUE;
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=entries.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    $fp = fopen('php://output', 'w');
} else {
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

<a href="?csv">Stáhnout CSV</a><br>
<table class="table table-responsive table-striped table-bordered dataTable no-footer" id="entryTable">
    <?php

    }

    function showPouring($pouringRaw)
    {
        global $output_csv;
        $sep = $output_csv? "\n" : "<br>";
        $pouringInfo ="";

        if ($pouringRaw && $pouringRaw != "[]") {
            $pouringDecoded =  json_decode($pouringRaw, true);
            if ($pouringDecoded) {
                if (array_key_exists('pouring', $pouringDecoded)) {
                    $speed = $pouringDecoded['pouring'];
                    if ($speed == "Fast") {
                        $pouringInfo = $pouringInfo."Nalévat rychle".$sep;
                    } else if ($speed == "Slow") {
                        $pouringInfo = $pouringInfo."Nalévat pomalu".$sep;
                    }
                }
                if (array_key_exists('pouring_rouse', $pouringDecoded)) {
                    if ($pouringDecoded['pouring_rouse'] == "Yes") {
                        $pouringInfo = $pouringInfo."Probudit kvasnice".$sep;
                    }
                }

                if (array_key_exists('pouring_notes', $pouringDecoded)) {
                    $notes = $pouringDecoded['pouring_notes'];
                    if ($notes) {
                        $pouringInfo = $pouringInfo.$notes;
                    }
                }

            }
        }

        return $pouringInfo;
    }

    function put_line($line, $header = FALSE)
    {
        global $fp;
        global $output_csv;
        if ($output_csv) {
            fputcsv($fp, $line, ';');
        } else {
            if ($header) {
                echo "<thead>";
            }
            echo "<tr>";
            foreach ($line as $cell) {
                if ($header) {
                    echo "<th class=\"sorting\" aria-controls=\"sortable\">" . $cell . "</th>";
                } else {
                    echo "<td>" . $cell . "</td>";
                }
            }
            echo "</tr>";
            if ($header) {
                echo "</thead>";
            }
        }
    }


    $headers = array();
    $headers[] = "Číslo vzorku";
    $headers[] = "Příjmení";
    $headers[] = "Jméno";
    $headers[] = "Podsládek";
    $headers[] = "Název vzorku";
    $headers[] = "Kategorie";
    $headers[] = "ABV";
    $headers[] = "Info";
    $headers[] = "Komentář";
    $headers[] = "Alergeny";
    $headers[] =  "Instrukce nalevani";
    $headers[] = "Hodnocení";
    $headers[] = "Zaplaceno";
    $headers[] = "Přijato";


    put_line($headers, TRUE);

    if (!$output_csv) {
        echo "<tbody>";
    }


    //$db_conn->join("brewer", "brewer.id=brewing.brewBrewerID ", "LEFT");
    $db = new MysqliDb($connection);
    $db->join($prefix . "judging_scores score", "score.eid=brewing.id", "LEFT");
    $db->orderBy("brewing.id", "asc");
    $entries = $db->get($prefix . "brewing as brewing", null, "brewing.id as brewId, brewBrewerLastName, brewBrewerFirstName, brewCoBrewer, brewStyle, brewJudgingNumber, brewPaid, brewReceived, brewInfo, brewComments, brewName, score.scoreEntry, brewABV, brewPouring, brewPossAllergens");

    foreach ($entries as $entry) {
//    print_r($entry);

        
        $line = array();
        $line[] = $entry['brewId'];
        $line[] = html_entity_decode($entry['brewBrewerLastName']);
        $line[] = html_entity_decode($entry['brewBrewerFirstName']);
        $line[] = html_entity_decode($entry['brewCoBrewer']);
        $line[] = html_entity_decode($entry['brewName']);
        $line[] = html_entity_decode($entry['brewStyle']);
        $line[] = $entry['brewABV'];
        $line[] = html_entity_decode($entry['brewInfo']);
        $line[] = html_entity_decode($entry['brewComments']);
        $line[] = html_entity_decode($entry['brewPossAllergens']);
        $line[] = showPouring(html_entity_decode($entry['brewPouring']));
        $line[] = $entry['scoreEntry'];
        $line[] = $entry['brewPaid'];
        $line[] = $entry['brewReceived'];
        put_line($line);
    }

    ?>


    <?php

    if ($output_csv) {


        fclose($fp);
    } else {
    ?>
</tbody>
</table>
</body>
</html>
<?php
}

?>
