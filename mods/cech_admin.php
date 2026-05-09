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

function get_evaluations_count()
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
$evaluations          = get_evaluations_count();
$scores               = get_score_count();
$tables_count         = get_tables_count();
$paid_not_received    = get_entries_paid_not_received();
$received_not_paid    = get_entries_received_not_paid();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Admin – <?php echo htmlspecialchars($_SESSION['contestName']); ?></title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <style>
        body { padding: 20px; }
        .stat-box { text-align: center; padding: 15px 0; }
        .stat-box .stat-num { font-size: 2.5em; font-weight: bold; line-height: 1; }
        .stat-box .stat-label { color: #888; font-size: 0.9em; margin-top: 4px; }
        .links-grid a { margin: 4px; }
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
                <?php if (!empty($_SESSION['contestJudgeDeadline'])): ?>
                    <dt>Judging deadline</dt>
                    <dd><?php echo format_date_cs($_SESSION['contestJudgeDeadline']); ?></dd>
                <?php endif; ?>
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
                        <div class="stat-num"><?php echo $entries_paid; ?> <small class="text-muted">/ <?php echo $entries_total; ?></small></div>
                        <div class="stat-label">Paid</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <div class="stat-num"><?php echo $entries_ok; ?> <small class="text-muted">/ <?php echo $entries_total; ?></small></div>
                        <div class="stat-label">Paid &amp; received</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <div class="stat-num"><?php echo $tables_count; ?></div>
                        <div class="stat-label">Judging tables</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <div class="stat-num"><?php echo $evaluations; ?></div>
                        <div class="stat-label">Evaluations</div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="stat-box">
                        <div class="stat-num"><?php echo $scores; ?></div>
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
