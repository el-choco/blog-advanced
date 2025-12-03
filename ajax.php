<?php
// IMPORTANT: No output before this point (no BOM, no whitespace)
// Main AJAX endpoint

require_once __DIR__ . '/common.php'; // bootstraps app and defines PROJECT_PATH
require_once PROJECT_PATH . 'app/categories.class.php';
require_once PROJECT_PATH . 'app/post.class.php';
require_once PROJECT_PATH . 'app/comment.class.php'; // bind DB-backed comments

header('Content-Type: application/json; charset=utf-8');

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

		default:
			echo json_encode(['error' => true, 'msg' => 'Unknown action']);
	}
} catch (Exception $e) {
	echo json_encode(['error' => true, 'msg' => $e->getMessage()]);
}