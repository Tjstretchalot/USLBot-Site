<?php
require_once 'api/common.php';
require_once 'database/helper.php';
require_once 'parsedown/Parsedown.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $markdown = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['markdown'])) {
    $markdown = $_POST['markdown'];
  }

  /* VALIDATING ARGUMENTS */
  if($markdown === null) {
    echo_fail(400, 'markdown is a required argument at this endpoint!');
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

  /* PERFORMING REQUEST */
  $parsedown = new Parsedown();
  $parsedown->setSafeMode(true);
  echo_success(array('html' => $parsedown->text($markdown)));
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
