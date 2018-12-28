<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/site_sessions.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $subreddit = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['subreddit'])) {
    $subreddit = $_POST['subreddit'];
  }

  /* VALIDATING ARGUMENTS */
  if($subreddit === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter subreddit');
    return;
  }

  if(strlen($subreddit) < 3) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Subreddit is too short');
    return;
  }

  if(strpos($subreddit, ' ') !== False) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Subreddit contains spaces');
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

  DatabaseHelper::execute($conn, 'INSERT INTO accept_mod_inv_requests (mod_person_id, subreddit, created_at, fulfilled_at, success) VALUES (?, ?, NOW(), NULL, 0)', array(
    array('i', $logged_in_person->id),
    array('s', $subreddit)
  ));

  $req_id = $conn->insert_id;

  $conn->close();
  echo_success(array('subreddit' => $subreddit, 'request_id' => $req_id));
  return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
