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

function get_styles()
{
    global $brewingTable;
    global $connection;
    $db = new MysqliDb($connection);
    $db->orderBy("brewStyle", "asc");
    return $db->get($brewingTable, null, "DISTINCT brewStyle");
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
        } elseif ($place == 'any') {
            $db->where('(score.scorePlace IS NOT NULL AND score.scorePlace > 0)');
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
        // Default filter from diplomas-output.php if "all" is selected?
        // "scoreEntry > 35 or scorePlace is not null"
        $db->where('(score.scoreEntry > 35 OR score.scorePlace IS NOT NULL)');
    }

    $db->orderBy("brewStyle", "asc");
    $db->orderBy("score.scoreEntry", "desc");
    
    return $db->get("$brewingTable brewing", null, "brewing.id as brewId, brewBrewerLastName, brewBrewerFirstName, brewName, brewCoBrewer, brewStyle, brewJudgingNumber, score.scoreEntry, brewerClubs, score.scorePlace");
}

$styles = get_styles();

$selected_style = isset($_POST['style']) ? $_POST['style'] : 'all';
$selected_place = isset($_POST['place']) ? $_POST['place'] : 'all';
$selected_color = isset($_POST['color']) ? $_POST['color'] : 'all';

$entries = array();
if (isset($_POST['show'])) {
    $entries = get_filtered_entries($selected_style, $selected_place, $selected_color);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Diploma Data</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <style>
        body { padding: 20px; }
        .filters { margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1>Diploma Data</h1>
        
        <div class="filters">
            <form method="post" class="form-inline">
                <div class="form-group">
                    <label for="style">Style:</label>
                    <select name="style" id="style" class="form-control">
                        <option value="all">All Styles</option>
                        <?php foreach ($styles as $s): 
                            $style_option_val = html_entity_decode($s['brewStyle']);
                            ?>
                            <option value="<?php echo htmlspecialchars($style_option_val); ?>" <?php if ($selected_style == $style_option_val) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($style_option_val); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="place">Place:</label>
                    <select name="place" id="place" class="form-control">
                        <option value="all">All Places</option>
                        <option value="1" <?php if ($selected_place == '1') echo 'selected'; ?>>1st Place</option>
                        <option value="2" <?php if ($selected_place == '2') echo 'selected'; ?>>2nd Place</option>
                        <option value="3" <?php if ($selected_place == '3') echo 'selected'; ?>>3rd Place</option>
                        <option value="any" <?php if ($selected_place == 'any') echo 'selected'; ?>>Any Place</option>
                        <option value="none" <?php if ($selected_place == 'none') echo 'selected'; ?>>No Place</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="color-">Diploma Color:</label>
                    <select name="color" id="color" class="form-control">
                        <option value="all">All Colors</option>
                        <option value="gold" <?php if ($selected_color == 'gold') echo 'selected'; ?>>Gold (45-50)</option>
                        <option value="silver" <?php if ($selected_color == 'silver') echo 'selected'; ?>>Silver (41-44)</option>
                        <option value="bronze" <?php if ($selected_color == 'bronze') echo 'selected'; ?>>Bronze (36-40)</option>
                        <option value="none" <?php if ($selected_color == 'none') echo 'selected'; ?>>No Color (< 41)</option>
                    </select>
                </div>
                
                <button type="submit" name="show" class="btn btn-primary">Show</button>
                
                <?php if (isset($_POST['show'])): 
                    $csv_url = "diplomas-data-csv.php?style=" . urlencode($selected_style) . "&place=" . urlencode($selected_place) . "&color=" . urlencode($selected_color);
                ?>
                    <a href="<?php echo $csv_url; ?>&view=inline" target="_blank" class="btn btn-info">Open CSV</a>
                    <a href="<?php echo $csv_url; ?>&view=attachment" class="btn btn-success">Download CSV</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_POST['show'])): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Short Style</th>
                        <th>Style</th>
                        <th>Place</th>
                        <th>Score</th>
                        <th>Brewer</th>
                        <th>Co-Brewer</th>
                        <th>Entry Name</th>
                        <th>Club</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($entries) > 0): ?>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?php echo $entry['brewId']; ?></td>
                                <td><?php 
                                    $styleId = get_style_id($entry['brewStyle']);
                                    echo $styleId; 
                                ?></td>
                                <td><?php echo html_entity_decode($entry['brewStyle']); ?></td>
                                <td><?php echo $entry['scorePlace'] ? $entry['scorePlace'] : '-'; ?></td>
                                <td><?php echo $entry['scoreEntry']; ?></td>
                                <td><?php echo html_entity_decode($entry['brewBrewerFirstName'] . ' ' . html_entity_decode($entry['brewBrewerLastName'])); ?></td>
                                <td><?php echo html_entity_decode($entry['brewCoBrewer']); ?></td>
                                <td><?php echo html_entity_decode($entry['brewName']); ?></td>
                                <td><?php echo html_entity_decode($entry['brewerClubs']); ?></td>
                                <td><?php echo isset($shortStyles[$styleId]) ? $shortStyles[$styleId] : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                        <td colspan="10">No entries found for the selected criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
