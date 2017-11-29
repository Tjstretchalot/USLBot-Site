<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/reset_password_requests.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $username = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['username'])) {
    $username = $_POST['username'];
  }

  /* VALIDATING ARGUMENTS */
  if($username === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Username cannot be empty!');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  /* PERFORMING REQUEST */
  $succ_res = array( 'message' => 'If that account exists and has been claimed and has not requested a password reset recently, a message will be sent to that account informing them what to do.' );
  $conn = create_db_connection(); 
  $person = PersonMapping::fetch_by_username($conn, $username);

  if($person === null) {
    echo_success($succ_res);
    $conn->close();
    return;
  }

  $curr_rpr = ResetPasswordRequestMapping::fetch_by_person_id($conn, $person->id);
  if($curr_rpr !== null && $curr_rpr->consumed !== 1 && ($curr_rpr->created_at - time()) < 60 * 60 * 24) {
    echo_success($succ_res);
    $conn->close();
    return;
  }

  $token = bin2hex(random_bytes(32));
  $rpr = new ResetPasswordRequest(-1, $person->id, $token, 0, time(), null);
  ResetPasswordRequestMapping::insert_row($conn, $rpr);

  echo_success($succ_res);
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
