<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/register_account_requests.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  /* CONSTANTS */
  $err_prefix = 'query.php';

  // The hashtags that non-moderators are allowed to search for
  $NON_MODERATOR_HASHTAGS = array( '#sketchy', '#scammer', '#troll' );

  /* DEFAULT ARGUMENTS */

  /*
  * Format options:
  *  1: ultra-compact: returns things like { success: true, data: { person: 'john', banned: true, reason: '#scammer' } }
  *  2: compact: returns things like { success: true, data: { person: 'john', grandfathered: false, history: { { kind: 'ban', subreddit: 'universalscammerlist', description: '#scammer', details: 'permanent', time: 1512412103 } } } }
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

  /*
  * Debug options:
  *  0: No debugging
  *  1: Debugging enabled (will clutter output)
  */
  $debug = 0;

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

  if(isset($_GET['debug'])) {
    $_debug = intval($_GET['debug']);

    if($_debug === 0 || $_debug === 1) {
      $debug = $_debug;
    }
  }

  function debug_echo($msg) {
    global $debug;
    if ($debug === 1) {
      echo($msg . "\n");
    }
  }

  /* VALIDATING ARGUMENTS */

  debug_echo('validating format...');

  if($format !== 1 && $format !== 2) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter format');
    return;
  }

  debug_echo('validating hashtags...');

  if($hashtags === null || count($hashtags) === 0) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter hashtags (maximum 5 tags can be specified)');
    return;
  }

  debug_echo('validating query...');

  if($query === null || strlen($query) < 3) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter query (minimum 3 characters)');
    return;
  }

  for($i = 0; $i < strlen($query); $i++) {
    $c = $query[$i];
    if(!ctype_alnum($c) && $c !== '-' && $c !== '_' && $c !== '%' && $c !== '\\') {
      echo_fail(400, 'ARGUMENT_INVALID', 'Invalid parameter query; has invalid character \'' . $c . '\'');
      return;
    }
  }

  /* VALIDATING AUTHORIZATION */

  debug_echo('validating authorization...');

  $required_auth = -1;

  if ($debug === 1) {
    $required_auth = max($required_auth, $MODERATOR_PERMISSION);
  }

  foreach($hashtags as $tag) {
    if(!in_array($tag, $NON_MODERATOR_HASHTAGS)) {
      $required_auth = max($required_auth, $MODERATOR_PERMISSION);
    }
  }

  include_once('pagestart.php');
  if($required_auth > -1 && $logged_in_person === null) {
    echo_fail(403, 'UNAUTHORIZED', 'Authentication is required for the specified arguments, but none were given');
    $conn->close();
    return;
  }

  if($required_auth > -1 && $auth_level < $required_auth) {
    echo_fail(403, 'INSUFFICIENT PERMISSIONS', 'You provided authentication, but your account has insufficient permission to perform the specified command');
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */

  debug_echo('performing request...');

  // Fetching the person
  debug_echo('fetching the person...');
  $person = PersonMapping::fetch_strict_then_like_username($conn, $query);

  if($person === null) {
    debug_echo('did not find a person');
    if($format === 1) {
      echo_success(array( 'person' => str_replace('%', '', $query), 'banned' => false));
      $conn->close();
      return;
    }else {
      echo_success(array( 'person' => str_replace('%', '', $query), 'grandfathered' => false, 'history' => array()));
      $conn->close();
      return;
    }
  }

  debug_echo('found a person');
  $is_searching_all = in_array('all', $hashtags);

  // Getting the traditional scammer list information
  $sql = 'SELECT * FROM traditional_scammers WHERE person_id=?';
  debug_echo('preparing ' . $sql);
  check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
  check_db_error($conn, $err_prefix, $stmt->bind_param('i', $person->id));
  check_db_error($conn, $err_prefix, $stmt->execute());
  check_db_error($conn, $err_prefix, $res = $stmt->get_result());
  $row = $res->fetch_assoc();
  $res->close();
  $stmt->close();
  debug_echo('executed ' . $sql);
  if($row !== null) {
    debug_echo('this person is a traditional scammer, checking if any relevant hashtags');
    $meets_reqs = $is_searching_all;
    if ($meets_reqs) {
      debug_echo('  nevermind, searching all');
    }

    if(!$meets_reqs) {
      foreach($hashtags as $tag) {
        if(strpos($row['description'], $tag) !== false) {
          $meets_reqs = true;
          debug_echo('matches on ' . $tag);
          break;
        }
      }
    }

    if($meets_reqs) {
      debug_echo('returning traditional scammer');
      $conn->close();
      if($format === 1) {
        echo_success(array( 'person' => $person->username, 'banned' => true, 'reason' => $row['description']));
        return;
      }else {
        echo_success(array( 'person' => $person->username, 'grandfathered' => true, 'description' => $row['description'], 'time' => strtotime($row['created_at']) ));
        return;
      }
    }
    debug_echo('ignoring traditional scammer; irrelevant');
  }

  // Getting all ban histories, unfiltered
  $sql = 'SELECT * FROM ban_histories WHERE banned_person_id=?';
  debug_echo('preparing ' . $sql);
  check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
  check_db_error($conn, $err_prefix, $stmt->bind_param('i', $person->id));
  check_db_error($conn, $err_prefix, $stmt->execute());
  check_db_error($conn, $err_prefix, $res = $stmt->get_result());
  debug_echo('executed ' . $sql);

  $ban_history = array();

  while(($row = $res->fetch_assoc()) != null) {
    $ban_history[] = new ArrayObject(array( 'bh' => $row ));
  }

  $res->close();
  $stmt->close();

  debug_echo('got ' . count($ban_history) . ' rows');

  // Returning if there is no bans, unless we're searching for all (in
  // which case we should return unpaired unbans)
  if(count($ban_history) === 0) {
    if($format === 1) {
      echo_success(array( 'person' => $person->username, 'banned' => false ));
      $conn->close();
      return;
    }elseif(!$is_searching_all) {
      echo_success(array( 'person' => $person->username, 'grandfathered' => false, 'history' => array() ));
      $conn->close();
      return;
    }
  }


  // Fetching the corresponding handled_mod_action for the ban histories
  debug_echo('Fetching corresponding hmas...');
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
  debug_echo('Found all matching hmas!');

  // Cleansing the ban_history of any bans that don't meet the search
  // criteria, and keeping track of the ones we removed
  $invalid_bhs = array();
  $changed_bhs = array();
  if(!$is_searching_all) {
    debug_echo('Cleaning irrelevant ban histories...');
    $valid_bhs = array();
    foreach($ban_history as $bh) {
      if(substr($bh['bh']['ban_details'], 0, strlen('changed to')) === 'changed to') {
        $changed_bhs[] = $bh;
        continue;
      }

      $desc = $bh['bh']['ban_description'];

      if($desc === null) {
        $invalid_bhs[] = $bh;
        continue;
      }

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
    debug_echo('After cleaning ban history reduced to ' . count($ban_history));
  }

  // Assigned the changed_bhs to either invalid or valid bhs
  debug_echo('assinging changed bhs');
  foreach($changed_bhs as $bh) {
    if($is_searching_all) {
      $valid_bhs[] = $bh;
      continue;
    }
    if(!isset($bh['hma']['occurred_at__php'])) {
      $bh['hma']['occurred_at__php'] = strtotime($bh['hma']['occurred_at']);
    }

    $most_applicable_bh = null;
    $nonchanged_bhs = $invalid_bhs + $ban_history;

    foreach($nonchanged_bhs as $bh2) {
      if(!isset($bh2['hma']['occurred_at__php'])) {
        $bh2['hma']['occurred_at__php'] = strtotime($bh2['hma']['occurred_at']);
      }

      if($bh2['hma']['monitored_subreddit_id'] !== $bh['hma']['monitored_subreddit_id']) {
        continue;
      }

      if($bh2['hma']['occurred_at__php'] > $bh['hma']['occurred_at__php']) {
        continue;
      }

      if($most_applicable_bh !== null && $bh2['hma']['occurred_at__php'] < $most_applicable_bh['hma']['occurred_at__php']) {
        continue;
      }

      $most_applicable_bh = $bh2;
    }

    if($most_applicable_bh === null || in_array($most_applicable_bh, $invalid_bhs)) {
      $invalid_bhs[] = $bh;
    }else {
      $valid_bhs[] = $bh;
    }
  }

  // Fetching the unfiltered list of unban actions
  debug_echo('fetching unfiltered unban actions...');
  $sql = 'SELECT * FROM unban_histories WHERE unbanned_person_id=?';
  check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
  check_db_error($conn, $err_prefix, $stmt->bind_param('i', $person->id));
  check_db_error($conn, $err_prefix, $stmt->execute());
  check_db_error($conn, $err_prefix, $res = $stmt->get_result());

  $unban_history = array();

  while(($row = $res->fetch_assoc()) != null) {
    $unban_history[] = new ArrayObject(array( 'ubh' => $row ));
  }

  $res->close();
  $stmt->close();

  debug_echo('got ' . count($unban_history) . ' unban actions');

  // Fetching the corresponding handled_mod_action for the
  // unban histories
  debug_echo('finding corresponding hmas...');
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
  debug_echo('found all corresponding hmas');

  if($format === 1) {
    $is_prop_row = DatabaseHelper::fetch_one($conn, 'SELECT property_value FROM propagator_settings WHERE property_key=?',
      array(array('s', 'suppress_no_op')));
    if($is_prop_row !== null && $is_prop_row->property_value !== 'false') {
      // We have to fallback to the old method since we are repropagating
      $banned_tags = array();
      $missing_tags = array();
      foreach($hashtags as $tag) {
        if($tag !== 'all' && !in_array($tag, $missing_tags)) {
          $missing_tags[] = $tag;
        }
      }
      if($is_searching_all) {
        foreach($NON_MODERATOR_HASHTAGS as $tag) {
          if(!in_array($tag, $missing_tags)) {
            $missing_tags[] = $tag;
          }
        }
      }
      if(count($ban_history) > 0 && count($unban_history) <= 0) {
        foreach($ban_history as $ban) {
          for($missing_tags_index = count($missing_tags) - 1; $missing_tags_index >= 0; $missing_tags_index--) {
            $tag = $missing_tags[$missing_tags_index];
            if(strpos($ban['bh']['ban_description'], $tag) !== false) {
              $banned_tags[] = $tag;
              array_splice($missing_tags, $missing_tags_index, 1);
              break;
            }
          }
        }
        $banned_tags_pretty = null;
        if(count($banned_tags) === 0) {
          $banned_tags_pretty = 'no matching tags';
        }else {
          $banned_tags_pretty = implode(', ', $banned_tags);
        }
        echo_success(array('person' => $person->username, 'banned' => true, 'reason' => $banned_tags_pretty, 'fallback' => true));
        $conn->close();
        return;
      }
      $relevant_subreddits = array();
      $subreddit_to_latest_ban = array();
      $subreddit_to_latest_unban = array();
      foreach($ban_history as $bh) {
        $sub_id = $bh['hma']['monitored_subreddit_id'];
        if(!array_key_exists('occurred_at__php', $bh['hma'])) {
          $bh['hma']['occurred_at__php'] = strtotime($bh['hma']['occurred_at']);
        }
        if(!array_key_exists($sub_id, $relevant_subreddits)) {
          $relevant_subreddits[$sub_id] = true;
        }
        $newest_ban = $bh['hma']['occurred_at__php'];
        if(array_key_exists($sub_id, $subreddit_to_latest_ban)) {
          if($subreddit_to_latest_ban[$sub_id]['time'] > $newest_ban)
          continue;
        }
        $subreddit_to_latest_ban[$sub_id] = array('time' => $newest_ban, 'ban' => $bh);
      }
      foreach($unban_history as $ubh) {
        $sub_id = $ubh['hma']['monitored_subreddit_id'];
        if(!array_key_exists('occurred_at__php', $ubh['hma'])) {
          $ubh['hma']['occurred_at__php'] = strtotime($ubh['hma']['occurred_at']);
        }
        if(!array_key_exists($sub_id, $relevant_subreddits)) {
          $relevant_subreddits[$sub_id] = true;
        }
        $newest_unban = $ubh['hma']['occurred_at__php'];
        if(array_key_exists($sub_id, $subreddit_to_latest_unban)) {
          $newest_unban = max($newest_unban, $subreddit_to_latest_unban);
        }
        $subreddit_to_latest_unban[$sub_id] = $newest_unban;
      }
      $total_subreddits = count($relevant_subreddits);
      $banned_subreddits = 0;
      foreach($relevant_subreddits as $rel_sub_id=>$dummy) {
        if(!array_key_exists($rel_sub_id, $subreddit_to_latest_ban)) {
          continue;
        }
        $is_new_sub = false;
        if(!array_key_exists($rel_sub_id, $subreddit_to_latest_unban)) {
          $is_new_sub = true;
        }else {
          $ban_time = $subreddit_to_latest_ban[$rel_sub_id]['time'];
          $unban_time = $subreddit_to_latest_unban[$rel_sub_id];
          if($ban_time > $unban_time) {
            $is_new_sub = true;
          }
        }
        if($is_new_sub) {
          $ban = $subreddit_to_latest_ban[$rel_sub_id]['ban'];
          $banned_subreddits++;
          for($missing_tags_index = count($missing_tags) - 1; $missing_tags_index >= 0; $missing_tags_index--) {
            $tag = $missing_tags[$missing_tags_index];
            if(strpos($ban['bh']['ban_description'], $tag) !== false) {
              $banned_tags[] = $tag;
              array_splice($missing_tags, $missing_tags_index, 1);
              break;
            }
          }
        }
      }
      $banned_heuristic = $banned_subreddits > ($total_subreddits / 2.0);
      $banned_tags_pretty = null;
      if($banned_heuristic) {
        if(count($banned_tags) === 0) {
          $banned_tags_pretty = 'no matching tags';
        }else {
          $banned_tags_pretty = implode(', ', $banned_tags);
        }
      }
      echo_success(array('person' => $person->username, 'banned' => $banned_heuristic, 'reason' => $banned_tags_pretty, 'fallback' => true));
      $conn->close();
      return;
    }

    debug_echo('checking latest usl action against person...');
    $action = DatabaseHelper::fetch_one($conn, 'SELECT id FROM usl_actions WHERE is_latest = 1 AND person_id = ? LIMIT 1',
      array(array('i', $person->id)));
    if($action === null) {
      debug_echo('there is none; we are done');
      echo_success(array('person' => $person->username, 'banned' => false));
      $conn->close();
      return;
    }

    debug_echo('found an action; checking tags...');
    $tags = DatabaseHelper::fetch_all($conn, join('', array(
      'SELECT hashtags.tag as tag FROM usl_action_hashtags ',
      'JOIN hashtags ON usl_action_hashtags.hashtag_id = hashtags.id ',
      'WHERE usl_action_hashtags.usl_action_id = ?'
    )), array(array('i', $action->id)));
    debug_echo('found ' . count($tags) . ' tags..');

    $filtered_tags = array();
    foreach($tags as $tag) {
      $found = $is_searching_all || in_array($tag->tag, $hashtags);
      if($found) {
        debug_echo('found relevant tag: ' . $tag->tag);
        $filtered_tags[] = $tag->tag;
      }
    }

    if(count($filtered_tags) === 0) {
      debug_echo('no relevant tags');
      echo_success(array('person' => $person->username, 'banned' => false));
      $conn->close();
      return;
    }

    $banned_tags_pretty = implode(', ', $filtered_tags);
    echo_success(array('person' => $person->username, 'banned' => true, 'reason' => $banned_tags_pretty));
    $conn->close();
    return;
  }

  function create_combined($filt_bhs, $filt_ubhs) {
    $comb = array();

    foreach($filt_bhs as $bh) {
      $desc = $bh['bh']['ban_description'];
      if(!mb_check_encoding($desc, 'UTF-8')) {
        $desc = mb_convert_encoding($desc, 'UTF-8', 'UTF-8');
      }

      $comb[] = array(
        'kind' => 'ban',
        'subreddit' => $bh['sub']['subreddit'],
        'description' => $desc,
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
    global $err_prefix;
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
    global $err_prefix;
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
    debug_echo('fetch_subs_bh');
    fetch_subs_bh($conn, $ban_history);
    debug_echo('fetch_subs_ubh');
    fetch_subs_ubh($conn, $unban_history);
    $res = array('person' => $person->username, 'grandfathered' => false, 'history' => create_combined($ban_history, $unban_history));
    if ($debug === 1) {
      debug_echo('return ' . print_r($res, true));
      debug_echo('jsonified that is ' . json_encode($res));
      debug_echo('last json error: ' . json_last_error_msg());
      debug_echo('jsonify with unescaped unicode: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
      debug_echo('last json error: ' . json_last_error_msg());
    }
    echo_success($res);
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
  echo_success(array('person' => $person->username, 'grandfathered' => false, 'history' => create_combined($ban_history, $valid_ubhs)));
  $conn->close();
  return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
