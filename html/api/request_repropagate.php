<?php
require_once 'api/common.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $reason = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['reason'])) {
    $reason = $_POST['reason'];
  }

  /* VALIDATING ARGUMENTS */
  if($reason === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'reason is required at this endpoint!');
    return;
  }

  if(strlen($reason) < 3) {
    echo_fail(400, 'ARGUMENT_INVALID', 'reason must be at least 3 characters long!');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  include_once 'pagestart.php';
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
  $row = DatabaseHelper::fetch_one($conn, 'SELECT property_value FROM propagator_settings WHERE property_key=\'suppress_no_op\'', array());
  if($row !== null && $row->property_value !== 'false') {
    echo_fail(400, 'The bot is already configured to re-evaluate reddit-to-meaning. Please wait until the current operation completes.');
    $conn->close();
    return;
  }

  DatabaseHelper::execute($conn, 'INSERT INTO repropagation_requests (requester_person_id, reason, approved, received_at, handled_at) VALUES (?, ?, 0, NOW(), NULL)',
    array(
      array('i', $logged_in_person->id),
      array('s', $reason)
    ));

  echo_success(array());
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
