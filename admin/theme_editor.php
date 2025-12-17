<?php
require_once 'common.php';

// Login-Pflicht
if (!User::is_logged_in()) {
    http_response_code(403);
    echo "<h1>Forbidden</h1>";
    exit;
}

// CSRF-Token
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = function_exists('random_bytes')
        ? bin2hex(random_bytes(5))
        : bin2hex(openssl_random_pseudo_bytes(5));
}
$csrf = $_SESSION['token'];

// Pfade
$customCssPath = PROJECT_PATH . 'static/styles/custom1.css';
$stylesDir     = PROJECT_PATH . 'static/styles';

// Helper
function escape($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function read_file_safely($p){ return file_exists($p) ? (string)file_get_contents($p) : ''; }
function write_file_safely($p,$c){ $d=dirname($p); if(!is_dir($d)) return false; return file_put_contents($p,(string)$c)!==false; }

// Theme-Liste (theme*.css)
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

// Variablenblock
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
        $err='CSRF-Token ungültig.';
    } else {
        $action=$_POST['action'] ?? '';
        if($action==='save_variables'){
            $vars=default_vars();
            // Eingaben kommen als vars[--color-primary] etc.
            foreach($vars as $k=>$v){
                $in=trim((string)($_POST['vars'][$k] ?? $v));
                if(preg_match('/[{};]/',$in)) $in=$v; // einfacher Schutz
                $vars[$k]=$in;
            }
            $current=read_file_safely($customCssPath);
            $block=build_te_block($vars);
            $next=$block . PHP_EOL . ltrim(remove_te_block($current));
            $msg = write_file_safely($customCssPath,$next) ? 'Variablen gespeichert.' : 'Schreiben fehlgeschlagen.';
        } elseif($action==='save_css'){
            $content=(string)($_POST['css_content'] ?? '');
            if(strlen($content)>0 && preg_match('/[{}]/',$content)){
                $msg = write_file_safely($customCssPath,$content) ? 'CSS gespeichert.' : 'Schreiben fehlgeschlagen.';
            } else { $err='Ungültiger CSS-Inhalt.'; }
        } elseif($action==='upload_css'){
            $mode=($_POST['upload_mode'] ?? 'append');
            if(!empty($_FILES['css_file']['tmp_name'])){
                $size=(int)$_FILES['css_file']['size'];
                if($size>524288){ $err='Datei zu groß (max 512KB).'; }
                else {
                    $data=(string)file_get_contents($_FILES['css_file']['tmp_name']);
                    if(!preg_match('/[{}]/',$data)){ $err='Datei sieht nicht wie CSS aus.'; }
                    else {
                        $current=read_file_safely($customCssPath);
                        $stamp=date('c');
                        $name=basename((string)($_FILES['css_file']['name'] ?? 'upload.css'));
                        $header="/* " . ($mode==='replace'?'Replaced':'Appended') . " by Theme-Editor Upload: {$stamp} ({$name}) */" . PHP_EOL;
                        $next = $mode==='replace' ? ($header.$data) : (rtrim($current).PHP_EOL.$header.$data);
                        $msg = write_file_safely($customCssPath,$next) ? 'Upload gespeichert.' : 'Upload-Schreiben fehlgeschlagen.';
                    }
                }
            } else { $err='Keine Datei gewählt.'; }
        }
    }
}

// Theme für Preview
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
    <title>🎨 Theme-Editor - <?php echo escape(Config::get('title')); ?></title>
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
    .btn-primary:hover { background: #1a7f37; filter: brightness(1.1); }
    .message { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
    .message-success { background: #dafbe1; border: 1px solid #2da44e; color: #1a7f37; }
    .message-error { background: #ffebe9; border: 1px solid #cf222e; color: #cf222e; }
    @media (max-width: 1000px) { .editor-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="admin-header">
        <div class="admin-container">
            <h1>🎨 Theme-Editor</h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get('name')); ?></span>
                <a href="../" class="btn btn-sm">← Zurück zum Blog</a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 Dashboard</a>
                <a href="posts.php">📝 Posts</a>
                <a href="comments.php">💬 Comments</a>
                <a href="media.php">📁 Files</a>
                <a href="backups.php">💾 Backups</a>
                <a href="trash.php">🗑️ Trash</a>
                <a href="categories.php">🏷️ Categories</a>
                <a href="theme_editor.php" class="active">🎨 Theme-Editor</a>
                <a href="settings.php">⚙️ Settings</a>
            </nav>
        </aside>

        <main class="admin-content">
            <?php if($msg): ?><div class="message message-success">✅ <?php echo escape($msg); ?></div><?php endif; ?>
            <?php if($err): ?><div class="message message-error">❌ <?php echo escape($err); ?></div><?php endif; ?>

            <div class="editor-grid">
                <div>
                    <div class="panel">
                        <div class="panel-header">Farben (CSS Variablen)</div>
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
                                <button class="btn btn-primary">💾 Speichern</button>
                            </form>
                        </div>
                    </div>

                    <div class="panel" style="margin-top:12px;">
                        <div class="panel-header">Custom CSS</div>
                        <div class="panel-body">
                            <form method="POST">
                                <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
                                <input type="hidden" name="action" value="save_css">
                                <textarea name="css_content" rows="18" style="width:100%;border:1px solid #d0d7de;border-radius:6px;font-family:monospace;padding:8px;"><?php echo escape($currentCss); ?></textarea>
                                <button class="btn btn-primary" style="margin-top:8px;">💾 CSS Speichern</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="panel">
                        <div class="panel-header">Upload CSS</div>
                        <div class="panel-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
                                <input type="hidden" name="action" value="upload_css">
                                <div class="form-row">
                                    <label>Datei (.css)</label>
                                    <input type="file" name="css_file" accept=".css" style="width:100%;padding:6px;border:1px solid #d0d7de;border-radius:6px;">
                                </div>
                                <div class="form-row">
                                    <label>Modus</label>
                                    <select name="upload_mode" style="width:100%;padding:6px 8px;border:1px solid #d0d7de;border-radius:6px;">
                                        <option value="append">Anhängen</option>
                                        <option value="replace">Ersetzen</option>
                                    </select>
                                </div>
                                <button class="btn btn-primary">📤 Upload</button>
                            </form>
                        </div>
                    </div>

                    <div class="panel" style="margin-top:12px;">
                        <div class="panel-header">Preview Themes</div>
                        <div class="panel-body">
                            <p style="font-size:13px;color:#57606a;margin-bottom:10px;">Verfügbare Themes:</p>
                            <ul style="list-style:none;padding:0;margin:0;">
                                <?php foreach($themes as $t): ?>
                                    <li style="padding:4px 0;"><code style="background:#f6f8fa;padding:2px 6px;border-radius:3px;font-size:12px;"><?php echo escape($t); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="../static/scripts/theme-editor.js"></script>
</body>
</html>
