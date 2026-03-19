<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('../paths.php');
require(CONFIG . 'bootstrap.php');
require(INCLUDES . 'url_variables.inc.php');
require(LANG . 'language.lang.php');

// Define table names using $prefix from site/config.php (included via bootstrap)
global $prefix;
$stylesTable = $prefix . "styles";
$brewingTable = $prefix . "brewing";
$scoresTable = $prefix . "judging_scores";
$brewersTable = $prefix . "brewer";

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

if (!function_exists('fputcsv')) {
    echo "<h1>Missing function fputcsv!</h1>";
    die;
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

function get_style_id($styleName)
{
    $pattern = "/(?<=\s|^)[A-Z](?=\s|-|\)|$)/";
    if (preg_match($pattern, $styleName, $matches)) {
        return $matches[0];
    }
    return "X";
}

function get_filtered_entries($style, $place, $color)
{
    global $brewingTable;
    global $scoresTable;
    global $brewersTable;
    global $connection;

    $db = new MysqliDb($connection);
    $db->join("$scoresTable score", "score.eid=brewing.id", "LEFT");
    $db->join("$brewersTable brewers", "brewers.id=brewing.brewBrewerID", "LEFT");
    
    if ($style != 'all') {
        $db->where('brewStyle', $style);
    }

    if ($place != 'all') {
        if ($place == 'none') {
            $db->where('(score.scorePlace IS NULL OR score.scorePlace = "")');
        } else {
            $db->where('score.scorePlace', $place);
        }
    }

    if ($color != 'all') {
        if ($color == 'gold') {
            $db->where('score.scoreEntry', array(45, 50), 'BETWEEN');
        } elseif ($color == 'silver') {
            $db->where('score.scoreEntry', array(41, 44), 'BETWEEN');
        } elseif ($color == 'bronze') {
            $db->where('score.scoreEntry', array(36, 40), 'BETWEEN');
        } elseif ($color == 'none') {
            $db->where('score.scoreEntry', 41, '<');
        }
    } else {
        $db->where('(score.scoreEntry > 35 OR score.scorePlace IS NOT NULL)');
    }

    $db->orderBy("brewStyle", "asc");
    $db->orderBy("score.scoreEntry", "desc");
    
    return $db->get("$brewingTable brewing", null, "brewing.id as brewId, brewBrewerLastName, brewBrewerFirstName, brewName, brewCoBrewer, brewStyle, brewJudgingNumber, score.scoreEntry, brewerClubs, score.scorePlace");
}

$selected_style = isset($_GET['style']) ? $_GET['style'] : 'all';
$selected_place = isset($_GET['place']) ? $_GET['place'] : 'all';
$selected_color = isset($_GET['color']) ? $_GET['color'] : 'all';
$view = isset($_GET['view']) ? $_GET['view'] : 'attachment';

$entries = get_filtered_entries($selected_style, $selected_place, $selected_color);

if ($view == 'inline') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename=diplomas.csv');
} else {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=diplomas.csv');
}
header('Pragma: no-cache');
header('Expires: 0');

$fp = fopen('php://output', 'w');

// Header line
fputcsv($fp, array('ID', 'Short Style', 'Style', 'Place', 'Score', 'Brewer', 'Co-Brewer', 'Entry Name', 'Club', 'Description'), ';');

foreach ($entries as $entry) {
    $styleId = get_style_id($entry['brewStyle']);
    $line = array(
        $entry['brewId'],
        $styleId,
        html_entity_decode($entry['brewStyle']),
        $entry['scorePlace'] ? $entry['scorePlace'] : '-',
        $entry['scoreEntry'],
        html_entity_decode($entry['brewBrewerFirstName'] . ' ' . $entry['brewBrewerLastName']),
        html_entity_decode($entry['brewCoBrewer']),
        html_entity_decode($entry['brewName']),
        html_entity_decode($entry['brewerClubs']),
        isset($shortStyles[$styleId]) ? str_replace('<br>', ' ', $shortStyles[$styleId]) : ''
    );
    fputcsv($fp, $line, ';');
}

fclose($fp);
?>
