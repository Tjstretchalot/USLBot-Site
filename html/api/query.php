<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/register_account_requests.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  /* CONSTANTS */

  // The hashtags that non-moderators are allowed to search for
  $NON_MODERATOR_HASHTAGS = array( '#sketchy', '#scammer', '#troll' );

  /* DEFAULT ARGUMENTS */

  /* 
   * Format options:
   *  1: ultra-compact: returns things like { success: true, data: { banned: true } }
   *  2: compact: returns things like { success: true, data: { history: { { kind: 'ban', subreddit: 'universalscammerlist', description: '#scammer', details: 'permanent', time: 1512412103 } } } }
   */
  $format = 1; 

  /*
   * Space seperated hashtags to search for, or the literal string 'all' for no restrictions.
   * 
   * All requires moderator permissions
   */
  $hashtags = null;

  // What to search for in the username. Currently this is compared to the username
  // using a mysql LIKE, so it supports % wildcards
  $query = null;

  /* PARSING ARGUMENTS */

  if(isset($_GET['format']) && is_numeric($_GET['format'])) {
    $_format = intval($_GET['format']);

    if($_format === 1 || $_format === 2) {
      $format = $_format;
    }
  }

  if(isset($_GET['hashtags'])) {
    $_hashtags = $_GET['hashtags'];

    $_expl_hashtags = explode(',', $_hashtags, 6);

    if(count($_expl_hashtags) <= 5) {
      $hashtags = $_expl_hashtags;
    }
  }

  if(isset($_GET['query'])) {
    $_query = $_GET['query'];
    if(strlen($_query) >= 3) {
      $query = $_query;
    }
  }

  /* VALIDATING ARGUMENTS */
  
  if($format !== 1 && $format !== 2) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter format');
    return;
  }

  if($hashtags === null || count($hashtags) === 0) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter hashtags (maximum 5 tags can be specified)');
    return;
  }

  if($query === null || strlen($query) < 3) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter query (minimum 3 characters)');
    return;
  }

  /* VALIDATING AUTHORIZATION */

  $required_auth = 0;

  foreach($hashtags as $tag) {
    if(!in_array($tag, $NON_MODERATOR_HASHTAGS)) {
      $required_auth = max($required_auth, $MODERATOR_PERMISSION);
    }
  }

  include_once('pagestart.php');
  if($required_auth > 0 && $logged_in_person === null) {
    echo_fail(403, 'UNAUTHORIZED', 'Authentication is required for the specified arguments, but none were given');
    $conn->close();
    return;
  }

  if($required_auth > 0 && $logged_in_person->auth_level < $required_auth) {
    echo_fail(403, 'INSUFFICIENT PERMISSIONS', 'You provided authentication, but your account has insufficient permission to perform the specified command');
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */

  // Fetching the person
  $person = PersonMapping::fetch_like_username($conn, $query);

  if($person === null) {
    if($format === 1) {
      echo_success(array( 'banned' => false));
      $conn->close();
      return;
    }else {
      echo_success(array( 'history' => array()));
      $conn->close();
      return;
    }
  }

  $is_searching_all = in_array('all', $hashtags);

  // Getting all ban histories, unfiltered
  $sql = 'SELECT * FROM ban_histories WHERE banned_person_id=?';
  check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
  check_db_error($conn, $err_prefix, $stmt->bind_param('i', $person->id));
  check_db_error($conn, $err_prefix, $stmt->execute());
  check_db_error($conn, $err_prefix, $res = $stmt->get_result());
  
  $ban_history = array();

  while(($row = $res->fetch_assoc()) != null) {
    $ban_history[] = array( 'bh' => $row );
  }

  $res->close();
  $stmt->close();

  // Returning if there is no bans, unless we're searching for all (in 
  // which case we should return unpaired unbans)
  if(count($ban_history) === 0) {
    if($format === 1) {
      echo_success(array( 'banned' => false ));
      $conn->close();
      return;
    }elseif(!$is_searching_all) {
      echo_success(array( 'history' => array() ));
      $conn->close();
      return;
    }
  }

  // Cleansing the ban_history of any bans that don't meet the search
  // criteria, and keeping track of the ones we removed 
  $invalid_bhs = array();
  if(!$is_searching_all) {
    $valid_bhs = array();
    foreach($ban_history as $bh) {
      $desc = $bh['bh']['ban_description'];

      $found_tag = false;
      foreach($hashtags as $tag) {
        if(strpos($desc, $tag) !== false) {
	  $found_tag = true;
	  break;
	}
      }

      if($found_tag) {
	$valid_bhs[] = $bh;
      }else {
	$invalid_bhs[] = $bh;
      }
    }
    $ban_history = $valid_bhs;
  }

  // Fetching the corresponding handled_mod_action for the remaining 
  // ban histories
  foreach($ban_history as $bh) {
    $sql = 'SELECT * FROM handled_modactions WHERE id=?';
    check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
    check_db_error($conn, $err_prefix, $stmt->bind_param('i', $bh['bh']['handled_modaction_id']));
    check_db_error($conn, $err_prefix, $stmt->execute());
    check_db_error($conn, $err_prefix, $res = $stmt->get_result());
    $row = $res->fetch_assoc();
    if($row === null) {
      $res->close();
      $stmt->close();
      echo_fail(500, 'SERVER ERROR', 'A mysql database conflict was detected (foreign key)');
      return;
    }
    $bh['hma'] = $row;
    $res->close();
    $stmt->close();
  }

  // Fetching the unfiltered list of unban actions
  $sql = 'SELECT * FROM unban_histories WHERE unbanned_person_id=?';
  check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
  check_db_error($conn, $err_prefix, $stmt->bind_param('i', $person->id));
  check_db_error($conn, $err_prefix, $stmt->execute());
  check_db_error($conn, $err_prefix, $res = $stmt->get_result());
  
  $unban_history = array();

  while(($row = $res->fetch_assoc()) != null) {
    $unban_history[] = array( 'ubh' => $row );
  }

  $res->close();
  $stmt->close();

  // Fetching the corresponding handled_mod_action for the 
  // unban histories
  foreach($unban_history as $ubh) {
    $sql = 'SELECT * FROM handled_modactions WHERE id=?';
    check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
    check_db_error($conn, $err_prefix, $stmt->bind_param('i', $ubh['ubh']['handled_modaction_id']));
    check_db_error($conn, $err_prefix, $stmt->execute());
    check_db_error($conn, $err_prefix, $res = $stmt->get_result());
    $row = $res->fetch_assoc();
    if($row === null) {
      $res->close();
      $stmt->close();
      echo_fail(500, 'SERVER ERROR', 'A mysql database conflict was detected (foreign key)');
      return;
    }
    $ubh['hma'] = $row;
    $res->close();
    $stmt->close();
  }

  // At this point we can return for the format type 1
  if($format === 1) {
    if(count($ban_history) > 0 && count($unban_history) === 0) {
      echo_success(array('banned' => true));
      $conn->close();
      return;
    }

    $latest_ban_time = 0;
    foreach($ban_history as $bh) {
      $latest_ban_time = max($latest_ban_time, strtotime($bh['hma']['occurred_at']));
    }
    $latest_unban_time = 0;
    foreach($unban_history as $ubh) {
      $latest_unban_time = max($latest_unban_time, strtotime($ubh['hma']['occurred_at']));
    }

    echo_success(array('banned' => ($latest_ban_time > $latest_unban_time)));
    $conn->close();
    return;
  }

  // From here we assume we want format type 2

  function create_combined($filt_bhs, $filt_ubhs) {
    $comb = array();

    foreach($filt_bhs as $bh) {
      $comb[] = array(
        'kind' => 'ban',
	'subreddit' => $bh['sub']['subreddit'],
	'description' => $bh['bh']['ban_description'],
	'details' => $bh['bh']['ban_details'],
	'time' => strtotime($bh['hma']['occurred_at'])
      );
    }

    foreach($filt_ubhs as $ubh) {
      $comb[] = array(
        'kind' => 'unban',
	'subreddit' => $ubh['sub']['subreddit'],
	'time' => strtotime($ubh['hma']['occurred_at'])
      );
    }

    return $comb;
  };

  function fetch_subs_bh($sql_c, $bhs) {
    foreach($bhs as $bh) {
      check_db_error($sql_c, $err_prefix, $stmt = $sql_c->prepare('SELECT * FROM monitored_subreddits WHERE id=?'));
      check_db_error($sql_c, $err_prefix, $stmt->bind_param('i', $bh['hma']['monitored_subreddit_id']));
      check_db_error($sql_c, $err_prefix, $stmt->execute());
      check_db_error($sql_c, $err_prefix, $res = $stmt->get_result());

      $bh['sub'] = $res->fetch_assoc();
      $res->close();
      $stmt->close();
    }
  };

  function fetch_subs_ubh($sql_c, $ubhs) {
    foreach($ubhs as $ubh) {
      $mon_sub_id = $ubh['hma']['monitored_subreddit_id'];
      check_db_error($sql_c, $err_prefix, $stmt = $sql_c->prepare('SELECT * FROM monitored_subreddits WHERE id=?'));
      check_db_error($sql_c, $err_prefix, $stmt->bind_param('i', $mon_sub_id));
      check_db_error($sql_c, $err_prefix, $stmt->execute());
      check_db_error($sql_c, $err_prefix, $res = $stmt->get_result());

      $ubh['sub'] = $res->fetch_assoc();
      $res->close();
      $stmt->close();
    }
  }

  // We can return if we're searching for all
  if($is_searching_all) {
    fetch_subs_bh($conn, $ban_history);
    fetch_subs_ubh($conn, $unban_history);
    echo_success(array('history' => create_combined($ban_history, $unban_history)));
    $conn->close();
    return;
  }

  // Otherrwise, we need to pair up the unbans with bans
  $valid_ubhs = array();
  $all_bhs = ($ban_history + $invalid_bhs);
  foreach($unban_history as $ubh) {
    $ubh_occ_at_php = strtotime($ubh['hma']['occurred_at']);
    $most_applicable_bh = null;
    foreach($all_bhs as $bh) {
      if(!isset($bh['hma']['occurred_at__php'])) {
	$bh['hma']['occurred_at__php'] = strtotime($bh['hma']['occurred_at']);
      }

      if($bh['hma']['monitored_subreddit_id'] !== $ubh['hma']['monitored_subreddit_id']) {
	continue;
      }

      if($bh['hma']['occurred_at__php'] > $ubh_occ_at_php) {
	continue;
      }

      if($most_applicable_bh !== null && $bh['hma']['occurred_at__php'] < $most_applicable_bh['hma']['occurred_at__php']) {
	continue;
      }

      $most_applicable_bh = $bh;
    }

    if($most_applicable_bh !== null && !in_array($most_applicable_bh, $invalid_bhs)) {
      $valid_ubhs[] = $ubh;
    }
  }
  fetch_subs_bh($conn, $ban_history);
  fetch_subs_ubh($conn, $valid_ubhs);
  echo_success(array('history' => create_combined($ban_history, $valid_ubhs)));
  $conn->close();
  return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
