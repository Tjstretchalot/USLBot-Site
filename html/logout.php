<?php
  require_once 'database/common.php';
  require_once 'database/site_sessions.php';
  require_once 'database/persons.php';

  $conn = create_db_connection();

  if(isset($_COOKIE['session_id'])) {
    $session = SiteSessionMapping::fetch_by_session_id($conn, $_COOKIE['session_id']);

    unset($_COOKIE['session_id']);
    setcookie('session_id', '', time() - 3600, '/');

    if($session !== null) {
      SiteSessionMapping::delete_by_id($conn, $session->id);
    }
  }

  $conn->close();

  header('Location: https://universalscammerlist.com');
?>
