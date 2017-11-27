<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/register_account_requests.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $username = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['username'])) {
    $username = $_POST['username'];
  }

  /* VALIDATING ARGUMENTS */
  if($username === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing required parameter username');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  $success_args = array('message' => 'If that account is registered and no password reset request has been made recently, then a message will be sent to that account in the next few minutes with a link to reset your password.');

  $conn = create_db_connection(); 
  $person = PersonMapping::fetch_by_username($conn, $username);
  
  if($person === null) {
    echo_success($success_args);
    $conn->close();
    return;
  }

  $latest_request = RegisterAccountRequestMapping::fetch_latest_by_person_id($conn, $person->id);
  if($latest_request !== null && ($latest_request->consumed !== 1 && $latest_request->created_at > time() - 60 * 60 * 24)) {
    echo_success($success_args);
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  $token = bin2hex(random_bytes(32));
  $request = new RegisterAccountRequest(-1, $person->id, password_hash($token, PASSWORD_DEFAULT), 0, time(), null);
  RegisterAccountRequestMapping::insert_row($conn, $request);
  $conn->close();
  echo_success($success_args);
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>