<?php
ini_set('display_errors', 1); // Change to 0 for prod; change to 1 for testing.
ini_set('display_startup_errors', 1); // Change to 0 for prod; change to 1 for testing.
error_reporting(E_ALL); // Change to error_reporting(0) for prod; change to E_ALL for testing.

require('../paths.php');
require(CONFIG . 'bootstrap.php');
require(INCLUDES . 'url_variables.inc.php');
require(LANG . 'language.lang.php');

$winner_method = $_SESSION['prefsWinnerMethod'];
$style_set = $_SESSION['prefsStyleSet'];
$pro_edition = $_SESSION['prefsProEdition'];
$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;


if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}


if (!function_exists('fputcsv')) {
    echo "<h1>Missing function fputcsv!";
    die;

}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=diplomas.csv');
header('Pragma: no-cache');
header('Expires: 0');
$fp = fopen('php://output', 'w');


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
    return $db->get($brewingTable, null, "DISTINCT brewStyle");

}

function get_entries($style)
{
    global $brewingTable;
    global $scoresTable;
    global $brewersTables;
    global $connection;

    $db = new MysqliDb($connection);
    $db->join("$scoresTable score", "score.eid=brewing.id", "LEFT");
    $db->join("$brewersTables brewers", "brewers.id=brewing.brewBrewerID", "LEFT");
    $db->where('brewStyle', $style);
    $db->where('scoreEntry > 35');
    $db->orderBy("score.scoreEntry", "desc");
    return $db->get("$brewingTable brewing", null, "brewing.id as brewId, brewBrewerLastName, brewBrewerFirstName, brewName, brewCoBrewer, brewStyle, brewJudgingNumber, brewPaid, brewReceived, score.scoreEntry, brewerClubs");
}

function get_style_id($styleName)
{
    $pattern = "/(?<=\s|^)[A-Z](?=\s|-|\)|$)/";

    if (preg_match($pattern, $styleName, $matches)) {
        return $matches[0];
    }

    return "X";
}

$shortStyles = array(
    "A" => "světlé pivo, spodně kvašené",
    "B" => "polotmavé a tmavé pivo, spodně kvašené",
    "C" => "svrchně kvašené pivo, mimo<br>pšenice a stout/porter",
    "D" => "pivo pšeničné",
    "E" => "stout/porter",
    "F" => "nakuřované pivo",
    "Q" => "kyseláče"
);


function put_line($line, $header = FALSE)
{
    global $fp;
    fputcsv($fp, $line, ';');

}


$styles = get_styles();

foreach ($styles as $stylearray) {
    $style = $stylearray['brewStyle'];


    $entries = get_entries($style);

    if (count($entries) > 0) {
        foreach ($entries as $entry) {

            $scoreEntry = $entry['scoreEntry'];
            if ($scoreEntry) {
                $score = $scoreEntry * 2;
                $line = array();
                $styleId = get_style_id($style);
                $line[] = $entry['brewId'];
                $line[] = $styleId;
                $line[] = $score;
                $line[] = html_entity_decode($entry['brewBrewerLastName']);
                $line[] = html_entity_decode($entry['brewBrewerFirstName']);
                $line[] = html_entity_decode($entry['brewCoBrewer']);
                $line[] = html_entity_decode($entry['brewName']);
                $line[] = html_entity_decode($entry['brewStyle']);
                $line[] = $entry['scorePlace'];
                $line[] = $shortStyles[$styleId];

                put_line($line);
            }

        }
    }
}
fclose($fp);
?>



