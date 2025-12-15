<?php
require_once 'common.php';

/* Labels */
$labelSearch     = $lang['Search']            ?? 'Search';
$labelEnterTerm  = $lang['Enter search term'] ?? 'Enter a search term';
$labelResults    = $lang['Results']           ?? 'Results';
$labelQuery      = $lang['Query']             ?? 'Query';
$labelNoResults  = $lang['No files available']?? 'No results found';
$labelBack       = $lang['Back to Blog']      ?? 'Back to Blog';

/* Request */
$q      = trim((string)($_GET['q'] ?? ''));
$limit  = max(1, min((int)($_GET['limit'] ?? 20), 50));
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* Helpers */
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function likeTerm($s) {
  $s = str_replace(['%','_'], ['\\%','\\_'], $s);
  return "%$s%";
}
function highlight($text, $q) {
  if ($q === '') return esc($text);
  $safe  = esc($text);
  $q_esc = preg_quote(esc($q), '/');
  return preg_replace('/(' . $q_esc . ')/i', '<mark>$1</mark>', $safe);
}
/* Link builder:
   - Your frontend uses anchors like index.php#id=67
   - Always return that format for search results */
function postLink(int $id): string {
  return 'index.php#id=' . $id;
}

/* Theme (same CSS as the rest of the site) */
$theme = Config::get_safe('theme', 'theme01');
$theme = preg_replace('/\.css$/i', '', trim((string)$theme));
$theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
if ($theme === '') { $theme = 'theme01'; }

/* Search */
$results = [];
$total   = 0;

try {
  // Use project DB wrapper (there is no global db() function)
  $db = DB::get_instance();

  if ($q !== '') {
    $term = likeTerm($q);

    // Columns that actually exist in your schema
    $searchCols = ['plain_text','text','content','feeling','location','persons'];

    // WHERE conditions: posts columns, category name, and comment text
    $whereParts = [];
    foreach ($searchCols as $c) {
      $whereParts[] = "p.$c LIKE ?";
    }
    $whereParts[] = "COALESCE(c.name, '') LIKE ?";
    $whereParts[] = "EXISTS (SELECT 1 FROM comments cm
                             WHERE cm.post_id = p.id
                               AND cm.content LIKE ?)";
    $whereSql = implode(' OR ', $whereParts);

    // Bind set for COUNT/SELECT (same order as in $whereParts)
    $bind = array_fill(0, count($whereParts), $term);

    // COUNT
    $countSql = "
      SELECT COUNT(*) AS cnt
      FROM posts p
      LEFT JOIN categories c ON c.id = p.category_id
      WHERE ($whereSql)
    ";
    $total = (int)($db->query($countSql, $bind)->first()['cnt'] ?? 0);

    // Use datetime if present (your schema has it), otherwise fallback to id
    $orderExpr = "COALESCE(p.datetime, NOW())";

    // Excerpt prefers plain_text > text > content
    $excerptExpr = "COALESCE(p.plain_text, p.text, p.content, '')";

    // Important: inline LIMIT/OFFSET as integers (MySQL + emulate prepares OFF)
    $limit  = (int)$limit;
    $offset = (int)$offset;

    $selectSql = "
      SELECT
        p.id,
        $excerptExpr AS content_like,
        COALESCE(c.name, '') AS category_name,
        p.datetime AS created_at
      FROM posts p
      LEFT JOIN categories c ON c.id = p.category_id
      WHERE ($whereSql)
      ORDER BY $orderExpr DESC, p.id DESC
      LIMIT $limit OFFSET $offset
    ";
    $rows = $db->query($selectSql, $bind)->all();

    foreach ($rows as $row) {
      $excerpt = trim(strip_tags((string)($row['content_like'] ?? '')));
      if ($excerpt === '') $excerpt = '…';
      if (mb_strlen($excerpt) > 220) $excerpt = mb_substr($excerpt, 0, 220) . '…';
      $title   = mb_strlen($excerpt) > 80 ? mb_substr($excerpt, 0, 80) . '…' : $excerpt;

      $results[] = [
        'id'            => (int)($row['id'] ?? 0),
        'title'         => $title,
        'excerpt'       => $excerpt,
        'created_at'    => $row['created_at'] ?? null,
        'category_name' => (string)($row['category_name'] ?? ''),
      ];
    }
  }
} catch (Throwable $e) {
  // Keep UI clean; consider logging if needed
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo esc($labelSearch); ?> - <?php echo esc(Config::get('title')); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="static/styles/main.css" rel="stylesheet" type="text/css" />
  <link href="static/styles/<?php echo esc($theme); ?>.css" rel="stylesheet" type="text/css" />
  <link href="static/styles/custom1.css" rel="stylesheet" type="text/css" />
  <style>
    .search-wrap { background: var(--surface-2); border-bottom: 1px solid var(--border-color); }
    .search-headbar {
      max-width: 1000px; margin: 0 auto; padding: 10px 12px;
      display: flex; justify-content: space-between; align-items: center; gap: 10px;
    }
    .search-container { max-width: 1000px; margin: 12px auto 20px; padding: 0 12px; }
    .search-card {
      background: var(--surface);
      border: 1px solid var(--border-color);
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,.06);
      padding: 14px;
    }
    .search-title {
      display: flex; align-items: center; gap: 8px;
      font-size: 28px; font-weight: 800; margin: 0 0 10px; color: var(--color-text);
    }
    .search-title .icon { font-size: 26px; line-height: 1; }
    .search-form-inline { display:flex; gap:8px; margin-bottom:12px; }
    .search-form-inline .headbar-search-input { flex:1; min-width: 240px; height: 36px; }
    .search-meta { color: var(--muted-text); margin: 6px 0 12px; }
    .search-result-card {
      background: var(--surface); border: 1px solid var(--border-color);
      border-radius: 8px; padding: 12px; margin-bottom: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,.06);
    }
    .search-result-title { font-weight: 700; margin-bottom: 6px; color: var(--color-text); }
    .search-excerpt { color: var(--color-text); }
    .search-category { font-size: 12px; color: var(--muted-text); }
    .pagination { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
    .pagination a {
      padding:6px 10px; border:1px solid var(--border-color);
      border-radius:6px; background: var(--surface); text-decoration:none; color: var(--color-text);
    }
  </style>
</head>
<body class="visitor-body">

  <div class="search-wrap">
    <div class="search-headbar">
      <a href="./" class="admin_btn">← <?php echo esc($labelBack); ?></a>
      <div></div>
    </div>
  </div>

  <div id="b_feed" class="search-container">
    <div class="search-card">
      <div class="search-title">
        <span class="icon">🔎</span><span><?php echo esc($labelSearch); ?></span>
      </div>

      <form method="get" action="search.php" class="search-form-inline">
        <input type="text" name="q" class="headbar-search-input" placeholder="<?php echo esc($labelSearch . '…'); ?>" value="<?php echo esc($q); ?>">
        <button class="admin_btn" type="submit"><?php echo esc($labelSearch); ?></button>
      </form>

      <?php if ($q === ''): ?>
        <div class="search-meta"><?php echo esc($labelEnterTerm); ?></div>
      <?php else: ?>
        <div class="search-meta">
          <?php echo esc($labelResults); ?>: <?php echo (int)$total; ?>
          • <?php echo esc($labelQuery); ?>: “<?php echo esc($q); ?>”
        </div>

        <?php if ($total === 0): ?>
          <div class="search-result-card"><?php echo esc($labelNoResults); ?></div>
        <?php else: ?>
          <?php foreach ($results as $row): ?>
            <div class="search-result-card">
              <div class="search-result-title">
                <a href="<?php echo esc(postLink((int)$row['id'])); ?>">
                  <?php echo highlight($row['title'], $q); ?>
                </a>
              </div>
              <div class="search-excerpt"><?php echo highlight($row['excerpt'], $q); ?></div>
              <?php if (!empty($row['category_name'])): ?>
                <div class="search-category">#<?php echo esc($row['category_name']); ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <?php
          $pages = (int)ceil(($total ?: 0) / $limit);
          if ($pages > 1):
            echo '<div class="pagination">';
            for ($i = 1; $i <= $pages; $i++) {
              $qs = http_build_query(['q'=>$q, 'limit'=>$limit, 'page'=>$i]);
              echo '<a href="search.php?' . esc($qs) . '"' . ($i===$page?' style="font-weight:700"':'') . '>' . $i . '</a>';
            }
            echo '</div>';
          endif;
          ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>