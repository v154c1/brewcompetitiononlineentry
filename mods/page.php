<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('../paths.php');
require(CONFIG . 'bootstrap.php');

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$brewingTable = $prefix . "brewing";
$scoresTable  = $prefix . "judging_scores";
$brewersTable = $prefix . "brewer";

function get_styles()
{
    global $brewingTable, $scoresTable, $connection;
    $db = new MysqliDb($connection);
    $db->join($scoresTable . " score", "score.eid = brewing.id", "INNER");
    $db->where('(score.scoreEntry > 35 OR (score.scorePlace IS NOT NULL AND score.scorePlace != ""))');
    $db->orderBy('brewing.brewStyle', 'asc');
    $rows = $db->get($brewingTable . " brewing", null, 'DISTINCT brewing.brewStyle');
    return array_column($rows, 'brewStyle');
}

function get_entries_for_style($style)
{
    global $brewingTable, $scoresTable, $brewersTable, $connection;
    $db = new MysqliDb($connection);
    $db->join($scoresTable . " score", "score.eid = brewing.id", "LEFT");
    $db->join($brewersTable . " brewer", "brewer.id = brewing.brewBrewerID", "LEFT");
    $db->where('brewing.brewStyle', $style);
    $db->where('(score.scoreEntry > 35 OR (score.scorePlace IS NOT NULL AND score.scorePlace != ""))');
    $db->orderBy('score.scoreEntry', 'desc');
    return $db->get($brewingTable . " brewing", null,
        'brewing.id, brewBrewerFirstName, brewBrewerLastName, brewCoBrewer, brewName, brewStyle, score.scoreEntry, score.scorePlace');
}

function diploma_class($scoreEntry)
{
    if ($scoreEntry >= 45) return 'goldenDiplom';
    if ($scoreEntry >= 41) return 'silverDiplom';
    if ($scoreEntry >= 36) return 'bronzeDiplom';
    return '';
}

function diploma_name_cs($scoreEntry)
{
    if ($scoreEntry >= 45) return 'Zlato';
    if ($scoreEntry >= 41) return 'Stříbro';
    if ($scoreEntry >= 36) return 'Bronz';
    return '';
}

$styles = get_styles();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($_SESSION['contestName']); ?> – Results</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <style>
        body { padding: 20px; }
        .goldenDiplom { background-color: #ffd700; }
        .silverDiplom { background-color: #c0c0c0; }
        .bronzeDiplom { background-color: #bfa396; }
        .category-block + .category-block { page-break-before: always; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="container-fluid">

    <div class="no-print" style="margin-bottom:15px">
        <a href="cech_admin.php" class="btn btn-default">Back</a>
    </div>

    <h1><?php echo htmlspecialchars($_SESSION['contestName']); ?></h1>

    <?php if (empty($styles)): ?>
        <p class="text-muted">No diploma-level results yet.</p>
    <?php else: ?>
        <?php foreach ($styles as $style):
            $entries = get_entries_for_style($style);
            if (empty($entries)) continue;

            usort($entries, function($a, $b) {
                $ap = !empty($a['scorePlace']);
                $bp = !empty($b['scorePlace']);
                if ($ap && $bp)  return (int)$a['scorePlace'] - (int)$b['scorePlace'];
                if ($ap)         return -1;
                if ($bp)         return 1;
                return (float)$b['scoreEntry'] - (float)$a['scoreEntry'];
            });
        ?>
            <div class="category-block">
            <h3><?php echo htmlspecialchars(html_entity_decode($style)); ?></h3>
            <table class="table table-bordered table-condensed">
                <thead>
                    <tr>
                        <th>Umístění</th>
                        <th>Sládek</th>
                        <th>Podsládek</th>
                        <th>Vzorek</th>
                        <th>Skóre</th>
                        <th>Diplom</th>
                        <th class="no-print"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $e):
                        $class = diploma_class((float)$e['scoreEntry']);
                    ?>
                        <tr class="<?php echo $class; ?>">
                            <td><?php echo !empty($e['scorePlace']) ? htmlspecialchars($e['scorePlace']) : '&mdash;'; ?></td>
                            <td><?php echo htmlspecialchars(html_entity_decode($e['brewBrewerFirstName']) . ' ' . html_entity_decode($e['brewBrewerLastName'])); ?></td>
                            <td><?php echo htmlspecialchars(html_entity_decode($e['brewCoBrewer'])); ?></td>
                            <td><?php echo htmlspecialchars(html_entity_decode($e['brewName'])); ?></td>
                            <td><?php echo htmlspecialchars($e['scoreEntry']*2); ?></td>
                            <td><?php echo diploma_name_cs((float)$e['scoreEntry']); ?></td>
                            <td class="no-print"><a href="diploma-preview.php?id=<?php echo $e['id']; ?>" target="_blank" class="btn btn-xs btn-default">Diplom</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>
