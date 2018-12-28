<?php
require_once 'api/common.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  /* DEFAULT ARGUMENTS */
  $hashtag = null;

  /* PARSING ARGUMENTS */
  if(isset($_GET['hashtag'])) {
    $hashtag = $_GET['hashtag'];
  }

  /* VALIDATING ARGUMENTS */
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

  /* PERFORMING REQUEST */
  if($hashtag === null) {
    $result = DatabaseHelper::fetch_all($conn, 'SELECT tag, description FROM hashtags', array());
    echo_success(array('hashtags' => $result));
    $conn->close();
    return;
  }

  $result = DatabaseHelper::fetch_one($conn, 'SELECT tag, description FROM hashtags WHERE tag=? LIMIT 1', array(
    array('s', $hashtag)
  ));

  if($result === null) {
    echo_fail(404, 'Hashtag not found');
    $conn->close();
    return;
  }

  echo_success(array('hashtags' => array($result)));
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
