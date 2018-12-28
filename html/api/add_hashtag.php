<?php
require_once 'api/common.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $hashtag = null;
  $description = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['hashtag'])) {
    $hashtag = $_POST['hashtag'];
  }

  if(isset($_POST['description'])) {
    $description = $_POST['description'];
  }

  /* VALIDATING ARGUMENTS */
  if($hashtag === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'hashtag is a required argument for this endpoint');
    return;
  }

  if($description === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'description is a required argument for this endpoint');
    return;
  }

  if(strlen($hashtag) < 3) {
    echo_fail(400, 'ARGUMENT_INVALID', 'hashtag is too short; use descriptive tags');
    return;
  }

  if(strlen($description) < 5) {
    echo_fail(400, 'ARGUMENT_INVALID', 'description is too short');
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

  /* SECONDARY VALIDATING ARGUMENTS */
  $existing_tag = DatabaseHelper::fetch_one($conn, 'SELECT 1 FROM hashtags WHERE tag=? LIMIT 1', array('s', $hashtag));
  if($existing_tag !== null) {
    echo_fail(400, 'That tag already exists!');
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  DatabaseHelper::execute($conn, 'INSERT INTO hashtags (tag, description, submitter_person_id, last_updated_by_person_id,
    created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())', array(
      array('s', $hashtag), array('s', $description), array('i', $logged_in_person->id), array('i', $logged_in_person->id)
    ));

  echo_success(array());
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
