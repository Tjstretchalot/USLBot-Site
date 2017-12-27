<?php
require_once 'api/common.php';
require_once 'database/common.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  /* DEFAULT ARGUMENTS */
  $after = null;

  /* PARSING ARGUMENTS */
  if(isset($_GET['after']) && is_numeric($_GET['after'])) {
    $after = intval($_GET['after']);
  }

  /* VALIDATING ARGUMENTS */
  if($after !== null && $after <= 0) {
    echo_fail(400, 'INVALID_ARGUMENT', 'After is an optional parameter, but if it is set it must be a strictly positive integer');
    return;
  }

  /* VALIDATING AUTHORIZATION */

  /* PERFORMING REQUEST */
  $sql_conn = create_db_connection(); 

  $err_prefix = "actionlog.php";
  check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM actions_log WHERE (? IS NULL OR id > ?)"));
  check_db_error($sql_conn, $err_prefix, $stmt->bind_param("ii", $after, $after));
  check_db_error($sql_conn, $err_prefix, $stmt->execute());
  check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

  $actions = array();

  // we treat these like dictionaries to avoid repeat entries
  $person_ids_to_add = array();
  $subreddit_ids_to_add = array();

  while(($row = $res->fetch_assoc()) != null) {
    $actions[] = $row;

    $matches = array();
    $preg_result = preg_match( '{\{link person (\d+)\}}', $row->action, $matches );
    if($preg_result === False) {
      error_log('Regex for person failed with error ' . preg_last_error());
      echo_fail(500, 'SERVER_ERROR', 'An internal server error occurred.');
      $res->close();
      $stmt->close();
      $sql_conn->close();
      return;
    }elseif($preg_result === 1) {
      $personid = $matches[1];

      $person_ids_to_add[$personid] = True;
    }

    $matches = array();
    $preg_result = preg_match( '{\{link subreddit (\d+)\}}', $row->action, $matches );
    if($preg_result === False) {
      error_log('Regex for subreddit failed with error ' . preg_last_error());
      echo_fail(500, 'SERVER_ERROR', 'An internal server error occurred.');
      $res->close();
      $stmt->close();
      $sql_conn->close();
    }elseif($preg_result === 1) {
      $subredditid = $matches[1];

      $subreddit_ids_to_add[$subredditid] = True;
    }
  }

  $res->close();
  $stmt->close();

  $persons = array();
  $subreddits = array();

  $err_prefix = "actionlog.php fetch_persons";
  foreach($person_ids_to_add as $id=>$v) {
    check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT username FROM persons WHERE id=?"));
    check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $id));
    check_db_error($sql_conn, $err_prefix, $stmt->execute());
    check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());
    
    $row = $res->fetch_assoc();
    $res->close();
    $stmt->close();

    if($row === null) {
      error_log('Unknown person id ' . $id . '!');
    }else {
      $persons[$id] = $row;
    }
  }
  
  $err_prefix = "actionlog.php fetch_subreddits";
  foreach($subreddit_ids_to_add as $id=>$v) {
    check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT subreddit FROM monitored_subreddits WHERE id=?"));
    check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $id));
    check_db_error($sql_conn, $err_prefix, $stmt->execute());
    check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());
    
    $row = $res->fetch_assoc();
    $res->close();
    $stmt->close();

    if($row === null) {
      error_log('Unknown monitored subreddit id ' . $id . '!');
    }else {
      $subreddits[$id] = $row;
    }
  }
  

  echo_success(array('actions' => $actions, 'persons' => $persons, 'subreddits' => $subreddits));
  $sql_conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
