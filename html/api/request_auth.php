<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/auth_levels.php';
require_once 'database/auth_requests.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  include_once 'pagestart.php';

  if($logged_in_person === null) {
    echo_fail(403, 'UNAUTHORIZED', 'Authentication is required for the specified arguments, but none were given');
    $conn->close();
    return;
  }

  $request = TemporaryAuthorizationRequestMapping::fetch_by_person_id($conn, $logged_in_person->id);

  if($request !== null) {
    echo_fail(429, 'TOO_MANY_REQUESTS', 'You have tried to do that too recently.');
    $conn->close();
    return;
  }

  TemporaryAuthorizationRequestMapping::create($conn, $logged_in_person->id);
  echo_success(array());
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
