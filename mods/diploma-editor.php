<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../paths.php');
require_once(CONFIG . 'bootstrap.php');
require_once(INCLUDES . 'url_variables.inc.php');
require_once(MODS . 'cech_common.php');
require_once(MODS . 'cech-print-common.php');

$admin_role = FALSE;
if ((isset($_SESSION['loginUsername'])) && ($_SESSION['userLevel'] <= 1)) $admin_role = TRUE;

if (!$admin_role) {
    echo "<h1>Access denied!</h1>";
    die;
}

if (!isset($cech_config['diploma']))                    $cech_config['diploma'] = [];
if (!isset($cech_config['diploma']['templates']))       $cech_config['diploma']['templates'] = [];
if (!isset($cech_config['diploma']['category_names'])) $cech_config['diploma']['category_names'] = [];

function find_template($templates, $id)
{
    foreach ($templates as $t) {
        if ($t['id'] === $id) return $t;
    }
    return null;
}

// --- POST: save boxes via fetch (?action=save_boxes&template_id=X) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_boxes') {
    $tmpl_id = (int)($_GET['template_id'] ?? 0);
    $boxes   = json_decode(file_get_contents('php://input'), true);
    if (is_array($boxes)) {
        foreach ($cech_config['diploma']['templates'] as &$t) {
            if ($t['id'] === $tmpl_id) { $t['boxes'] = $boxes; break; }
        }
        unset($t);
        cech_config_save($cech_config);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    }
    exit;
}

// --- POST: render preview via fetch (?action=preview) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'preview') {
    $payload = json_decode(file_get_contents('php://input'), true);
    $boxes   = $payload['boxes'] ?? [];
    $vars    = $payload['vars']  ?? [];
    $bg_file = $payload['background'] ?? '';

    // Substitution for category name
    if (isset($vars['category'])) {
        $cat_names = $cech_config['diploma']['category_names'] ?? [];
        if (isset($cat_names[$vars['category']])) {
            $vars['category'] = $cat_names[$vars['category']];
        } else {
            $vars['category'] = html_entity_decode($vars['category']);
        }
    }

    $bg_url  = $bg_file ? $base_url . 'user_images/' . rawurlencode($bg_file) : '';

    header('Content-Type: text/html; charset=utf-8');
    echo render_diploma_html($bg_url, $boxes, $vars);
    exit;
}

// --- POST handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_template') {
        $tpls   = &$cech_config['diploma']['templates'];
        $new_id = empty($tpls) ? 1 : max(array_column($tpls, 'id')) + 1;
        $tpls[] = ['id' => $new_id, 'name' => 'Diplom ' . $new_id,
                   'background' => '', 'place' => '', 'color' => '', 'boxes' => []];
        unset($tpls);
        cech_config_save($cech_config);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?template=' . $new_id);
        exit;
    }

    if ($action === 'delete_template') {
        $del_id = (int)($_POST['template_id'] ?? 0);
        $filtered = [];
        foreach ($cech_config['diploma']['templates'] as $t) {
            if ($t['id'] !== $del_id) $filtered[] = $t;
        }
        $cech_config['diploma']['templates'] = $filtered;
        cech_config_save($cech_config);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'save_settings') {
        $tmpl_id = (int)($_POST['template_id'] ?? 0);
        foreach ($cech_config['diploma']['templates'] as &$t) {
            if ($t['id'] === $tmpl_id) {
                $t['name']  = trim(isset($_POST['name'])  ? $_POST['name']  : '');
                $t['place'] = isset($_POST['place']) ? $_POST['place'] : '';
                $t['color'] = isset($_POST['color']) ? $_POST['color'] : '';
                break;
            }
        }
        unset($t);
        cech_config_save($cech_config);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?template=' . $tmpl_id);
        exit;
    }

    if ($action === 'upload_bg') {
        $tmpl_id     = (int)($_POST['template_id'] ?? 0);
        $upload_error = null;
        if (empty($_FILES['bg_image']) || $_FILES['bg_image']['error'] !== UPLOAD_ERR_OK) {
            $upload_error = 'Upload failed.';
        } else {
            $allowed_mime = ['image/jpeg', 'image/jpg', 'image/png'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($_FILES['bg_image']['tmp_name']);
            if (!in_array($mime, $allowed_mime)) {
                $upload_error = 'Only JPEG and PNG allowed.';
            } elseif ($_FILES['bg_image']['size'] > 10 * 1024 * 1024) {
                $upload_error = 'File too large (max 10 MB).';
            } else {
                $orig     = basename($_FILES['bg_image']['name']);
                $ext      = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $base     = preg_replace('/[^a-z0-9\-]/', '-', strtolower(pathinfo($orig, PATHINFO_FILENAME)));
                $base     = preg_replace('/-+/', '-', trim($base, '-'));
                $filename = $base . '.' . $ext;
                if (move_uploaded_file($_FILES['bg_image']['tmp_name'], USER_IMAGES . $filename)) {
                    foreach ($cech_config['diploma']['templates'] as &$t) {
                        if ($t['id'] === $tmpl_id) { $t['background'] = $filename; break; }
                    }
                    unset($t);
                    cech_config_save($cech_config);
                } else {
                    $upload_error = 'Could not save file.';
                }
            }
        }
        $qs = '?template=' . $tmpl_id . ($upload_error ? '&err=' . urlencode($upload_error) : '');
        header('Location: ' . $_SERVER['PHP_SELF'] . $qs);
        exit;
    }

    if ($action === 'copy_boxes') {
        $tmpl_id = (int)($_POST['template_id'] ?? 0);
        $src_id  = (int)($_POST['source_id']   ?? 0);
        $src     = find_template($cech_config['diploma']['templates'], $src_id);
        if ($src) {
            foreach ($cech_config['diploma']['templates'] as &$t) {
                if ($t['id'] === $tmpl_id) { $t['boxes'] = $src['boxes']; break; }
            }
            unset($t);
            cech_config_save($cech_config);
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?template=' . $tmpl_id);
        exit;
    }

    if ($action === 'save_categories') {
        $raw_styles = isset($_POST['styles']) && is_array($_POST['styles']) ? $_POST['styles'] : [];
        $raw_names  = isset($_POST['names'])  && is_array($_POST['names'])  ? $_POST['names']  : [];
        $cat_names  = [];
        foreach ($raw_styles as $i => $style) {
            $custom = trim(isset($raw_names[$i]) ? $raw_names[$i] : '');
            if ($custom !== '') $cat_names[$style] = $custom;
        }
        $cech_config['diploma']['category_names'] = $cat_names;
        cech_config_save($cech_config);
        $back = isset($_GET['template']) ? '?template=' . (int)$_GET['template'] : '';
        header('Location: ' . $_SERVER['PHP_SELF'] . $back);
        exit;
    }
}

// --- Page data ---
$diploma_config  = $cech_config['diploma'];
$templates       = $diploma_config['templates'];
$category_names  = $diploma_config['category_names'];

$current_id       = isset($_GET['template']) ? (int)$_GET['template'] : null;
$current_template = $current_id ? find_template($templates, $current_id) : null;

$bg_url     = '';
$boxes_json = '[]';
if ($current_template) {
    $bg_file    = isset($current_template['background']) ? $current_template['background'] : '';
    $bg_url     = $bg_file ? $base_url . 'user_images/' . rawurlencode($bg_file) : '';
    $boxes_json = json_encode(isset($current_template['boxes']) ? $current_template['boxes'] : [], JSON_UNESCAPED_UNICODE);
}

$stylesTable = $prefix . "styles";
$current_styles_active = json_decode($_SESSION['prefsSelectedStyles'], true);
$selected_ids = !empty($current_styles_active) ? array_keys($current_styles_active) : [];

$all_styles = [];
if (!empty($selected_ids)) {
    $db_s = new MysqliDb($connection);
    $db_s->where('id', $selected_ids, 'IN');
    $db_s->orderBy('brewStyle', 'asc');
    $all_styles = array_column($db_s->get($stylesTable, null, 'DISTINCT brewStyle'), 'brewStyle');
}

$place_options = [
    ''     => 'No condition',
    '1'    => '1st place',
    '2'    => '2nd place',
    '3'    => '3rd place',
    'any'  => 'Any placed',
    'none' => 'Not placed',
];

$thresholds = $cech_config['score_thresholds'];
$color_options = [
    ''       => 'No condition',
    'gold'   => 'Gold (≥' . $thresholds['gold'] . ')',
    'silver' => 'Silver (' . $thresholds['silver'] . '–' . ($thresholds['gold'] - 1) . ')',
    'bronze' => 'Bronze (' . $thresholds['bronze'] . '–' . ($thresholds['silver'] - 1) . ')',
];

function condition_label($place_options, $color_options, $t)
{
    $place = isset($t['place']) ? $t['place'] : '';
    $color = isset($t['color']) ? $t['color'] : '';
    $p = isset($place_options[$place]) ? $place_options[$place] : '?';
    $c = isset($color_options[$color]) ? $color_options[$color] : '?';
    return $p . ' / ' . $c;
}

$other_templates = [];
foreach ($templates as $t) {
    if ($t['id'] !== $current_id) $other_templates[] = $t;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Diploma Editor</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        <?php echo render_diploma_css(); ?>
        body { padding: 20px; }
        #diploma-wrapper {
            position: relative;
            width: 100%;
            background: #ddd;
            border: 1px solid #ccc;
            overflow: hidden;
            display: flex;
            justify-content: center;
        }
        #diploma-inner {
            transform-origin: top center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        #diploma-inner .diploma-box {
            cursor: move;
            border: 1px dashed #555;
            padding: 2px 4px;
            background: rgba(255,255,255,0.55);
            user-select: none;
        }
        #diploma-inner .diploma-box.selected { border: 2px solid #0074d9; background: rgba(200,230,255,0.7); }
        #props-panel { padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
        #props-panel label { font-weight: bold; margin-top: 8px; display: block; }
        #props-panel .form-control { margin-bottom: 6px; }
        dialog { border: 1px solid #ccc; border-radius: 4px; padding: 20px; min-width: 480px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        dialog::backdrop { background: rgba(0,0,0,0.45); }
        dialog h3 { margin-top: 0; }
        .cat-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .cat-row .cat-orig { flex: 0 0 45%; font-size: 0.85em; color: #555; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cat-row input { flex: 1; }
        #preview-container .diploma { margin: 0 auto; }
    </style>
</head>
<body>
<div class="container-fluid">

    <h2>Diploma Editor</h2>
    <a href="cech_admin.php" class="btn btn-default btn-xs" style="margin-bottom:10px">Back</a>
    <button id="btn-categories" class="btn btn-default btn-xs" style="margin-bottom:10px">Category Names</button>

    <!-- Template list -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Diploma Templates</strong></div>
        <div class="panel-body">
            <?php if (empty($templates)): ?>
                <p class="text-muted">No templates yet.</p>
            <?php else: ?>
                <table class="table table-condensed table-bordered" style="margin-bottom:10px">
                    <thead>
                        <tr><th>Name</th><th>Conditions</th><th style="width:1px;white-space:nowrap">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $t): ?>
                            <tr class="<?php echo ($current_id === $t['id']) ? 'info' : ''; ?>">
                                <td><?php echo htmlspecialchars($t['name']); ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars(condition_label($place_options, $color_options, $t)); ?></small></td>
                                <td>
                                    <a href="?template=<?php echo $t['id']; ?>" class="btn btn-xs btn-primary">Edit</a>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this diploma template?')">
                                        <input type="hidden" name="action" value="delete_template">
                                        <input type="hidden" name="template_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="add_template">
                <button type="submit" class="btn btn-success btn-sm">+ Add diploma</button>
            </form>
        </div>
    </div>

    <?php if ($current_template): ?>

        <?php if (!empty($_GET['err'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['err']); ?></div>
        <?php endif; ?>

        <div class="panel panel-primary">
            <div class="panel-heading"><strong>Editing: <?php echo htmlspecialchars($current_template['name']); ?></strong></div>
            <div class="panel-body">

                <!-- Name + Conditions -->
                <form method="post" class="form-inline" style="margin-bottom:12px">
                    <input type="hidden" name="action" value="save_settings">
                    <input type="hidden" name="template_id" value="<?php echo $current_id; ?>">
                    <div class="form-group" style="margin-right:8px">
                        <label style="margin-right:4px">Name</label>
                        <input type="text" name="name" class="form-control input-sm"
                               value="<?php echo htmlspecialchars($current_template['name']); ?>">
                    </div>
                    <div class="form-group" style="margin-right:8px">
                        <label style="margin-right:4px">Place</label>
                        <select name="place" class="form-control input-sm">
                            <?php foreach ($place_options as $val => $lbl): ?>
                                <option value="<?php echo $val; ?>"
                                    <?php if ((isset($current_template['place']) ? $current_template['place'] : '') === $val) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($lbl); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-right:8px">
                        <label style="margin-right:4px">Color</label>
                        <select name="color" class="form-control input-sm">
                            <?php foreach ($color_options as $val => $lbl): ?>
                                <option value="<?php echo $val; ?>"
                                    <?php if ((isset($current_template['color']) ? $current_template['color'] : '') === $val) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($lbl); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save settings</button>
                </form>

                <!-- Background upload -->
                <form method="post" enctype="multipart/form-data" class="form-inline" style="margin-bottom:15px">
                    <input type="hidden" name="action" value="upload_bg">
                    <input type="hidden" name="template_id" value="<?php echo $current_id; ?>">
                    <?php if (!empty($current_template['background'])): ?>
                        <span class="text-muted" style="margin-right:8px;font-size:0.85em">
                            <?php echo htmlspecialchars($current_template['background']); ?>
                        </span>
                    <?php endif; ?>
                    <input type="file" name="bg_image" accept="image/jpeg,image/png" required>
                    <button type="submit" class="btn btn-default btn-sm">Upload background</button>
                </form>

                <!-- Canvas + Properties -->
                <div class="row">
                    <div class="col-sm-8">
                        <div id="diploma-wrapper">
                            <div id="diploma-inner" class="diploma"
                                 style="<?php echo $bg_url ? 'background-image:url(' . htmlspecialchars($bg_url) . ')' : ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div id="props-panel">
                            <button id="btn-add" class="btn btn-success btn-block" style="margin-bottom:12px">+ Add text box</button>
                            <div id="props-fields" style="display:none">
                                <label>Template</label>
                                <input id="prop-template" type="text" class="form-control"
                                       placeholder="${brewer}, ${brewer1}, ${brewer2}, ${name}, ${place}, ${score}, ${category}">
                                <label>Font size (pt)</label>
                                <input id="prop-fontsize" type="number" class="form-control" value="18" min="6" max="120">
                                <label>Color</label>
                                <input id="prop-color" type="color" class="form-control" value="#000000">
                                <label>Align</label>
                                <select id="prop-align" class="form-control">
                                    <option value="left">Left</option>
                                    <option value="center" selected>Center</option>
                                    <option value="right">Right</option>
                                </select>
                                <label>V-Align</label>
                                <select id="prop-valign" class="form-control">
                                    <option value="top">Top</option>
                                    <option value="middle" selected>Middle</option>
                                    <option value="bottom">Bottom</option>
                                </select>
                                <label>Width (%)</label>
                                <input id="prop-width" type="number" class="form-control" value="60" min="5" max="100">
                                <label>Height (%)</label>
                                <input id="prop-height" type="number" class="form-control" value="5" min="1" max="100">
                                <label>Position X (%)</label>
                                <input id="prop-x" type="number" class="form-control" value="0" min="0" max="100" step="0.01">
                                <label>Position Y (%)</label>
                                <input id="prop-y" type="number" class="form-control" value="0" min="0" max="100" step="0.01">
                                <button id="btn-delete" class="btn btn-danger btn-block" style="margin-top:10px">Delete box</button>
                            </div>
                            <hr>
                            <?php if (!empty($other_templates)): ?>
                                <form method="post" class="form-inline" style="margin-bottom:8px"
                                      onsubmit="return confirm('Replace current boxes with boxes from selected template?')">
                                    <input type="hidden" name="action" value="copy_boxes">
                                    <input type="hidden" name="template_id" value="<?php echo $current_id; ?>">
                                    <select name="source_id" class="form-control input-sm" style="width:auto;margin-right:4px">
                                        <?php foreach ($other_templates as $ot): ?>
                                            <option value="<?php echo $ot['id']; ?>">
                                                <?php echo htmlspecialchars($ot['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-default btn-sm">Copy boxes</button>
                                </form>
                                <hr>
                            <?php endif; ?>
                            <button id="btn-save" class="btn btn-primary btn-block">Save boxes</button>
                            <button id="btn-preview" class="btn btn-info btn-block" style="margin-top:8px">Preview with variables</button>
                            <p id="save-status" class="text-muted" style="margin-top:6px;font-size:0.85em"></p>
                            <hr>
                            <p class="text-muted" style="font-size:0.85em">
                                Variables:<br>
                                <code>${brewer}</code> <code>${brewer1}</code> <code>${brewer2}</code> <code>${name}</code><br>
                                <code>${place}</code> <code>${score}</code> <code>${category}</code>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    <?php endif; ?>

</div>

<!-- Category dialog -->
<dialog id="cat-dialog">
    <h3>Category Names</h3>
    <form method="post">
        <input type="hidden" name="action" value="save_categories">
        <p class="text-muted" style="font-size:0.85em;margin-bottom:10px">
            Leave blank to use the original name. Used in the <code>${category}</code> placeholder.
        </p>
        <div style="max-height:420px;overflow-y:auto;padding-right:4px">
            <?php foreach ($all_styles as $style): ?>
                <div class="cat-row">
                    <span class="cat-orig" title="<?php echo htmlspecialchars($style); ?>">
                        <?php echo htmlspecialchars(html_entity_decode($style)); ?>
                    </span>
                    <input type="hidden" name="styles[]" value="<?php echo htmlspecialchars($style); ?>">
                    <input type="text" name="names[]" class="form-control input-sm"
                           placeholder="Custom name"
                           value="<?php echo htmlspecialchars(isset($category_names[$style]) ? $category_names[$style] : ''); ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" id="cat-close" class="btn btn-default">Cancel</button>
        </div>
    </form>
</dialog>

<!-- Preview dialog -->
<dialog id="preview-dialog" style="min-width: 800px; max-width: 95vw;">
    <h3 style="display:flex;justify-content:space-between;align-items:center">
        Preview
        <button type="button" id="preview-close" class="btn btn-default btn-xs">×</button>
    </h3>
    <div class="row">
        <div class="col-sm-4">
            <h4>Variables</h4>
            <div class="form-group">
                <label>Brewer (full)</label>
                <input id="pre-brewer" type="text" class="form-control input-sm" value="Jan Sládek, Petr Novák">
            </div>
            <div class="form-group">
                <label>Brewer 1 (primary)</label>
                <input id="pre-brewer1" type="text" class="form-control input-sm" value="Jan Sládek">
            </div>
            <div class="form-group">
                <label>Brewer 2 (co-brewer)</label>
                <input id="pre-brewer2" type="text" class="form-control input-sm" value="Petr Novák">
            </div>
            <div class="form-group">
                <label>Name of beer</label>
                <input id="pre-name" type="text" class="form-control input-sm" value="Vánoční speciál">
            </div>
            <div class="form-group">
                <label>Place</label>
                <input id="pre-place" type="text" class="form-control input-sm" value="1">
            </div>
            <div class="form-group">
                <label>Score</label>
                <input id="pre-score" type="text" class="form-control input-sm" value="92">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="pre-category" class="form-control input-sm">
                    <?php foreach ($all_styles as $style): ?>
                        <option value="<?php echo htmlspecialchars($style); ?>">
                            <?php echo htmlspecialchars(html_entity_decode($style)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="btn-pre-gen" class="btn btn-primary btn-block">Update Preview</button>
        </div>
        <div class="col-sm-8">
            <div id="preview-container" style="background:#eee; padding:10px; height: 600px; overflow: auto; border: 1px solid #ccc">
                <div id="preview-content"></div>
            </div>
        </div>
    </div>
</dialog>

<script>
(function () {
    document.getElementById('btn-categories').addEventListener('click', function () {
        document.getElementById('cat-dialog').showModal();
    });
    document.getElementById('cat-close').addEventListener('click', function () {
        document.getElementById('cat-dialog').close();
    });

    <?php if ($current_template): ?>
    var TEMPLATE_ID = <?php echo $current_id; ?>;
    var inner       = document.getElementById('diploma-inner');
    var propsPanel  = document.getElementById('props-fields');
    var btnAdd      = document.getElementById('btn-add');
    var btnDelete   = document.getElementById('btn-delete');
    var btnSave     = document.getElementById('btn-save');
    var btnPreview  = document.getElementById('btn-preview');
    var saveStatus  = document.getElementById('save-status');

    var propTemplate = document.getElementById('prop-template');
    var propFontsize = document.getElementById('prop-fontsize');
    var propColor    = document.getElementById('prop-color');
    var propAlign    = document.getElementById('prop-align');
    var propValign   = document.getElementById('prop-valign');
    var propWidth    = document.getElementById('prop-width');
    var propHeight   = document.getElementById('prop-height');
    var propX        = document.getElementById('prop-x');
    var propY        = document.getElementById('prop-y');

    var boxes      = <?php echo $boxes_json; ?>;
    var selectedId = null;
    var dragging   = null;

    function pct(px, total) { return (px / total) * 100; }

    function resizeCanvas() {
        var wrapper = document.getElementById('diploma-wrapper');
        var scale = wrapper.offsetWidth / inner.offsetWidth;
        inner.style.transform = 'scale(' + scale + ')';
        wrapper.style.height = (inner.offsetHeight * scale) + 'px';
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    function renderBoxes() {
        inner.querySelectorAll('.diploma-box').forEach(function (el) { el.remove(); });
        boxes.forEach(function (box) { createBoxEl(box); });
    }

    function createBoxEl(box) {
        var el = document.createElement('div');
        el.className   = 'diploma-box' + (box.id === selectedId ? ' selected' : '');
        el.dataset.id  = box.id;
        el.style.left      = box.x + '%';
        el.style.top       = box.y + '%';
        el.style.width     = box.width + '%';
        el.style.height    = (box.height || 5) + '%';
        el.style.fontSize  = box.fontSize + 'pt';
        el.style.color     = box.color;
        el.style.textAlign = box.align;

        // Vertical align via flexbox
        var justify = 'center';
        if (box.valign === 'top') justify = 'flex-start';
        if (box.valign === 'bottom') justify = 'flex-end';
        el.style.justifyContent = justify;

        // Horizontal align via flexbox (in addition to text-align)
        var items = 'center';
        if (box.align === 'left') items = 'flex-start';
        if (box.align === 'right') items = 'flex-end';
        el.style.alignItems = items;

        el.textContent     = box.template || '(empty)';
        el.addEventListener('mousedown', function (e) {
            e.stopPropagation();
            selectBox(box.id);
            var rect = inner.getBoundingClientRect();
            dragging = { id: box.id, startX: e.clientX, startY: e.clientY,
                         origLeft: box.x, origTop: box.y, rectW: rect.width, rectH: rect.height };
        });
        inner.appendChild(el);
    }

    function selectBox(id) {
        selectedId = id;
        inner.querySelectorAll('.diploma-box').forEach(function (el) {
            el.classList.toggle('selected', parseInt(el.dataset.id) === id);
        });
        var box = boxes.find(function (b) { return b.id === id; });
        if (box) {
            propsPanel.style.display = 'block';
            propTemplate.value = box.template;
            propFontsize.value = box.fontSize;
            propColor.value    = box.color;
            propAlign.value    = box.align;
            propValign.value   = box.valign || 'middle';
            propWidth.value    = box.width;
            propHeight.value   = box.height || 5;
            propX.value        = box.x.toFixed(2);
            propY.value        = box.y.toFixed(2);
        }
    }

    function updateSelected() {
        if (selectedId === null) return;
        var box = boxes.find(function (b) { return b.id === selectedId; });
        if (!box) return;
        box.template = propTemplate.value;
        box.fontSize = parseInt(propFontsize.value) || 18;
        box.color    = propColor.value;
        box.align    = propAlign.value;
        box.valign   = propValign.value;
        box.width    = parseFloat(propWidth.value) || 60;
        box.height   = parseFloat(propHeight.value) || 5;
        box.x        = parseFloat(propX.value) || 0;
        box.y        = parseFloat(propY.value) || 0;
        renderBoxes();
    }

    [propTemplate, propFontsize, propColor, propAlign, propValign, propWidth, propHeight, propX, propY].forEach(function (el) {
        el.addEventListener('input', updateSelected);
    });

    document.addEventListener('mousemove', function (e) {
        if (!dragging) return;
        var dx  = e.clientX - dragging.startX;
        var dy  = e.clientY - dragging.startY;
        var box = boxes.find(function (b) { return b.id === dragging.id; });
        if (!box) return;
        box.x = Math.max(0, Math.min(100, dragging.origLeft + pct(dx, dragging.rectW)));
        box.y = Math.max(0, Math.min(100, dragging.origTop  + pct(dy, dragging.rectH)));
        propX.value = box.x.toFixed(2);
        propY.value = box.y.toFixed(2);
        renderBoxes();
    });

    document.addEventListener('mouseup', function () { dragging = null; });

    btnAdd.addEventListener('click', function () {
        var newBox = { id: Date.now(), template: '${brewer}', x: 10, y: 10,
                       width: 60, height: 5, fontSize: 18, color: '#000000', align: 'center', valign: 'middle' };
        boxes.push(newBox);
        renderBoxes();
        selectBox(newBox.id);
    });

    btnDelete.addEventListener('click', function () {
        if (selectedId === null) return;
        boxes = boxes.filter(function (b) { return b.id !== selectedId; });
        selectedId = null;
        propsPanel.style.display = 'none';
        renderBoxes();
    });

    btnSave.addEventListener('click', function () {
        saveStatus.textContent = 'Saving…';
        fetch('?action=save_boxes&template_id=' + TEMPLATE_ID, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(boxes)
        })
        .then(function (r) { return r.json(); })
        .then(function (d) { saveStatus.textContent = d.ok ? 'Saved.' : 'Error: ' + d.error; })
        .catch(function () { saveStatus.textContent = 'Network error.'; });
    });

    // --- Preview logic ---
    var previewDialog  = document.getElementById('preview-dialog');
    var previewClose   = document.getElementById('preview-close');
    var btnPreGen      = document.getElementById('btn-pre-gen');
    var previewContent = document.getElementById('preview-content');

    btnPreview.addEventListener('click', function() {
        previewDialog.showModal();
        generatePreview();
    });

    previewClose.addEventListener('click', function() {
        previewDialog.close();
    });

    btnPreGen.addEventListener('click', generatePreview);

    function generatePreview() {
        previewContent.innerHTML = '<p class="text-muted">Generating...</p>';
        var vars = {
            brewer:   document.getElementById('pre-brewer').value,
            brewer1:  document.getElementById('pre-brewer1').value,
            brewer2:  document.getElementById('pre-brewer2').value,
            name:     document.getElementById('pre-name').value,
            place:    document.getElementById('pre-place').value,
            score:    document.getElementById('pre-score').value,
            category: document.getElementById('pre-category').value
        };
        
        fetch('?action=preview', {
            method: 'POST',
            body: JSON.stringify({
                boxes: boxes,
                vars: vars,
                background: <?php echo json_encode($current_template['background'] ?? ''); ?>
            })
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            previewContent.innerHTML = html;
        })
        .catch(function(err) {
            previewContent.innerHTML = '<p class="text-danger">Error: ' + err + '</p>';
        });
    }

    renderBoxes();
    <?php endif; ?>
})();
</script>
</body>
</html>
