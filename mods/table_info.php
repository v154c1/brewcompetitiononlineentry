<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');
require_once(MODS . 'cech_common.php');

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

$tablesTable = $prefix . "judging_tables";

// POST handlers (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $our_tables = isset($cech_config['tables']) ? $cech_config['tables'] : [];

    if ($action === 'save') {
        $name      = trim(isset($_POST['name'])       ? $_POST['name']       : '');
        $username  = trim(isset($_POST['username'])   ? $_POST['username']   : '');
        $password  = trim(isset($_POST['password'])   ? $_POST['password']   : '');
        $xtable_id   = (int)(isset($_POST['xtable_id']) ? $_POST['xtable_id']  : 0);
        $sort_by_epm = isset($_POST['sort_by_epm']) ? TRUE : FALSE;
        $id          = (isset($_POST['id']) && $_POST['id'] !== '') ? (int)$_POST['id'] : null;

        if ($id !== null) {
            foreach ($our_tables as &$t) {
                if ($t['id'] === $id) {
                    $t['name']        = $name;
                    $t['username']    = $username;
                    $t['password']    = $password;
                    $t['xtable_id']   = $xtable_id;
                    $t['sort_by_epm'] = $sort_by_epm;
                    break;
                }
            }
            unset($t);
        } else {
            $new_id = empty($our_tables) ? 1 : max(array_column($our_tables, 'id')) + 1;
            $our_tables[] = [
                'id'          => $new_id,
                'name'        => $name,
                'username'    => $username,
                'password'    => $password,
                'xtable_id'   => $xtable_id,
                'sort_by_epm' => $sort_by_epm,
            ];
        }

        $cech_config['tables'] = $our_tables;
        cech_config_save($cech_config);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } elseif ($action === 'delete') {
        $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $filtered = [];
        foreach ($our_tables as $t) {
            if ($t['id'] !== $id) $filtered[] = $t;
        }
        $cech_config['tables'] = $filtered;
        cech_config_save($cech_config);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

function get_xtables()
{
    global $tablesTable, $connection;
    $db = new MysqliDb($connection);
    $db->orderBy('tableName', 'asc');
    return $db->get($tablesTable, null, 'id, tableName');
}

$xtables    = get_xtables();
$our_tables = isset($cech_config['tables']) ? $cech_config['tables'] : [];

$xtable_map = [];
foreach ($xtables as $xt) {
    $xtable_map[$xt['id']] = $xt['tableName'];
}

$edit_id    = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_entry = null;
if ($edit_id !== null) {
    foreach ($our_tables as $t) {
        if ($t['id'] === $edit_id) {
            $edit_entry = $t;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Table Info</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <style>
        body { padding: 20px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <h1>Table Info</h1>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Our tables</strong></div>
        <div class="panel-body">
            <?php if (empty($our_tables)): ?>
                <p class="text-muted">No tables defined yet.</p>
            <?php else: ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Linked xtable</th>
                            <th>Sort by EPM</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($our_tables as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['name']); ?></td>
                                <td><?php echo htmlspecialchars($t['username']); ?></td>
                                <td><?php echo htmlspecialchars($t['password']); ?></td>
                                <td><?php
                                    if (!empty($t['xtable_id']) && isset($xtable_map[$t['xtable_id']])) {
                                        echo htmlspecialchars($xtable_map[$t['xtable_id']]);
                                    } else {
                                        echo '<em class="text-muted">&mdash;</em>';
                                    }
                                ?></td>
                                <td><?php echo !empty($t['sort_by_epm']) ? '&#10003;' : ''; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $t['id']; ?>" class="btn btn-xs btn-info">Edit</a>
                                    <?php if (!empty($t['xtable_id'])): ?>
                                        <a href="table_print.php?id=<?php echo $t['id']; ?>"
                                           class="btn btn-xs btn-default" >Info</a>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline"
                                          onsubmit="return confirm('Delete this table?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <strong><?php echo $edit_entry ? 'Edit table' : 'Add new table'; ?></strong>
            <?php if ($edit_entry): ?>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                   class="pull-right btn btn-xs btn-default">Cancel</a>
            <?php endif; ?>
        </div>
        <div class="panel-body">
            <form method="post" class="form-horizontal">
                <input type="hidden" name="action" value="save">
                <?php if ($edit_entry): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_entry['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="col-sm-2 control-label">Name</label>
                    <div class="col-sm-4">
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo $edit_entry ? htmlspecialchars($edit_entry['name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">Username</label>
                    <div class="col-sm-4">
                        <input type="text" name="username" class="form-control"
                               value="<?php echo $edit_entry ? htmlspecialchars($edit_entry['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">Password</label>
                    <div class="col-sm-4">
                        <input type="text" name="password" class="form-control"
                               value="<?php echo $edit_entry ? htmlspecialchars($edit_entry['password']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">Linked xtable</label>
                    <div class="col-sm-4">
                        <select name="xtable_id" class="form-control">
                            <option value="0">&mdash; none &mdash;</option>
                            <?php foreach ($xtables as $xt): ?>
                                <option value="<?php echo $xt['id']; ?>"
                                    <?php if ($edit_entry && $edit_entry['xtable_id'] == $xt['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($xt['tableName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-4">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sort_by_epm"
                                    <?php if ($edit_entry && !empty($edit_entry['sort_by_epm'])) echo 'checked'; ?>>
                                Sort by EPM
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-4">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
