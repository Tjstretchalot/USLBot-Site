<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/site_sessions.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $username = null;
  $password = null;
  $duration = 'forever';

  /* PARSING ARGUMENTS */
  if(isset($_POST['username'])) {
    $username = $_POST['username'];
  }

  if(isset($_POST['password'])) {
    $password = $_POST['password'];
  }

  if(isset($_POST['duration'])) {
    $duration = $_POST['duration'];
  }

  /* VALIDATING ARGUMENTS */
  if($username === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Username cannot be empty!');
    return;
  }

  if($password === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Password cannot be empty!');
    return;
  }

  if($duration === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Duration cannot be empty!');
    return;
  }

  if($duration !== 'forever' && $duration !== '1day' && $duration !== '30days') {
    echo_fail(400, 'ARGUMENT_INVALID', 'Duration must be forever, 1day, or 30days');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  if(isset($_COOKIE['session_id'])) {
    echo_fail(403, 'ALREADY_LOGGED_IN', 'You must be logged out to do that!');
    return;
  }

  /* PERFORMING REQUEST */
  $conn = create_db_connection(); 
  $person = PersonMapping::fetch_by_username($conn, $username);

  if($person === null) {
    echo_fail(400, 'BAD_PASSWORD', 'That username/password combination is not correct.');
    return;
  }

  if($person->password_hash === null || !password_verify($password, $person->password_hash)) {
    echo_fail(400, 'BAD_PASSWORD', 'That username/password combination is not correct.');
    return;
  }

  $session_id = bin2hex(random_bytes(32));
  $expires_at = null;
  if($duration === '30days') {
    $expires_at = time() + 60 * 60 * 24 * 30;
  }elseif($duration === '1day') {
    $expires_at = time() + 60 * 60 * 24;
  }

  $session = SiteSessionsMapping::create_and_save($conn, $session_id, $person->id, time(), $expires_at); 
  echo_success(array('session_id' => $session_id));
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>