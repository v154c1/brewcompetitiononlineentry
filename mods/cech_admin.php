<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$brewingTable = $prefix . "brewing";
$evalTable    = $prefix . "evaluation";
$scoresTable  = $prefix . "judging_scores";
$tablesTable  = $prefix . "judging_tables";

function get_entries_count()
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewPaid', 1);
    $db->where('brewReceived', 1);
    return (int)$db->getValue($brewingTable, 'count(*)');
}

function get_entries_total()
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    return (int)$db->getValue($brewingTable, 'count(*)');
}

function get_evaluations_total_count()
{
    global $evalTable, $connection;
    $db = new MysqliDb($connection);
    return (int)$db->getValue($evalTable, 'count(*)');
}

function get_evaluations_distinct_count()
{
    global $evalTable, $connection;
    $db = new MysqliDb($connection);
    return (int)$db->getValue($evalTable, 'count(distinct eid)');
}

function get_score_count()
{
    global $scoresTable, $connection;
    $db = new MysqliDb($connection);
    return (int)$db->getValue($scoresTable, 'count(distinct eid)');
}

function get_placed_count()
{
    global $scoresTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('scorePlace', 0, '>');
    return (int)$db->getValue($scoresTable, 'count(distinct eid)');
}

function get_entries_paid()
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewPaid', 1);
    return (int)$db->getValue($brewingTable, 'count(*)');
}

function get_entries_paid_not_received()
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewPaid', 1);
    $db->where('(brewReceived IS NULL OR brewReceived = 0)');
    return (int)$db->getValue($brewingTable, 'count(*)');
}

function get_entries_received_not_paid()
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewReceived', 1);
    $db->where('(brewPaid IS NULL OR brewPaid = 0)');
    return (int)$db->getValue($brewingTable, 'count(*)');
}

function get_tables_count()
{
    global $tablesTable, $connection;
    $db = new MysqliDb($connection);
    return (int)$db->getValue($tablesTable, 'count(*)');
}

function get_category_stats()
{
    global $brewingTable, $evalTable, $scoresTable, $connection, $base_url;
    $db = new MysqliDb($connection);
    $query = "SELECT brewCategorySort, 
                     COUNT(*) as total, 
                     SUM(CASE WHEN brewPaid = 1 THEN 1 ELSE 0 END) as paid, 
                     SUM(CASE WHEN brewReceived = 1 THEN 1 ELSE 0 END) as received,
                     (SELECT COUNT(*) FROM $evalTable e JOIN $brewingTable b2 ON e.eid = b2.id WHERE b2.brewCategorySort = $brewingTable.brewCategorySort AND b2.brewConfirmed = 1) as evaluations_total,
                     (SELECT COUNT(DISTINCT e.eid) FROM $evalTable e JOIN $brewingTable b2 ON e.eid = b2.id WHERE b2.brewCategorySort = $brewingTable.brewCategorySort AND b2.brewConfirmed = 1) as evaluations_distinct,
                     (SELECT COUNT(DISTINCT s.eid) FROM $scoresTable s JOIN $brewingTable b3 ON s.eid = b3.id WHERE b3.brewCategorySort = $brewingTable.brewCategorySort AND b3.brewConfirmed = 1) as scores,
                     (SELECT COUNT(DISTINCT s.eid) FROM $scoresTable s JOIN $brewingTable b4 ON s.eid = b4.id WHERE b4.brewCategorySort = $brewingTable.brewCategorySort AND b4.brewConfirmed = 1 AND s.scorePlace IS NOT NULL AND s.scorePlace > 0) as placed
              FROM $brewingTable 
              WHERE brewConfirmed = 1 
              GROUP BY brewCategorySort 
              ORDER BY brewCategorySort ASC";
    $results = $db->rawQuery($query);

    $stats = [];
    foreach ($results as $row) {
        $cat_num = $row['brewCategorySort'];
        $cat_name = style_convert($cat_num, 1, $base_url);
        $stats[] = [
            'number' => $cat_num,
            'name' => $cat_name,
            'total' => (int)$row['total'],
            'paid' => (int)$row['paid'],
            'received' => (int)$row['received'],
            'evaluations_total' => (int)$row['evaluations_total'],
            'evaluations_distinct' => (int)$row['evaluations_distinct'],
            'scores' => (int)$row['scores'],
            'placed' => (int)$row['placed']
        ];
    }
    return $stats;
}

function format_date_cs($value)
{
    if (empty($value)) return '';
    $ts = is_numeric($value) ? (int)$value : strtotime($value);
    if ($ts === false || $ts === 0) return htmlspecialchars($value);
    setlocale(LC_TIME, 'cs_CZ.UTF-8', 'cs_CZ', 'czech');
    return strftime('%e. %B %Y %H:%M', $ts);
}

$scoresheet_names = [
    1  => 'Classic',
    2  => 'Checklist',
    3  => 'Structured',
    4  => 'Structured',
    37 => 'Holoubek (Varianta hodnocení stylu)',
    38 => 'Cech (Varianta Cech Domovarniku)',
];
$scoresheet_type = isset($_SESSION['jPrefsScoresheet']) ? (int)$_SESSION['jPrefsScoresheet'] : 1;
$scoresheet_name = isset($scoresheet_names[$scoresheet_type]) ? $scoresheet_names[$scoresheet_type] : 'Unknown ('.$scoresheet_type.')';

$entries_ok           = get_entries_count();
$entries_paid         = get_entries_paid();
$entries_total        = get_entries_total();
$evaluations_total    = get_evaluations_total_count();
$evaluations_distinct = get_evaluations_distinct_count();
$scores               = get_score_count();
$placed_total         = get_placed_count();
$tables_count         = get_tables_count();
$paid_not_received    = get_entries_paid_not_received();
$received_not_paid    = get_entries_received_not_paid();
$category_stats       = get_category_stats();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin – <?php echo htmlspecialchars($_SESSION['contestName']); ?></title>
    <?php
    if (CDN) include(INCLUDES . 'load_cdn_libraries.inc.php');
    else include(INCLUDES . 'load_local_libraries.inc.php');
    ?>
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
    <style>
        body { padding: 20px; }
        .stat-box { text-align: center; padding: 15px 0; }
        .stat-box .stat-num { font-size: 2.5em; font-weight: bold; line-height: 1; }
        .stat-box .stat-label { color: #888; font-size: 0.9em; margin-top: 4px; }
        .links-grid a { margin: 4px; }
        .tooltip-inner { text-align: left; max-width: 300px; }
    </style>
</head>
<body>
<div class="container-fluid">

    <h1><?php echo htmlspecialchars($_SESSION['contestName']); ?></h1>

    <!-- Competition info -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Competition info</strong></div>
        <div class="panel-body">
            <dl class="dl-horizontal">
                <dt>Scoresheet type</dt>
                <dd><?php echo htmlspecialchars($scoresheet_name); ?></dd>
                <?php if (!empty($_SESSION['contestHost'])): ?>
                    <dt>Host</dt>
                    <dd>
                        <?php echo htmlspecialchars($_SESSION['contestHost']); ?>
                        <?php if (!empty($_SESSION['contestHostWebsite'])): ?>
                            — <a href="<?php echo htmlspecialchars($_SESSION['contestHostWebsite']); ?>" target="_blank">
                                <?php echo htmlspecialchars($_SESSION['contestHostWebsite']); ?>
                            </a>
                        <?php endif; ?>
                    </dd>
                <?php endif; ?>
                <?php if (!empty($_SESSION['contestHostLocation'])): ?>
                    <dt>Location</dt>
                    <dd><?php echo htmlspecialchars($_SESSION['contestHostLocation']); ?></dd>
                <?php endif; ?>
                <?php if (!empty($_SESSION['contestAwardsLocName'])): ?>
                    <dt>Awards venue</dt>
                    <dd><?php echo htmlspecialchars($_SESSION['contestAwardsLocName']); ?></dd>
                <?php endif; ?>
                <?php if (!empty($_SESSION['contestAwardsLocDate'])): ?>
                    <dt>Awards date</dt>
                    <dd>
                        <?php echo format_date_cs($_SESSION['contestAwardsLocDate']); ?>
<!--                        --><?php //if (!empty($_SESSION['contestAwardsLocTime'])): ?>
<!--                            --><?php //echo htmlspecialchars($_SESSION['contestAwardsLocTime']); ?>
<!--                        --><?php //endif; ?>
                    </dd>
                <?php endif; ?>
                <?php if (!empty($_SESSION['contestEntryDeadline'])): ?>
                    <dt>Entry deadline</dt>
                    <dd><?php echo format_date_cs($_SESSION['contestEntryDeadline']); ?></dd>
                <?php endif; ?>
                <dt>Judging Start</dt>
                <dd><?php echo (isset($_SESSION['jPrefsJudgingOpen']) && !empty($_SESSION['jPrefsJudgingOpen'])) ? format_date_cs($_SESSION['jPrefsJudgingOpen']) : "<em>not set</em>"; ?></dd>
                <dt>Judging End</dt>
                <dd><?php echo (isset($_SESSION['jPrefsJudgingClosed']) && !empty($_SESSION['jPrefsJudgingClosed'])) ? format_date_cs($_SESSION['jPrefsJudgingClosed']) : "<em>not set</em>"; ?></dd>
                <dt>Judging Status</dt>
                <dd><?php 
                    $now = time();
                    if (isset($_SESSION['jPrefsJudgingOpen']) && isset($_SESSION['jPrefsJudgingClosed']) && $now >= $_SESSION['jPrefsJudgingOpen'] && $now <= $_SESSION['jPrefsJudgingClosed']) {
                        echo '<span class="label label-success" style="font-size: 1em;">OPEN</span>';
                    } else {
                        echo '<span class="label label-danger" style="font-size: 1em;">CLOSED</span>';
                    }
                ?></dd>
                <dt>Table Mode</dt>
                <dd><?php echo (isset($_SESSION['jPrefsTablePlanning']) && $_SESSION['jPrefsTablePlanning'] == 1) ? '<span class="label label-warning" style="font-size: 1em;">Planning</span>' : '<span class="label label-success" style="font-size: 1em;">Competition</span>'; ?></dd>
            </dl>
        </div>
    </div>

    <!-- Quick stats -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Judging state</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-2">
                    <div class="stat-box">
                        <?php $paid_class = ($entries_paid == $entries_total) ? 'text-success' : 'text-primary'; ?>
                        <div class="stat-num <?php echo $paid_class; ?>"><?php echo $entries_paid; ?> <small class="text-muted">/ <?php echo $entries_total; ?></small></div>
                        <div class="stat-label">Paid</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <?php $ok_class = ($entries_ok == $entries_total) ? 'text-success' : 'text-primary'; ?>
                        <div class="stat-num <?php echo $ok_class; ?>"><?php echo $entries_ok; ?> <small class="text-muted">/ <?php echo $entries_total; ?></small></div>
                        <div class="stat-label">Paid &amp; received</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <div class="stat-num text-primary"><?php echo $tables_count; ?></div>
                        <div class="stat-label">Judging tables</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <?php $eval_class = ($evaluations_distinct == $entries_ok) ? 'text-success' : 'text-primary'; ?>
                        <div class="stat-num <?php echo $eval_class; ?>"><?php echo $evaluations_total; ?> <small class="text-muted">(<?php echo $evaluations_distinct; ?> / <?php echo $entries_ok; ?>)</small></div>
                        <div class="stat-label">Evaluations</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <?php $score_class = ($scores == $entries_ok) ? 'text-success' : 'text-primary'; ?>
                        <div class="stat-num <?php echo $score_class; ?>"><?php echo $scores; ?> <small class="text-muted">(<?php echo $placed_total; ?>)</small></div>
                        <div class="stat-label">Scores entered</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($paid_not_received > 0 || $received_not_paid > 0): ?>
    <div class="alert alert-warning">
        <strong>Entry status mismatch:</strong>
        <?php if ($paid_not_received > 0): ?>
            <span style="margin-right:16px">&#9888; <?php echo $paid_not_received; ?> paid but not received</span>
        <?php endif; ?>
        <?php if ($received_not_paid > 0): ?>
            <span>&#9888; <?php echo $received_not_paid; ?> received but not paid</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Entries by Category -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Entries by Category</strong></div>
        <div class="panel-body">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-center">Entries</th>
                        <th class="text-center">Paid</th>
                        <th class="text-center">Received</th>
                        <th class="text-center">Evaluations</th>
                        <th class="text-center">Scores</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($category_stats as $stat): 
                        $tooltip = "<strong>Category:</strong> " . htmlspecialchars($stat['number'] . ' - ' . $stat['name']) . "<br>";
                        $tooltip .= "<strong>Total Entries:</strong> " . $stat['total'] . "<br>";
                        $tooltip .= "<strong>Paid:</strong> " . $stat['paid'] . "<br>";
                        $tooltip .= "<strong>Received:</strong> " . $stat['received'] . "<br>";
                        $tooltip .= "<strong>Total Evaluations:</strong> " . $stat['evaluations_total'] . "<br>";
                        $tooltip .= "<strong>Unique Entries Evaluated:</strong> " . $stat['evaluations_distinct'] . " / " . $stat['total'] . "<br>";
                        $tooltip .= "<strong>Scores Entered:</strong> " . $stat['scores'] . "<br>";
                        $tooltip .= "<strong>Placed:</strong> " . $stat['placed'];
                    ?>
                    <tr data-toggle="tooltip" data-html="true" data-placement="auto top" title="<?php echo htmlspecialchars($tooltip); ?>">
                        <td><?php echo htmlspecialchars($stat['number']); ?> - <?php echo htmlspecialchars($stat['name']); ?></td>
                        <td class="text-center"><?php echo $stat['total']; ?></td>
                        <td class="text-center"><?php echo $stat['paid']; ?></td>
                        <td class="text-center"><?php echo $stat['received']; ?></td>
                        <td class="text-center"><?php echo $stat['evaluations_total']; ?> (<?php echo $stat['evaluations_distinct']; ?> / <?php echo $stat['received']; ?>)</td>
                        <td class="text-center"><?php echo $stat['scores']; ?> (<?php echo $stat['placed']; ?>)</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Links -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Judging prep</strong></div>
        <div class="panel-body links-grid">
            <a href="table_info.php" class="btn btn-default">Table Info</a>
            <a href="codes.php" class="btn btn-default">QR Labels</a>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Status</strong></div>
        <div class="panel-body links-grid">
            <a href="status.php" class="btn btn-default">Judging Status</a>
            <a href="evaluation.php" class="btn btn-default">Evaluation Status</a>
            <a href="entries.php" class="btn btn-default">Entry List</a>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Results</strong></div>
        <div class="panel-body links-grid">
            <a href="diplomas-data.php" class="btn btn-default">Diploma Data</a>
            <a href="page.php" class="btn btn-default">Results Page</a>
            <a href="diploma-editor.php" class="btn btn-default">Diploma Editor</a>
            <a href="diplomas-print.php" class="btn btn-default">Print Diplomas</a>
        </div>
    </div>

</div>
</body>
</html>
