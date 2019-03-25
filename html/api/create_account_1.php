<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/register_account_requests.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $username = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['username'])) {
    $username = trim($_POST['username']);
  }

  /* VALIDATING ARGUMENTS */
  if($username === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing required parameter username');
    return;
  }

  if(strlen($username) < 3) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Username is too short');
    return;
  }

  for($i = 0; $i < strlen($username); $i++) {
    $ch = $username[i];
    if(!ctype_alnum($ch) && $ch !== '_' && $ch !== '-') {
      echo_fail(400, 'ARGUMENT_INVALID', "Invalid character in username (pos $i has '$ch')");
      return;
    }
  }

  /* VALIDATING AUTHORIZATION */
  $success_args = array('message' => 'If that account is registered and no account request has been made recently, then a message will be sent to that account in the next few minutes with a link to claim your account.');

  $conn = create_db_connection();
  $person = PersonMapping::fetch_by_username($conn, $username);

  if($person === null) {
    $person = new Person(-1, $username, null, null, 0, time(), time());
    PersonMapping::insert_row($conn, $person);
  }

  $latest_request = RegisterAccountRequestMapping::fetch_latest_by_person_id($conn, $person->id);
  if($latest_request !== null && ($latest_request->consumed !== 1 && $latest_request->created_at > time() - 60 * 60 * 24)) {
    echo_success($success_args);
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  $token = bin2hex(random_bytes(32));
  $request = new RegisterAccountRequest(-1, $person->id, $token, 0, time(), null);
  RegisterAccountRequestMapping::insert_row($conn, $request);
  $conn->close();
  echo_success($success_args);
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
