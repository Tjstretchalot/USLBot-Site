<?php
  require_once 'database/common.php';
  require_once 'database/site_sessions.php';
  require_once 'database/persons.php';

  $conn = create_db_connection();

  $logged_in_person = null;
  if(isset($_COOKIE['session_id'])) {
    $session = SiteSessionMapping::fetch_by_session_id($conn, $_COOKIE['session_id']);

    if($session === null || $session->expires_at <= time()) {
      unset($_COOKIE['session_id']);
      setcookie('session_id', '', time() - 3600, '/');
    }else {
      $logged_in_person = PersonMapping::fetch_by_id($conn, $session->person_id); 
    }
  }
?>
