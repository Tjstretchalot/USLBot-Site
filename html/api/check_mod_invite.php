<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/site_sessions.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $id = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id = intval($_POST['id']);
  }

  /* VALIDATING ARGUMENTS */
  if($id === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'id is missing or invalid!');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  include_once('pagestart.php');
  if($logged_in_person === null) {
    echo_fail(403, 'Authentication is required for this endpoint');
    $conn->close();
    return;
  }

  if($auth_level < $MODERATOR_PERMISSION) {
    echo_fail(403, 'Access denied, insufficient permission');
    $conn->close();
    return;
  }

  $row = DatabaseHelper::fetch_one($conn, 'SELECT id,subreddit,fulfilled_at,CAST(success AS unsigned integer) as success FROM accept_mod_inv_requests WHERE mod_person_id=? AND id=? LIMIT 1', array(
    array('i', $logged_in_person->id),
    array('i', $id),
  ));

  if($row === null) {
    echo_fail(400, 'NOT_FOUND', 'There is no request with that id!');
    $conn->close();
    return;
  }
  $success = $row->success === 1;

  $conn->close();
  if($row->fulfilled_at === null) {
    echo_success(array('subreddit' => $row->subreddit, 'request_id' => $row->id, 'fulfilled' => false));
    return;
  }

  echo_success(array('subreddit' => $row->subreddit, 'request_id' => $row->id, 'fulfilled' => true, 'fulfilled_at' => strtotime($row->fulfilled_at), 'success' => $success));
  return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
