<?php
require_once 'common.php';

// Nur eingeloggte Nutzer
if (!User::is_logged_in()) {
    http_response_code(403);
    echo "<h1>Forbidden</h1>";
    exit;
}

// CSRF-Token
if (empty($_SESSION['token'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    } else {
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
$csrf = $_SESSION['token'];

// Pfade
$customCssPath = PROJECT_PATH . 'static/styles/custom1.css';
$stylesDir     = PROJECT_PATH . 'static/styles';

function escape($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function read_file_safely($path) { return file_exists($path) ? (string)file_get_contents($path) : ''; }
function write_file_safely($path, $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) return false;
    return file_put_contents($path, (string)$content) !== false;
}

// Theme-Liste (theme*.css)
function get_theme_list($stylesDir) {
    $files = [];
    if (is_dir($stylesDir)) {
        foreach (scandir($stylesDir) as $f) {
            if (!is_string($f)) continue;
            if (preg_match('/^theme[0-9a-z_-]*\.css$/i', $f)) $files[] = $f;
        }
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    return $files;
}

// Variablen-Block in custom1.css verwalten
const TE_BLOCK_START = '/* THEME EDITOR VARIABLES START */';
const TE_BLOCK_END   = '/* THEME EDITOR VARIABLES END */';

function extract_te_block($css) {
    $pattern = '/\/\*\s*THEME EDITOR VARIABLES START\s*\*\/.*?\/\*\s*THEME EDITOR VARIABLES END\s*\*\//s';
    if (preg_match($pattern, $css, $m)) return $m[0];
    return '';
}
function remove_te_block($css) {
    $pattern = '/\/\*\s*THEME EDITOR VARIABLES START\s*\*\/.*?\/\*\s*THEME EDITOR VARIABLES END\s*\*\//s';
    return (string)preg_replace($pattern, '', $css);
}
function default_vars() {
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
function parse_vars_from_block($block) {
    $vars = default_vars();
    if (!$block) return $vars;
    if (preg_match('/:root\s*\{([^}]*)\}/s', $block, $m)) {
        $body = $m[1];
        if (preg_match_all('/(--[a-zA-Z0-9\-_]+)\s*:\s*([^;]+);/', $body, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $row) {
                $name  = trim($row[1]);
                $value = trim($row[2]);
                if ($name !== '' && $value !== '') $vars[$name] = $value;
            }
        }
    }
    return $vars;
}
function build_te_block($vars) {
    $lines = [];
    foreach ($vars as $k => $v) $lines[] = "  {$k}: {$v};";
    $root = implode(PHP_EOL, $lines);
    return TE_BLOCK_START . PHP_EOL .
           ":root {" . PHP_EOL . $root . PHP_EOL . "}" . PHP_EOL .
           TE_BLOCK_END;
}

// Status
$msg = '';
$err = '';

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $err = 'CSRF-Token ungültig.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'save_variables') {
            $current = read_file_safely($customCssPath);
            $vars = default_vars();
            foreach ($vars as $k => $_) {
                if (isset($_POST['vars'][$k])) {
                    $val = trim((string)$_POST['vars'][$k]);
                    // Validate CSS value - basic sanity checks
                    if (strpos($val, '}') !== false || strpos($val, '{') !== false || 
                        strpos($val, ';') !== false || strpos($val, '/*') !== false) {
                        $val = ''; // prevent CSS injection
                    }
                    if ($val !== '') $vars[$k] = $val;
                }
            }
            $block = build_te_block($vars);
            $without = trim(remove_te_block($current));
            $newline = $without === '' ? '' : (PHP_EOL . PHP_EOL);
            $newCss = $block . $newline . $without;
            if (write_file_safely($customCssPath, $newCss)) $msg = 'Farben gespeichert (custom1.css aktualisiert).';
            else $err = 'Konnte custom1.css nicht schreiben.';
        } elseif ($action === 'save_custom_css') {
            $content = (string)($_POST['custom_css'] ?? '');
            if ($content === '') $err = 'Kein Inhalt übermittelt.';
            else if (write_file_safely($customCssPath, $content)) $msg = 'custom1.css gespeichert.';
            else $err = 'Konnte custom1.css nicht schreiben.';
        } elseif ($action === 'upload_css') {
            $mode = ($_POST['mode'] ?? 'append') === 'replace' ? 'replace' : 'append';
            if (!isset($_FILES['cssfile']) || $_FILES['cssfile']['error'] !== UPLOAD_ERR_OK) {
                $err = 'Upload fehlgeschlagen.';
            } else {
                $tmp = $_FILES['cssfile']['tmp_name'];
                $name = $_FILES['cssfile']['name'];
                $sz = (int)($_FILES['cssfile']['size'] ?? 0);
                if ($sz > 512 * 1024) {
                    $err = 'Datei zu groß (max. 512KB).';
                } else {
                    $code = (string)file_get_contents($tmp);
                    // Enhanced CSS validation
                    $hasBasicCSS = preg_match('/[{};]/', $code);
                    $hasSelector = preg_match('/[a-z0-9\-_#.:\[\]]+\s*\{/i', $code);
                    if (!$hasBasicCSS || !$hasSelector) {
                        $err = 'Sieht nicht nach CSS aus.';
                    } else {
                        $current = read_file_safely($customCssPath);
                        $stamp = date('c');
                        if ($mode === 'replace') {
                            $newCss = "/* Replaced by Theme-Editor Upload: {$stamp} ({$name}) */\n{$code}\n";
                        } else {
                            $newCss = rtrim($current, "\r\n") . "\n\n/* Appended by Theme-Editor Upload: {$stamp} ({$name}) */\n{$code}\n";
                        }
                        if (write_file_safely($customCssPath, $newCss)) {
                            $msg = $mode === 'replace' ? 'custom1.css ersetzt.' : 'CSS an custom1.css angehängt.';
                        } else {
                            $err = 'Konnte custom1.css nicht schreiben.';
                        }
                    }
                }
            }
        } else {
            $err = 'Unbekannte Aktion.';
        }
    }
}

// Daten
$currentCss   = read_file_safely($customCssPath);
$existingBlock= extract_te_block($currentCss);
$vars         = parse_vars_from_block($existingBlock);
$themes       = get_theme_list($stylesDir);
$currentTheme = Config::get_safe('theme', 'theme01');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🎨 Theme-Editor - <?php echo escape(Config::get('title')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="<?php echo escape($csrf); ?>">
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo escape(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$currentTheme)); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <style>
      .te-container { max-width:1080px; margin:20px auto; background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
      .te-header { padding:16px 18px; background:#f8fafc; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; }
      .te-tabs { display:flex; gap:8px; padding:10px; border-bottom:1px solid #e5e7eb; background:#fff; flex-wrap:wrap; }
      .te-tab { padding:8px 12px; border:1px solid #e5e7eb; background:#fff; border-radius:8px; cursor:pointer; font-weight:600; }
      .te-tab.active { background:#1877f2; color:#fff; border-color:#1877f2; }
      .te-body { padding:16px 18px; }
      .te-card { border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
      .te-card .hd { padding:10px 12px; border-bottom:1px solid #e5e7eb; font-weight:700; background:#f9fafb; }
      .te-card .bd { padding:12px; }
      .msg { margin: 10px 0; padding:10px 12px; border-radius:8px; }
      .msg.ok { background:#ecfdf5; color:#065f46; border:1px solid #34d399; }
      .msg.err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
      .btn { display:inline-block; padding:8px 12px; border-radius:8px; border:1px solid #e5e7eb; background:#1877f2; color:#fff; cursor:pointer; text-decoration:none; }
      .btn.gray { background:#6b7280; }
      .btn.light { background:#fff; color:#111827; border:1px solid #e5e7eb; }
      .row { display:flex; gap:10px; align-items:center; margin:8px 0; flex-wrap:wrap; }
      .row label { min-width:180px; font-weight:600; }
      .row input[type="text"], .row input[type="color"], .row select, .row textarea { padding:8px 10px; border:1px solid #e5e7eb; border-radius:8px; }
      .row textarea { width:100%; min-height:280px; font-family: ui-monospace, Menlo, Consolas, monospace; }
      iframe#te_preview { width:100%; height:700px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
      .gallery { display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px; }
      .gallery .g { border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; background:#fff; }
      .gallery .t { padding:10px; font-weight:600; border-bottom:1px solid #e5e7eb; background:#f9fafb; }
      .gallery .a { padding:10px; display:flex; gap:8px; flex-wrap:wrap; }
      .muted { color:#6b7280; font-size:12px; }
    </style>
</head>
<body>
<div class="te-container">
  <div class="te-header">
    <div>
      <h2 style="margin:0;">🎨 Theme-Editor</h2>
      <div class="muted">Aktuelles Theme: <strong><?php echo escape($currentTheme); ?></strong></div>
    </div>
    <div class="row">
      <a class="btn light" href="../index.php" target="_blank">Seite öffnen</a>
      <a class="btn gray" href="index.php">Zur Admin-Startseite</a>
    </div>
  </div>

  <?php if ($msg): ?><div class="msg ok"><?php echo escape($msg); ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?php echo escape($err); ?></div><?php endif; ?>

  <div class="te-tabs" id="te_tabs">
    <div class="te-tab active" data-tab="preview">Vorschau</div>
    <div class="te-tab" data-tab="colors">Farben</div>
    <div class="te-tab" data-tab="custom">Custom CSS</div>
    <div class="te-tab" data-tab="gallery">Galerie</div>
    <div class="te-tab" data-tab="upload">Upload</div>
  </div>

  <div class="te-body">
    <!-- Vorschau -->
    <div class="te-panel" data-panel="preview" style="display:block;">
      <div class="te-card">
        <div class="hd">Live-Vorschau</div>
        <div class="bd">
          <div class="row">
            <label for="te_theme_select">Theme wählen</label>
            <select id="te_theme_select">
              <?php foreach ($themes as $t): ?>
                <option value="<?php echo escape($t); ?>" <?php echo (strcasecmp($t, $currentTheme . '.css') === 0 ? 'selected' : ''); ?>>
                  <?php echo escape($t); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn" id="btn_preview_theme">In Vorschau laden</button>
            <button class="btn light" id="btn_reload_preview">Vorschau neu laden</button>
          </div>
          <iframe id="te_preview" src="../index.php?te_preview=1&v=<?php echo time(); ?>"></iframe>
          <div class="muted" style="margin-top:8px;">
            Hinweis: Die Vorschau wechselt das Theme im iFrame ohne Dateien zu verändern. Dauerhafte Änderungen erfolgen über „Farben", „Custom CSS" oder „Upload".
          </div>
        </div>
      </div>
    </div>

    <!-- Farben -->
    <div class="te-panel" data-panel="colors" style="display:none;">
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
        <input type="hidden" name="action" value="save_variables">
        <div class="te-card">
          <div class="hd">Farben anpassen (CSS-Variablen)</div>
          <div class="bd">
            <?php foreach ($vars as $name => $value):
              $label = $name;
              if ($name === '--color-primary') $label = 'Primärfarbe';
              elseif ($name === '--surface') $label = 'Fläche';
              elseif ($name === '--surface-2') $label = 'Fläche 2';
              elseif ($name === '--surface-3') $label = 'Fläche 3';
              elseif ($name === '--border-color') $label = 'Rahmenfarbe';
              elseif ($name === '--color-text') $label = 'Text';
              elseif ($name === '--muted-text') $label = 'Text gedämpft';
              elseif ($name === '--input-bg') $label = 'Eingabe Hintergrund';
              elseif ($name === '--input-text') $label = 'Eingabe Text';
            ?>
            <div class="row">
              <label><?php echo escape($label); ?> <span class="muted">(<?php echo escape($name); ?>)</span></label>
              <input type="color" class="te-color" data-var="<?php echo escape($name); ?>" value="<?php echo escape($value); ?>" />
              <input type="text" name="vars[<?php echo escape($name); ?>]" class="te-text" data-var="<?php echo escape($name); ?>" value="<?php echo escape($value); ?>" style="min-width:260px;" />
              <button type="button" class="btn light te-apply" data-var="<?php echo escape($name); ?>">Nur Vorschau</button>
            </div>
            <?php endforeach; ?>
            <div class="row" style="margin-top:14px;">
              <button type="submit" class="btn">Speichern (schreibt Variablen in custom1.css)</button>
              <button type="button" id="btn_apply_all" class="btn light">Alle nur in Vorschau anwenden</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Custom CSS -->
    <div class="te-panel" data-panel="custom" style="display:none;">
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
        <input type="hidden" name="action" value="save_custom_css">
        <div class="te-card">
          <div class="hd">Custom CSS direkt bearbeiten (static/styles/custom1.css)</div>
          <div class="bd">
            <div class="row">
              <textarea name="custom_css" spellcheck="false"><?php echo escape($currentCss); ?></textarea>
            </div>
            <div class="row">
              <button type="submit" class="btn">Speichern</button>
              <button type="button" id="btn_preview_custom_css" class="btn light">Nur in Vorschau anwenden (nicht speichern)</button>
            </div>
            <div class="muted">
              Hinweis: Der Theme-Editor-Variablenblock (<?php echo escape(TE_BLOCK_START); ?> … <?php echo escape(TE_BLOCK_END); ?>) kann hier mitbearbeitet oder durch „Farben"-Tab überschrieben werden.
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Galerie -->
    <div class="te-panel" data-panel="gallery" style="display:none;">
      <div class="te-card">
        <div class="hd">Theme-Galerie (Vorschau)</div>
        <div class="bd">
          <div class="gallery">
            <?php foreach ($themes as $t): ?>
              <div class="g">
                <div class="t"><?php echo escape($t); ?></div>
                <div class="a">
                  <button class="btn light g-preview" data-theme="<?php echo escape($t); ?>">In Vorschau laden</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="muted" style="margin-top:8px;">
            Die Galerie lädt Themes nur in der Vorschau; kein dauerhaftes Umschalten.
          </div>
        </div>
      </div>
    </div>

    <!-- Upload -->
    <div class="te-panel" data-panel="upload" style="display:none;">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?php echo escape($csrf); ?>">
        <input type="hidden" name="action" value="upload_css">
        <div class="te-card">
          <div class="hd">Eigene CSS hochladen</div>
          <div class="bd">
            <div class="row">
              <label for="cssfile">CSS-Datei</label>
              <input type="file" id="cssfile" name="cssfile" accept=".css,text/css" />
            </div>
            <div class="row">
              <label>Modus</label>
              <label><input type="radio" name="mode" value="append" checked> an custom1.css anhängen</label>
              <label><input type="radio" name="mode" value="replace"> custom1.css ersetzen</label>
            </div>
            <div class="row">
              <button type="submit" class="btn">Upload ausführen</button>
            </div>
            <div class="muted">
              Sicherheit: Es wird ausschließlich in static/styles/custom1.css geschrieben; keine weiteren Dateien werden angelegt oder eingebunden.
            </div>
          </div>
        </div>
      </form>
    </div>

  </div>
</div>

<script src="../static/scripts/jquery.min.js"></script>
<script src="../static/scripts/admin.js"></script>
<script src="../static/scripts/theme-editor.js"></script>
<script>
// Tabs
(function(){
  var tabs = document.getElementById('te_tabs');
  if (!tabs) return;
  tabs.addEventListener('click', function(e){
    var t = e.target.closest('.te-tab');
    if (!t) return;
    var tab = t.getAttribute('data-tab');
    tabs.querySelectorAll('.te-tab').forEach(function(n){ n.classList.toggle('active', n===t); });
    document.querySelectorAll('.te-panel').forEach(function(p){ p.style.display = (p.getAttribute('data-panel')===tab?'block':'none'); });
  });
})();
</script>
</body>
</html>
