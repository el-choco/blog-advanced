<?php
include 'common.php';

$ajax = new Ajax();

try {
    $ajax->token();

    // Prepare inputs
    $request = array_merge(@$_POST, @$_GET);
    if(empty($request["action"])){
        throw new Exception("No action specified.");
    }

    $action = $request["action"];

    // Check if action is for comments
    if (strpos($action, 'comment_') === 0) {
        // Comment actions
        if(!method_exists('Comment', $action)){
            throw new Exception("Comment method was not found.");
        }
        $response = Comment::$action($request);
    } else {
        // Post actions (original behavior)
        if(!method_exists('Post', $action)){
            throw new Exception("Method was not found.");
        }
        $response = Post::$action($request);
    }

    $ajax->set_response($response);

    // Log
    Log::put("ajax_access", $request["action"]);

} catch (Exception $e) {
    $ajax->set_error($e->getMessage());
}

$ajax->json_response();
