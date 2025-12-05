<?php
// IMPORTANT: No output before this point (no BOM, no whitespace)
// Main AJAX endpoint

require_once __DIR__ . '/common.php'; // bootstraps app and defines PROJECT_PATH
require_once PROJECT_PATH . 'app/categories.class.php';
require_once PROJECT_PATH . 'app/post.class.php';
require_once PROJECT_PATH . 'app/comment.class.php'; // bind DB-backed comments

header('Content-Type: application/json; charset=utf-8');

// Helper: normalize comment/content to plain text safely
function decode_entities_deep($s) {
    $cur = (string)($s ?? '');
    for ($i = 0; $i < 3; $i++) {
        $next = html_entity_decode($cur, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($next === $cur) break;
        $cur = $next;
    }
    return $cur;
}
function to_plain_text($s) {
    $dec = decode_entities_deep($s);
    // Remove all tags
    $dec = preg_replace('/<[^>]*>/', ' ', $dec);
    // Basic Markdown cleanup (bold/italic/strike/headers/hr/links/images/code)
    $dec = preg_replace('/!\[[^\]]*]\([^)]+\)/', ' ', $dec);             // images
    $dec = preg_replace('/\[[^\]]*]\([^)]+\)/', ' ', $dec);              // links
    $dec = preg_replace('/`{1,3}[^`]*`{1,3}/', ' ', $dec);               // inline/fenced code
    $dec = preg_replace('/\*{1,3}([^*]+)\*{1,3}/', ' $1 ', $dec);        // **bold**, *italic*
    $dec = preg_replace('/_{1,3}([^_]+)_{1,3}/', ' $1 ', $dec);          // __bold__, _italic_
    $dec = preg_replace('/~~([^~]+)~~/', ' $1 ', $dec);                  // ~~strike~~
    $dec = preg_replace('/^\s{0,3}#{1,6}\s+/m', '', $dec);               // headers
    $dec = preg_replace('/^\s{0,3}[-*_]{3,}\s*$/m', '', $dec);           // hr
    // Normalize whitespace
    $dec = preg_replace('/\s+/u', ' ', $dec);
    return trim($dec);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
	switch ($action) {
		/* ===== Auth / session ===== */
		case 'handshake':
			echo json_encode(Post::handshake([]));
			break;

		case 'login':
			echo json_encode(Post::login([
				'nick' => $_GET['nick'] ?? $_POST['nick'] ?? '',
				'pass' => $_GET['pass'] ?? $_POST['pass'] ?? ''
			]));
			break;

		case 'logout':
			echo json_encode(Post::logout());
			break;

		/* ===== Posts CRUD ===== */
		case 'insert':
			echo json_encode(Post::insert([
				'text' => $_POST['text'] ?? '',
				'feeling' => $_POST['feeling'] ?? '',
				'persons' => $_POST['persons'] ?? '',
				'location' => $_POST['location'] ?? '',
				'content_type' => $_POST['content_type'] ?? '',
				'content' => $_POST['content'] ?? '',
				'privacy' => $_POST['privacy'] ?? '',
				'category_id' => $_POST['category_id'] ?? null
			]));
			break;

		case 'update':
			echo json_encode(Post::update([
				'id' => $_POST['id'] ?? 0,
				'text' => $_POST['text'] ?? '',
				'feeling' => $_POST['feeling'] ?? '',
				'persons' => $_POST['persons'] ?? '',
				'location' => $_POST['location'] ?? '',
				'content_type' => $_POST['content_type'] ?? '',
				'content' => $_POST['content'] ?? '',
				'privacy' => $_POST['privacy'] ?? '',
				'category_id' => $_POST['category_id'] ?? null
			]));
			break;

		case 'toggle_sticky':
			echo json_encode(Post::toggle_sticky(['id' => $_POST['id'] ?? 0]));
			break;

		case 'hide':
			echo json_encode(Post::hide(['id' => $_POST['id'] ?? 0]));
			break;

		case 'show':
			echo json_encode(Post::show(['id' => $_POST['id'] ?? 0]));
			break;

		case 'delete':
			echo json_encode(Post::delete(['id' => $_POST['id'] ?? 0]));
			break;

		case 'restore':
			echo json_encode(Post::restore(['id' => $_POST['id'] ?? 0]));
			break;

		case 'permanent_delete':
			echo json_encode(Post::permanent_delete(['id' => $_POST['id'] ?? 0]));
			break;

		/* ===== Date helpers ===== */
		case 'get_date':
			echo json_encode(Post::get_date(['id' => $_GET['id'] ?? $_POST['id'] ?? 0]));
			break;

		case 'set_date':
			echo json_encode(Post::set_date([
				'id' => $_POST['id'] ?? 0,
				'date' => $_POST['date'] ?? []
			]));
			break;

		/* ===== Uploads / link parse ===== */
		case 'upload_image':
			echo json_encode(Post::upload_image());
			break;

		case 'upload_file':
			echo json_encode(Post::upload_file());
			break;

		case 'parse_link':
			echo json_encode(Post::parse_link(['link' => $_GET['link'] ?? '']));
			break;

		/* ===== Trash ===== */
		case 'list_trash':
			echo json_encode(Post::list_trash([
				'limit' => $_GET['limit'] ?? 20,
				'offset' => $_GET['offset'] ?? 0
			]));
			break;

		/* ===== Feed ===== */
		case 'load':
			$filter = $_GET['filter'] ?? [];
			echo json_encode(Post::load([
				'filter' => is_array($filter) ? $filter : [],
				'limit' => $_GET['limit'] ?? 5,
				'offset' => $_GET['offset'] ?? 0,
				'sort' => $_GET['sort'] ?? 'default'
			]));
			break;

		/* ===== Edit modal ===== */
		case 'edit_data':
			$id = $_GET['id'] ?? $_POST['id'] ?? 0;
			echo json_encode(Post::edit_data(['id' => $id]));
			break;

		/* ===== Categories for sidebar ===== */
		case 'categories':
			$cats = Categories::withCounts();
			$data = array_map(function($c) {
				return [
					'id' => (int)$c['id'],
					'name' => (string)$c['name'],
					'slug' => (string)$c['slug'],
					'post_count' => (int)$c['post_count']
				];
			}, $cats ?: []);
			echo json_encode($data);
			break;

		/* ===== Comments endpoints (DB-backed via Comment class) ===== */
		case 'comment_get': // GET list for a post
		{
			$postId = (int)($_GET['post_id'] ?? 0);
			if ($postId <= 0) {
				echo json_encode(['error' => true, 'msg' => 'Invalid post_id']);
				break;
			}
			$resp = Comment::comment_get(['post_id' => $postId]);
			echo json_encode(['error' => false, 'post_id' => $postId, 'comments' => $resp['comments'], 'count' => $resp['count']]);
			break;
		}

		case 'comment_add': // POST add comment (pending unless auto_approve)
		{
			$r = [
				'post_id'       => (int)($_POST['post_id'] ?? 0),
				'author_name'   => (string)($_POST['author_name'] ?? $_POST['name'] ?? ''),
				'content'       => (string)($_POST['content'] ?? $_POST['text'] ?? ''),
				'website_check' => (string)($_POST['website_check'] ?? '')
			];
			try {
				$resp = Comment::comment_add($r);
				echo json_encode(['error' => false, 'msg' => 'ok', 'comment' => $resp]);
			} catch (Exception $e) {
				echo json_encode(['error' => true, 'msg' => $e->getMessage()]);
			}
			break;
		}

		case 'comment_approve': // POST approve comment
		{
			$id = (int)($_POST['id'] ?? $_POST['comment_id'] ?? 0);
			try {
				$resp = Comment::comment_approve(['id' => $id]);
				echo json_encode(['error' => false, 'msg' => 'approved', 'result' => $resp]);
			} catch (Exception $e) {
				echo json_encode(['error' => true, 'msg' => $e->getMessage()]);
			}
			break;
		}

		case 'comment_spam': // POST mark as spam
		{
			$id = (int)($_POST['id'] ?? $_POST['comment_id'] ?? 0);
			try {
				$resp = Comment::comment_spam(['id' => $id]);
				echo json_encode(['error' => false, 'msg' => 'spam', 'result' => $resp]);
			} catch (Exception $e) {
				echo json_encode(['error' => true, 'msg' => $e->getMessage()]);
			}
			break;
		}

		case 'comment_delete': // POST move to trash
		{
			$id = (int)($_POST['id'] ?? $_POST['comment_id'] ?? 0);
			try {
				$resp = Comment::comment_delete(['id' => $id]);
				echo json_encode(['error' => false, 'msg' => 'deleted', 'result' => $resp]);
			} catch (Exception $e) {
				echo json_encode(['error' => true, 'msg' => $e->getMessage()]);
			}
			break;
		}

		case 'comment_count': // GET count for a post (approved + pending if logged in)
		{
			$postId = (int)($_GET['post_id'] ?? 0);
			if ($postId <= 0) {
				echo json_encode(['error' => true, 'msg' => 'Invalid post_id']);
				break;
			}
			$resp = Comment::comment_get(['post_id' => $postId]);
			echo json_encode(['error' => false, 'post_id' => $postId, 'count' => $resp['count']]);
			break;
		}

		/* ===== Optional aliases for older scripts ===== */
		case 'comments': // alias list
		case 'get_comments':
			$postId = (int)($_GET['id'] ?? 0);
			$resp = $postId ? Comment::comment_get(['post_id' => $postId]) : ['comments' => [], 'count' => 0];
			echo json_encode(['error' => false, 'post_id' => $postId, 'comments' => $resp['comments'], 'count' => $resp['count']]);
			break;

		case 'add_comment': // alias add
			$r = [
				'post_id'     => (int)($_POST['id'] ?? 0),
				'author_name' => (string)($_POST['name'] ?? ''),
				'content'     => (string)($_POST['text'] ?? '')
			];
			try {
				$resp = Comment::comment_add($r);
				echo json_encode(['error' => false, 'msg' => 'ok', 'comment' => $resp]);
			} catch (Exception $e) {
				echo json_encode(['error' => true, 'msg' => $e->getMessage()]);
			}
			break;

		/* ===== Sidebar: comments grouped by category ===== */
		case 'comments_by_category': // GET grouped comments for sidebar
		{
			$perCat = (int)($_GET['limit'] ?? 5);
			if ($perCat < 1) $perCat = 5;

			try {
				// If a class method exists, use it; otherwise fallback to SQL.
				if (method_exists('Comment', 'comments_by_category')) {
					$resp = Comment::comments_by_category(['limit' => $perCat]);
					$groups = [];
					if (is_array($resp) && isset($resp['groups']) && is_array($resp['groups'])) {
						// Sanitize each comment to plain text for the sidebar (no HTML/markdown)
						foreach ($resp['groups'] as $g) {
							$g = (array)$g;
							$comments = [];
							foreach ((array)($g['comments'] ?? []) as $c) {
								$c = (array)$c;
								$comments[] = [
									'id'          => (int)($c['id'] ?? 0),
									'author_name' => (string)($c['author_name'] ?? ''),
									'content'     => to_plain_text($c['content'] ?? ''), // comment text plain
									'created_at'  => (string)($c['created_at'] ?? ''),
									'post_id'     => (int)($c['post_id'] ?? 0),
									'post_title'  => to_plain_text($c['post_title'] ?? '') // TITLE PLAIN
								];
							}
							$groups[] = [
								'category_id'   => (int)($g['category_id'] ?? 0),
								'category_name' => (string)($g['category_name'] ?? 'Ohne Kategorie'),
								'category_slug' => (string)($g['category_slug'] ?? 'uncategorized'),
								'comments'      => $comments
							];
						}
					}
					echo json_encode(['error' => false, 'groups' => $groups], JSON_UNESCAPED_UNICODE);
					break;
				}

				// Fallback SQL implementation
				$includePending = (class_exists('User') && method_exists('User', 'is_logged_in')) ? User::is_logged_in() : false;
				$statusSql = $includePending ? "c.status IN ('approved','pending')" : "c.status = 'approved'";

				// Obtain DB instance (adjust if your app uses a different accessor)
				if (class_exists('DB') && method_exists('DB', 'get_instance')) {
					$db = DB::get_instance();
				} else if (class_exists('Database') && method_exists('Database', 'get')) {
					$db = Database::get();
				} else {
					throw new Exception('Database accessor not found.');
				}

				// NOTE: Adjust column names if they differ in your schema
				$sql = "
					SELECT
						c.id AS comment_id,
						c.author_name,
						c.content AS comment_text,
						c.created_at,
						p.id AS post_id,
						COALESCE(p.plain_text, p.text, '') AS post_title,
						p.category_id,
						COALESCE(cat.name, 'Ohne Kategorie') AS category_name,
						COALESCE(cat.slug, 'uncategorized') AS category_slug
					FROM comments c
					INNER JOIN posts p ON p.id = c.post_id
					LEFT JOIN categories cat ON cat.id = p.category_id
					WHERE $statusSql
					ORDER BY cat.name ASC, c.created_at DESC
				";

				$rows = $db->query($sql)->all();

				$grouped = [];
				foreach ($rows as $r) {
					$key = (int)($r['category_id'] ?? 0);
					if (!isset($grouped[$key])) {
						$grouped[$key] = [
							'category_id' => $key,
							'category_name' => (string)$r['category_name'],
							'category_slug' => (string)$r['category_slug'],
							'comments' => []
						];
					}
					// limit per category
					if (count($grouped[$key]['comments']) < $perCat) {
						$grouped[$key]['comments'][] = [
							'id'          => (int)$r['comment_id'],
							'author_name' => (string)$r['author_name'],
							'content'     => to_plain_text($r['comment_text']),        // comment text plain
							'created_at'  => (string)$r['created_at'],
							'post_id'     => (int)$r['post_id'],
							'post_title'  => to_plain_text($r['post_title'])           // TITLE PLAIN
						];
					}
				}

				$groups = array_values($grouped);
				usort($groups, function($a, $b) {
					return strcmp($a['category_name'], $b['category_name']);
				});

				echo json_encode(['error' => false, 'groups' => $groups], JSON_UNESCAPED_UNICODE);
			} catch (Throwable $e) {
				echo json_encode(['error' => true, 'msg' => 'comments_by_category failed: '.$e->getMessage()]);
			}
			break;
		}

		default:
			echo json_encode(['error' => true, 'msg' => 'Unknown action']);
	}
} catch (Exception $e) {
	echo json_encode(['error' => true, 'msg' => $e->getMessage()]);
}