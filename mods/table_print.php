<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');
require_once(INCLUDES . 'url_variables.inc.php');
require_once(MODS . 'cech_common.php');

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$our_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$our_tables = isset($cech_config['tables']) ? $cech_config['tables'] : [];

$table_entry = null;
foreach ($our_tables as $t) {
    if ($t['id'] === $our_id) {
        $table_entry = $t;
        break;
    }
}

if (!$table_entry) {
    echo "<h2>Table not found.</h2>";
    die;
}

if (empty($table_entry['xtable_id'])) {
    echo "<h2>This table has no linked xtable.</h2>";
    die;
}

$xtablesTable = $prefix . "judging_tables";
$stylesTable  = $prefix . "styles";
$brewingTable = $prefix . "brewing";

function get_xtable_styles($xtable_id)
{
    global $xtablesTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('id', $xtable_id);
    $row = $db->getOne($xtablesTable, 'tableStyles');
    if (!$row || empty($row['tableStyles'])) return [];
    return array_values(array_filter(array_map('intval', explode(',', $row['tableStyles']))));
}

function get_styles_by_ids($ids)
{
    global $stylesTable, $connection;
    if (empty($ids)) return [];
    $db = new MysqliDb($connection);
    $db->where('id', $ids, 'IN');
    $db->orderBy('brewStyleGroup', 'asc');
    $db->orderBy('brewStyleNum', 'asc');
    return $db->get($stylesTable, null, 'id, brewStyleGroup, brewStyleNum, brewStyle');
}

function get_entries_for_style($brewStyleGroup, $brewStyleNum, $include_unready = false)
{
    global $brewingTable, $connection;
    $db = new MysqliDb($connection);
    $db->where('brewCategory', $brewStyleGroup);
    $db->where('brewSubCategory', $brewStyleNum);
    if (!$include_unready) {
        $db->where('brewPaid', 1);
        $db->where('brewReceived', 1);
    }
    $db->orderBy('id', 'asc');
    return $db->get($brewingTable, null,
        'id as brewId, brewABV, brewInfo, brewComments, brewPouring, brewPaid, brewReceived');
}

function showPouring($pouringRaw)
{
    $sep = "<br>";
    $pouringInfo = "";
    if ($pouringRaw && $pouringRaw != "[]") {
        $pouringDecoded = json_decode($pouringRaw, true);
        if ($pouringDecoded) {
            if (array_key_exists('pouring', $pouringDecoded)) {
                $speed = $pouringDecoded['pouring'];
                if ($speed == "Fast" || $speed == "Rychle") $pouringInfo .= "Nalévat rychle" . $sep;
                else if ($speed == "Slow" || $speed == "Pomalu") $pouringInfo .= "Nalévat pomalu" . $sep;
            }
            if (array_key_exists('pouring_rouse', $pouringDecoded) && ($pouringDecoded['pouring_rouse'] == "Yes" || $pouringDecoded['pouring_rouse'] == "Ano"))
                $pouringInfo .= "Probudit kvasnice" . $sep;
            if (array_key_exists('pouring_notes', $pouringDecoded) && $pouringDecoded['pouring_notes'])
                $pouringInfo .= "Poznámka: ".$pouringDecoded['pouring_notes'];
        }
    }
    return $pouringInfo;
}

$sort_by_epm = !empty($table_entry['sort_by_epm']);
$saved_epm   = isset($cech_config['epm']) ? $cech_config['epm'] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_epm') {
    $epm_raw = isset($_POST['epm']) && is_array($_POST['epm']) ? $_POST['epm'] : [];
    $epm_clean = [];
    foreach ($epm_raw as $bid => $val) {
        $epm_clean[(int)$bid] = trim($val);
    }
    if (!isset($cech_config['epm'])) $cech_config['epm'] = [];
    foreach ($epm_clean as $bid => $val) {
        $cech_config['epm'][$bid] = $val;
    }
    cech_config_save($cech_config);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $our_id);
    exit;
}

$style_ids = get_xtable_styles($table_entry['xtable_id']);
$styles    = get_styles_by_ids($style_ids);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Info – <?php echo htmlspecialchars($table_entry['name']); ?></title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <style>
        body { padding: 20px; }
        @page { size: A4 portrait;  }
        .entry-unready { display: none; background-color: #fff3cd; }
        .show-unready .entry-unready { display: table-row; }
        @media print {
            body { margin: 1.5cm; padding: 0; }
            .no-print { display: none; }
            .print-only { display: block; }
            .entry-unready { display: none !important; }
            h4 { page-break-after: avoid; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            input[type="text"] { border: none; box-shadow: none; background: transparent; padding: 0; width: auto; }
        }
        .print-only { display: none; }
    </style>
</head>
<body><div class="counter"></div>
<div class="container-fluid">

    <div class="no-print" style="margin-bottom:15px">
        <a href="javascript:window.print()" class="btn btn-default">Print</a>
        <a href="table_info.php" class="btn btn-link">Back</a>
        <label style="margin-left:15px; font-weight:normal; cursor:pointer">
            <input type="checkbox" id="toggle-unready"> Show unpaid/unreceived
        </label>
    </div>

    <div class="page-header">
        <?php if ((isset($_SESSION['contestLogo'])) && (!empty($_SESSION['contestLogo'])) && (file_exists(USER_IMAGES . $_SESSION['contestLogo']))): ?>
            <img src="<?php echo $base_url; ?>user_images/<?php echo $_SESSION['contestLogo']; ?>" class="pull-right" style="max-height: 80px;" alt="Competition Logo">
        <?php endif; ?>
        <h2><?php echo htmlspecialchars($table_entry['name']); ?></h2>
        <p>
            <strong>URL:</strong> <?php echo htmlspecialchars($base_url); ?><br>
            <strong>Username:</strong> <?php echo htmlspecialchars($table_entry['username']); ?><br>
            <strong>Password:</strong> <?php echo htmlspecialchars($table_entry['password']); ?>
        </p>
    </div>

    <?php if ($sort_by_epm): ?>
    <form method="post">
    <input type="hidden" name="action" value="save_epm">
    <?php endif; ?>

    <?php if (empty($styles)): ?>
        <p class="text-muted">No styles assigned to this xtable.</p>
    <?php else: ?>
        <?php foreach ($styles as $style):
            $entries = get_entries_for_style($style['brewStyleGroup'], $style['brewStyleNum'], true);
            if ($sort_by_epm) {
                usort($entries, function($a, $b) use ($saved_epm) {
                    $ea = (isset($saved_epm[$a['brewId']]) && $saved_epm[$a['brewId']] !== '') ? (float)$saved_epm[$a['brewId']] : PHP_FLOAT_MAX;
                    $eb = (isset($saved_epm[$b['brewId']]) && $saved_epm[$b['brewId']] !== '') ? (float)$saved_epm[$b['brewId']] : PHP_FLOAT_MAX;
                    return $ea <=> $eb;
                });
            }
        ?>
            <h4><?php echo htmlspecialchars($style['brewStyle']); ?></h4>
            <?php if (empty($entries)): ?>
                <p class="text-muted" style="margin-left:15px">No entries.</p>
            <?php else: ?>
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ABV</th>
                            <th>Info</th>
                            <th>Comment</th>
                            <th style="width:120px">Pouring</th>
                            <?php if ($sort_by_epm): ?>
                            <th style="width:1px;white-space:nowrap">EPM</th>
                            <?php endif; ?>
                            <th style="width:1px;white-space:nowrap">Received</th>
                            <th style="width:1px;white-space:nowrap">Judged</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e):
                            $is_ready = ($e['brewPaid'] == 1 && $e['brewReceived'] == 1);
                            $row_class = $is_ready ? '' : 'entry-unready';
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo htmlspecialchars($e['brewId']); ?></td>
                                <td><?php echo htmlspecialchars($e['brewABV'] ? $e['brewABV']."%" : ''); ?></td>
                                <td><?php echo htmlspecialchars(html_entity_decode($e['brewInfo'])); ?></td>
                                <td><?php echo htmlspecialchars(html_entity_decode($e['brewComments'])); ?></td>
                                <td><?php echo showPouring(html_entity_decode($e['brewPouring'])); ?></td>
                                <?php if ($sort_by_epm): ?>
                                <td><input type="text" name="epm[<?php echo $e['brewId']; ?>]"
                                           value="<?php echo htmlspecialchars(isset($saved_epm[$e['brewId']]) ? $saved_epm[$e['brewId']] : ''); ?>"
                                           style="width:55px"></td>
                                <?php endif; ?>
                                <?php if ($is_ready): ?>
                                <td><input type="checkbox"></td>
                                <td><input type="checkbox"></td>
                                <?php else: ?>
                                <td colspan="2" class="no-print text-muted small">
                                    <?php if (!$e['brewPaid']): ?>Unpaid<?php endif; ?>
                                    <?php if (!$e['brewReceived']): ?>Not received<?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($sort_by_epm): ?>
    <div class="no-print" style="margin-top:15px">
        <button type="submit" class="btn btn-primary">Save EPM</button>
    </div>
    </form>
    <?php endif; ?>

</div>
<script>
document.getElementById('toggle-unready').addEventListener('change', function() {
    document.body.classList.toggle('show-unready', this.checked);
});
</script>
</body>
</html>
