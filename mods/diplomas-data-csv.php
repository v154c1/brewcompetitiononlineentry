<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('../paths.php');
require(CONFIG . 'bootstrap.php');
require(INCLUDES . 'url_variables.inc.php');
require(LANG . 'language.lang.php');
require_once(MODS . 'cech_common.php');

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
    "A" => "Světlý ležák",
    "B" => "Polotmavý a tmavý ležák",
    "C" => "Pale ale",
    "D" => "IPL experimental",
    "E" => "Baltic porter",
    "G" => "Klášterní pivo",
    "F" => "Divoká karta",
    "H" => "Divoká karta",
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
    global $cech_config;

    $thresholds = $cech_config['score_thresholds'];

    $db = new MysqliDb($connection);
    $db->join("$scoresTable score", "score.eid=brewing.id", "LEFT");
    $db->join("$brewersTable brewers", "brewers.id=brewing.brewBrewerID", "LEFT");
    
    if ($style != 'all') {
        $db->where('brewStyle', $style);
    }

    if ($place != 'all') {
        if ($place == 'none') {
            $db->where('(score.scorePlace IS NULL OR score.scorePlace = "")');
        } elseif ($place == 'any') {
            $db->where('(score.scorePlace IS NOT NULL AND score.scorePlace > 0)');
        } else {
            $db->where('score.scorePlace', $place);
        }
    }

    if ($color != 'all') {
        if ($color == 'gold') {
            $db->where('score.scoreEntry', array($thresholds['gold'], 50), 'BETWEEN');
        } elseif ($color == 'silver') {
            $db->where('score.scoreEntry', array($thresholds['silver'], $thresholds['gold'] - 1), 'BETWEEN');
        } elseif ($color == 'bronze') {
            $db->where('score.scoreEntry', array($thresholds['bronze'], $thresholds['silver'] - 1), 'BETWEEN');
        } elseif ($color == 'none') {
            $db->where('score.scoreEntry', $thresholds['bronze'], '<');
        }
    } else {
        $db->where('(score.scoreEntry >= ' . $thresholds['bronze'] . ' OR score.scorePlace IS NOT NULL)');
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
fputcsv($fp, array('ID', 'Short Style', 'Style', 'Place', 'Score', 'Brewer', 'Co-Brewer', 'Entry Name', 'Club', 'Description', 'Score2', 'place_text', 'score_text', 'brewers_text'), ';');

foreach ($entries as $entry) {
    $styleId = get_style_id($entry['brewStyle']);
    $score2 = $entry['scoreEntry'] * 2;
    $place = $entry['scorePlace'];
    $brewer = html_entity_decode($entry['brewBrewerFirstName'] . ' ' . $entry['brewBrewerLastName']);
    $cobrewer = html_entity_decode($entry['brewCoBrewer']);
    $line = array(
        $entry['brewId'],
        $styleId,
        html_entity_decode($entry['brewStyle']),
        $place ? $place : '-',
        $entry['scoreEntry'],
        $brewer,
        $cobrewer,
        html_entity_decode($entry['brewName']),
        html_entity_decode($entry['brewerClubs']),
        isset($shortStyles[$styleId]) ? str_replace('<br>', ' ', $shortStyles[$styleId]) : '',
        $score2,
        ($place && $place > 0) ? 'Za ' . $place . '. místo' : '',
        html_entity_decode($entry['brewName']). ', ' . $score2 . ' b.',
        $cobrewer ? $brewer . ', ' . $cobrewer : $brewer,
    );
    fputcsv($fp, $line, ';');
}

fclose($fp);
?>
