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
    echo_fail(400, 'ARGUMENT_MISSING', 'hashtag is a required paramater at this endpoint');
    return;
  }

  if($description === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'description is a required paramater at this endpoint');
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

  if($logged_in_person->auth_level < $MODERATOR_PERMISSION) {
    echo_fail(403, 'Access denied, insufficient permission');
    $conn->close();
    return;
  }

  /* SECONDARY VALIDATING ARGUMENTS */
  $row = DatabaseHelper::fetch_one($conn, 'SELECT 1 FROM hashtags WHERE tag=? LIMIT 1', array(array('s', $hashtag)));
  if($row === null) {
    echo_fail(404, 'Unknown hashtag: \'' . $hashtag . '\', perhaps you want add_hashtag?');
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  DatabaseHelper::execute($conn, 'UPDATE hashtags SET description=?, last_updated_by_person_id=?, updated_at=NOW() WHERE tag=? LIMIT 1',
    array(array('s', $description), array('i', $logged_in_person->id), array('s', $hashtag)));

  echo_success(array());
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
