<?php
require_once 'common.php';

/**
 * Theme Editor page
 * - Edits CSS variable block inside static/styles/custom1.css
 * - Allows saving raw custom CSS
 * - Allows uploading CSS (append/replace)
 * All UI text comes from language keys defined in app/lang/*.ini
 */

// Require login
if (!User::is_logged_in()) {
    http_response_code(403);
    echo "<h1>Forbidden</h1>";
    exit;
}

// CSRF token
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = function_exists('random_bytes')
        ? bin2hex(random_bytes(5))
        : bin2hex(openssl_random_pseudo_bytes(5));
}
$csrf = $_SESSION['token'];

// Paths
$customCssPath = PROJECT_PATH . 'static/styles/custom1.css';
$stylesDir     = PROJECT_PATH . 'static/styles';

// Helpers
function escape($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function read_file_safely($p){ return file_exists($p) ? (string)file_get_contents($p) : ''; }
function write_file_safely($p,$c){ $d=dirname($p); if(!is_dir($d)) return false; return file_put_contents($p,(string)$c)!==false; }

// Theme list (theme*.css)
function get_theme_list($dir){
    $out=[];
    if(is_dir($dir)){
        foreach(scandir($dir) as $f){
            if(is_string($f) && preg_match('/^theme[0-9a-z_\-]*\.css$/i',$f)) $out[]=$f;
        }
    }
    sort($out, SORT_NATURAL | SORT_FLAG_CASE);
    return $out;
}

// Variables block
const TE_BLOCK_START = '/* THEME EDITOR VARIABLES START */';
const TE_BLOCK_END   = '/* THEME EDITOR VARIABLES END */';

function default_vars(){
    return [
        '--color-primary' => '#1877f2',
        '--surface'       => '#ffffff',
        '--surface-2'     => '#f7fafc',
        '--surface-3'     => '#f1f5f9',
        '--border-color'  => '#e5e7eb',
        '--color-text'    => '#111827',
        '--muted-text'    => '#6b7280',
        '--input-bg'      => '#ffffff',
        '--input-text'    => '#111827',
    ];
}
function extract_te_block($css){
    $pat='/\/\*\s*THEME EDITOR VARIABLES START\s*\*\/.*?\/\*\s*THEME EDITOR VARIABLES END\s*\*\//s';
    return preg_match($pat,$css,$m)?$m[0]:'';
}
function remove_te_block($css){
    $pat='/\/\*\s*THEME EDITOR VARIABLES START\s*\*\/.*?\/\*\s*THEME EDITOR VARIABLES END\s*\*\//s';
    return (string)preg_replace($pat,'',$css);
}
function parse_vars_from_block($block){
    $vars=default_vars();
    if(!$block) return $vars;
    if(preg_match('/:root\s*\{([^}]*)\}/s',$block,$m)){
        $body=$m[1];
        if(preg_match_all('/(--[a-zA-Z0-9\-_]+)\s*:\s*([^;]+);/',$body,$mm,PREG_SET_ORDER)){
            foreach($mm as $row){
                $n=trim($row[1]); $v=trim($row[2]);
                if($n!=='' && $v!=='') $vars[$n]=$v;
            }
        }
    }
    return $vars;
}
function build_te_block($vars){
    $lines=[];
    foreach($vars as $k=>$v){ $lines[]="  {$k}: {$v};"; }
    $root=implode(PHP_EOL,$lines);
    return TE_BLOCK_START . PHP_EOL . ":root {" . PHP_EOL . $root . PHP_EOL . "}" . PHP_EOL . TE_BLOCK_END;
}

// Status
$msg=''; $err='';

// POST
if($_SERVER['REQUEST_METHOD']==='POST'){
    $token=$_POST['csrf'] ?? '';
    if(!hash_equals($csrf,$token)){
        $err=$lang['CSRF token invalid'] ?? 'CSRF token invalid';
    } else {
        $action=$_POST['action'] ?? '';
        if($action==='save_variables'){
            $vars=default_vars();
            foreach($vars as $k=>$v){
                $in=trim((string)($_POST['vars'][$k] ?? $v));
                if(preg_match('/[{}]|javascript:/i',$in)) $in=$v;
                $vars[$k]=$in;
            }
            $current=read_file_safely($customCssPath);
            $block=build_te_block($vars);
            $next=$block . PHP_EOL . ltrim(remove_te_block($current));
            $msg = write_file_safely($customCssPath,$next)
                ? ($lang['Variables saved'] ?? 'Variables saved')
                : ($lang['Write failed'] ?? 'Write failed');
        } elseif($action==='save_css'){
            $content=(string)($_POST['css_content'] ?? '');
            if(strlen($content)>0 && preg_match('/[{}]/',$content)){
                $msg = write_file_safely($customCssPath,$content)
                    ? ($lang['CSS saved'] ?? 'CSS saved')
                    : ($lang['Write failed'] ?? 'Write failed');
            } else { $err=$lang['Invalid CSS content'] ?? 'Invalid CSS content'; }
        } elseif($action==='upload_css'){
            $mode=($_POST['upload_mode'] ?? 'append');
            if(!empty($_FILES['css_file']['tmp_name'])){
                $size=(int)$_FILES['css_file']['size'];
                if($size>524288){ $err=$lang['Only CSS files allowed'] ?? 'Only CSS files allowed'; }
                else {
                    $filename = (string)($_FILES['css_file']['name'] ?? '');
                    if(!preg_match('/\.css$/i',$filename)){ $err=$lang['Only CSS files allowed'] ?? 'Only CSS files allowed'; }
                    else {
                        $data=(string)file_get_contents($_FILES['css_file']['tmp_name']);
                        if(!preg_match('/[{}]/',$data) || preg_match('/<\?php|<script|javascript:/i',$data)){
                            $err=$lang['File is not valid CSS or contains dangerous code'] ?? 'File is not valid CSS or contains dangerous code';
                        } else {
                            $current=read_file_safely($customCssPath);
                            $stamp=date('c');
                            $name=basename($filename);
                            $header="/* " . ($mode==='replace'?'Replaced':'Appended') . " by Theme-Editor Upload: {$stamp} ({$name}) */" . PHP_EOL;
                            $next = $mode==='replace' ? ($header.$data) : (rtrim($current).PHP_EOL.$header.$data);
                            $msg = write_file_safely($customCssPath,$next)
                                ? ($lang['Upload saved'] ?? 'Upload saved')
                                : ($lang['Upload write failed'] ?? 'Upload write failed');
                        }
                    }
                }
            } else { $err=$lang['No file selected'] ?? 'No file selected'; }
        }
    }
}

// Theme for preview
$theme = Config::get_safe('theme','theme01');
$theme = preg_replace('/\.css$/i','',trim((string)$theme));
$theme = preg_replace('/[^a-zA-Z0-9_-]/','',$theme);
if($theme===''){ $theme='theme01'; }

$themes = get_theme_list($stylesDir);
$currentCss = read_file_safely($customCssPath);
$vars = parse_vars_from_block(extract_te_block($currentCss));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🎨 <?php echo escape($lang['Theme-Editor'] ?? 'Theme-Editor'); ?> - <?php echo escape(Config::get('title')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="<?php echo escape($csrf); ?>">

    <link href="../static/styles/main.css" rel="stylesheet" type="text/css">
    <link href="../static/styles/<?php echo escape($theme); ?>.css" rel="stylesheet" type="text/css">
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css">
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css">

    <style>
    .editor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .panel { background: #fff; border: 1px solid #e1e4e8; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .panel-header { background: #f6f8fa; padding: 10px 14px; font-weight: 600; border-bottom: 1px solid #e1e4e8; }
    .panel-body { padding: 14px; }
    .form-row { margin-bottom: 12px; }
    .form-row label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #24292e; }
    .color-input { width: 100%; padding: 6px 8px; border: 1px solid #d0d7de; border-radius: 6px; font-size: 14px; }
    .btn { display: inline-block; padding: 6px 16px; background: #0969da; color: #fff; border: 0; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; transition: background .15s; }
    .btn:hover { background: #0860ca; }
    .btn-primary { background: #1a7f37; }
    .btn-primary:hover { filter: brightness(1.1); }
    iframe.preview{width:100%;height:720px;border:1px solid #e1e4e8;border-radius:6px;background:#fff;}
    @media (max-width: 1000px) { .editor-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="admin-header">
        <div class="admin-container">
            <h1>🎨 <?php echo escape($lang['Theme-Editor'] ?? 'Theme-Editor'); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get('name')); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape($lang['Back to Blog'] ?? 'Back to Blog'); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape($lang['Dashboard'] ?? 'Dashboard'); ?></a>
                <a href="posts.php">📝 <?php echo escape($lang['Posts'] ?? 'Posts'); ?></a>
                <a href="comments.php">💬 <?php echo escape($lang['Comments'] ?? 'Comments'); ?></a>
                <a href="media.php">📁 <?php echo escape($lang['Files'] ?? 'Files'); ?></a>
                <a href="backups.php">💾 <?php echo escape($lang['Backups'] ?? 'Backups'); ?></a>
                <a href="trash.php">🗑️ <?php echo escape($lang['Trash'] ?? 'Trash'); ?></a>
                <a href="categories.php">🏷️ <?php echo escape($lang['Categories'] ?? 'Categories'); ?></a>
                <a href="theme_editor.php" class="active">🎨 <?php echo escape($lang['Theme-Editor'] ?? 'Theme-Editor'); ?></a>
                <a href="settings.php">⚙️ <?php echo escape($lang['Settings'] ?? 'Settings'); ?></a>
            </nav>
        </aside>

        <main class="admin-content">
            <?php if($msg): ?><div class="message message-success">✅ <?php echo escape($msg); ?></div><?php endif; ?>
            <?php if($err): ?><div class="message message-error">❌ <?php echo escape($err); ?></div><?php endif; ?>

            <div class="editor-grid">
                <div>
                    <div class="panel">
                        <div class="panel-header"><?php echo escape($lang['Colors CSS Variables'] ?? 'Colors (CSS Variables)'); ?></div>
                        <div class="panel-body">
                            <form method="POST">
                                <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
                                <input type="hidden" name="action" value="save_variables">
                                <?php foreach($vars as $name=>$value): ?>
                                    <div class="form-row">
                                        <label><?php echo escape($name); ?></label>
                                        <input class="color-input" type="text" name="vars[<?php echo escape($name); ?>]" value="<?php echo escape($value); ?>">
                                    </div>
                                <?php endforeach; ?>
                                <button class="btn btn-primary">💾 <?php echo escape($lang['Save'] ?? 'Save'); ?></button>
                            </form>
                        </div>
                    </div>

                    <div class="panel" style="margin-top:12px;">
                        <div class="panel-header"><?php echo escape($lang['Custom CSS'] ?? 'Custom CSS'); ?></div>
                        <div class="panel-body">
                            <form method="POST">
                                <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
                                <input type="hidden" name="action" value="save_css">
                                <textarea name="css_content" rows="18" style="width:100%;border:1px solid #d0d7de;border-radius:6px;font-family:monospace;padding:8px;"><?php echo escape($currentCss); ?></textarea>
                                <button class="btn btn-primary" style="margin-top:8px;">💾 <?php echo escape($lang['Save CSS'] ?? 'Save CSS'); ?></button>
                            </form>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="panel">
                        <div class="panel-header"><?php echo escape($lang['Upload CSS'] ?? 'Upload CSS'); ?></div>
                        <div class="panel-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
                                <input type="hidden" name="action" value="upload_css">
                                <div class="form-row">
                                    <label><?php echo escape($lang['File .css'] ?? 'File (.css)'); ?></label>
                                    <input type="file" name="css_file" accept=".css" style="width:100%;padding:6px;border:1px solid #d0d7de;border-radius:6px;">
                                </div>
                                <div class="form-row">
                                    <label><?php echo escape($lang['Mode'] ?? 'Mode'); ?></label>
                                    <select name="upload_mode" style="width:100%;padding:6px 8px;border:1px solid #d0d7de;border-radius:6px;">
                                        <option value="append"><?php echo escape($lang['Append'] ?? 'Append'); ?></option>
                                        <option value="replace"><?php echo escape($lang['Replace'] ?? 'Replace'); ?></option>
                                    </select>
                                </div>
                                <button class="btn btn-primary">📤 <?php echo escape($lang['Upload CSS'] ?? 'Upload CSS'); ?></button>
                            </form>
                        </div>
                    </div>

                    <div class="panel" style="margin-top:12px;">
                        <div class="panel-header"><?php echo escape($lang['Preview Themes'] ?? 'Preview Themes'); ?></div>
                        <div class="panel-body">
                            <p style="font-size:13px;color:#57606a;margin-bottom:10px;"><?php echo escape($lang['Available Themes:'] ?? 'Available Themes:'); ?></p>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;">
                                <?php foreach($themes as $t): ?>
                                    <button type="button" class="btn" onclick="window.ThemeEditorPreview && window.ThemeEditorPreview.loadTheme('<?php echo escape($t); ?>');">
                                        👁️ <?php echo escape($t); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top:8px;">
                                <button type="button" class="btn" onclick="window.ThemeEditorPreview && window.ThemeEditorPreview.reloadPreview();">🔄 <?php echo escape($lang['Reload Preview'] ?? 'Reload Preview'); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="panel" style="margin-top:12px;">
                        <div class="panel-header"><?php echo escape($lang['Preview'] ?? 'Preview'); ?></div>
                        <div class="panel-body">
                            <iframe class="preview" id="previewFrame" src="../index.php"></iframe>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="../static/scripts/theme-editor.js"></script>
</body>
</html>