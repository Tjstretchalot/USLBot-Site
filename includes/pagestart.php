<?php
  require_once 'database/common.php';
  require_once 'database/site_sessions.php';
  require_once 'database/persons.php';
  require_once 'database/auth_levels.php';

  $conn = create_db_connection();

  $logged_in_person = null;
  $auth_level = -1;
  if(isset($_COOKIE['session_id'])) {
    $session = SiteSessionMapping::fetch_by_session_id($conn, $_COOKIE['session_id']);

    if($session === null || $session->expires_at <= time()) {
      unset($_COOKIE['session_id']);
      setcookie('session_id', '', time() - 3600, '/');
    }else {
      $logged_in_person = PersonMapping::fetch_by_id($conn, $session->person_id);

      $auth_level = $logged_in_person->auth_level;
      
      $temp_auth_level = TemporaryAuthorizationLevelMapping::fetch_by_person_id($conn, $logged_in_person->id);
      if($temp_auth_level->expires_at < time()) {
	TemporaryAuthorizationLevelMapping::delete_by_id($conn, $temp_auth_level->id);
      }elseif($temp_auth_level !== null && $temp_auth_level->auth_level > $auth_level) {
	$auth_level = $temp_auth_level->auth_level;
      }
    }
  }
?>
