<?php
require_once 'pagestart.php';
require_once 'api/common.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  if($logged_in_person === null) {
    echo_fail(403, 'UNAUTHORIZED', 'Authentication is required for the specified arguments, but none were given');
    $conn->close();
    return;
  }

  if($auth_level < $MODERATOR_PERMISSION) {
    echo_fail(403, 'INSUFFICIENT PERMISSIONS', 'You provided authentication, but your account has insufficient permission to perform the specified command');
    $conn->close();
    return;
  }
  $conn->close();
  
  $s = file_get_contents( '/home/timothy/USLBot/logs/app.log' );
  if(!$s) {
    echo_fail(500, 'SERVER ERROR', 'Failed to locate log file');
    return;
  }

  echo $s;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
