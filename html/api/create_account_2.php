<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/register_account_requests.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $id = null;
  $token = null;
  $password = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['id']) && is_numeric($_POST['id'])) {
    $_id = intval($_POST['id']);
    if($_id > 0 && $_id < 100000) {
      $id = $_id;
    }
  }

  if(isset($_POST['token'])) {
    $token = $_POST['token'];
  }

  if(isset($_POST['password'])) {
    $password = $_POST['password'];
  }

  /* VALIDATING ARGUMENTS */
  if($id === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'id cannot be null!');
    return;
  }

  if($token === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'token cannot be null!');
    return;
  }

  if($password === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'password cannot be null!');
    return;
  }

  if(strlen($password) < 8) {
    echo_fail(400, 'PASSWORD_INVALID', 'Password must be at least 8 characters long!');
    return;
  }

  $token_err_type = 'TOKEN_INVALID';
  $token_err_mess = 'The provided token does not match our records. The link might be malformed, the token might be expired, or the token may have already been used';

  $conn = create_db_connection(); 
  $person = PersonMapping::fetch_by_id($conn, $id);
  if($person === null) {
    error_log('Person is null for ID ' . $id);
    echo_fail(400, $token_err_type, $token_err_mess);
    $conn->close();
    return;
  }

  /* VALIDATING AUTHORIZATION */
  $request = RegisterAccountRequestMapping::fetch_latest_by_person_id($conn, $id);
  if($request === null) {
    error_log('Request is null for id ' . $id);
    echo_fail(400, $token_err_type, $token_err_mess);
    $conn->close();
    return;
  }

  if($request->consumed !== 0) {
    error_log('Request is already consumed for id ' . $id);
    echo_fail(400, $token_err_type, $token_err_mess);
    $conn->close();
    return;
  }

  if($request->sent_at === null) {
    error_log('Request has not been sent for id ' . $id);
    echo_fail(400, $token_err_type, $token_err_mess);
    $conn->close();
    return;
  }

  if($token !== $request->token) {
    error_log('Token does not match for id ' . $id);
    echo_fail(400, $token_err_type, $token_err_mess);
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  $request->consumed = 1;
  RegisterAccountRequestMapping::update_row($conn, $request);

  $person->password_hash = password_hash($password, PASSWORD_DEFAULT);
  $person->updated_at = time();
  PersonMapping::update_row($conn, $person);

  $conn->close();
  echo_success(array('message' => 'Success! You will now be able to login with the provided password.'));
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
