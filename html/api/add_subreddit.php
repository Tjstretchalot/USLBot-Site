<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/site_sessions.php';
require_once 'database/helper.php';

function pretty_tags_list($tags_arr) {
  $result = '';
  foreach($tags_arr as $ind=>$tag) {
    if($ind === 0) {
    }elseif($ind === 1 && count($missing_tags) === 2) {
      $result .= ' and ';
    }elseif($ind === (count($missing_tags) - 1)) {
      $result .= ', and ';
    }else {
      $result .= ', ';
    }

    $result .= '\'' . $tag . '\'';
  }
  return $result;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $subreddit = null;
  $hashtags = null; // separated by spaces
  $remap_modmail = null; // separated by spaces
  $hashtags_arr = null;
  $silent = 1;
  $read_only = 0;
  $write_only = 0;
  $suppress_repropagate = 0;

  /* PARSING ARGUMENTS */
  if(isset($_POST['subreddit'])) {
    $subreddit = $_POST['subreddit'];
  }

  if(isset($_POST['hashtags'])) {
    $hashtags = $_POST['hashtags'];
  }

  if(isset($_POST['remap_modmail'])) {
    $remap_modmail = $_POST['remap_modmail'];
  }

  if(isset($_POST['silent']) && is_numeric($_POST['silent'])) {
    $silent = intval($_POST['silent']);
  }

  if(isset($_POST['read_only']) && is_numeric($_POST['read_only'])) {
    $read_only = intval($_POST['read_only']);
  }

  if(isset($_POST['write_only']) && is_numeric($_POST['write_only'])) {
    $write_only = intval($_POST['write_only']);
  }

  if(isset($_POST['suppress_repropagate'])) {
    if(!is_numeric($_POST['suppress_repropagate'])) {
      $suppress_repropagate = -1;
    }else {
      $suppress_repropagate = intval($_POST['suppress_repropagate']);
    }
  }

  /* VALIDATING ARGUMENTS */
  if($subreddit === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter subreddit');
    return;
  }

  if(strlen($subreddit) < 3) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Subreddit is too short');
    return;
  }

  if(strpos($subreddit, ' ') !== False) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Subreddit contains spaces');
    return;
  }

  if(strpos($subreddit, '/') !== False) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Subreddit contains a slash');
    return;
  }

  if($hashtags === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter hashtags');
    return;
  }

  if(strlen($hashtags) < 3) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Hashtags is too short (3 chars at least per tag) (got ' . $hashtags . ')');
    return;
  }

  $hashtags_arr = explode(' ', $hashtags, 100);
  foreach($hashtags_arr as $hashtag) {
    if(strlen($hashtag) < 3) {
      echo_fail(400, 'ARGUMENT_INVALID', 'Hashtags contains too short hashtag (3 chars at least per tag) (got ' . $hashtag . ')');
      return;
    }
  }

  if($remap_modmail === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing remap_modmail. This should be a space-separated list of subreddits to redirect the modmail to. May be/include the primary subreddit to send them actual modmail');
    return;
  }

  if(strlen($remap_modmail) < 3) {
    echo_fail(400, 'ARGUMENT_INVALID', 'remap_modmail is too short (all subreddits are at least 3 characters) (got ' . $remap_modmail . ')');
    return;
  }

  $remap_modmail_arr = explode(' ', $remap_modmail, 100);
  foreach($remap_modmail_arr as $sub) {
    if(strlen($sub) < 3) {
      echo_fail(400, 'ARGUMENT_INVALID', 'remap_modmail contains too short subreddit: ' . $sub);
      return;
    }
    if(preg_match('/[^a-z_\-0-9]/i', $sub)) {
      echo_fail(400, 'ARGUMENT_INVALID', 'remap_modmail contains subreddit with illegal characters: ' . $sub);
      return;
    }
  }

  if($silent !== 0 && $silent !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid value for silent; got ' . strval($silent) . ' expected 0 or 1');
    return;
  }

  if($write_only !== 0 && $write_only !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid value for write_only; got ' . strval($write_only) . ' expected 0 or 1');
    return;
  }

  if($read_only !== 0 && $read_only !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid value for read_only; got ' . strval($read_only) . ' expected 0 or 1');
    return;
  }

  if($suppress_repropagate !== 0 && $suppress_repropagate !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid valid for suppress_repropagate; expected 0 or 1');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  include_once('pagestart.php');
  if($logged_in_person === null) {
    echo_fail(403, 'Authentication is required for this endpoint');
    $conn->close();
    return;
  }

  if($auth_level < $MODERATOR_PERMISSION) {
    echo_fail(403, 'Access denied, insufficient permission');
    $conn->close();
    return;
  }

  /* SECONDARY VALIDATION OF ARGUMENTS */
  $existing_sub = DatabaseHelper::fetch_one($conn, 'SELECT 1 FROM monitored_subreddits WHERE subreddit=?', array(array('s', $subreddit)));
  if($existing_sub !== null) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Already have a monitored subreddit by that name!');
    $conn->close();
    return;
  }

  $params = array();
  foreach($hashtags_arr as $hashtag) {
    $params[] = array('s', $hashtag);
  }
  $questions = join(',', array_fill(0, count($hashtags_arr), '?'));
  $hashtag_rows_arr = DatabaseHelper::fetch_all($conn, 'SELECT id, tag FROM hashtags WHERE tag IN (' . $questions . ')', $params);

  if(count($hashtag_rows_arr) !== count($hashtags_arr)) {
    $missing_tags = array();
    foreach($hashtags_arr as $hashtag) {
      $found = false;
      foreach($hashtag_rows_arr as $hashtag_row) {
        if($hashtag_row->tag === $hashtag) {
          $found = true;
          break;
        }
      }

      if(!$found) {
        $missing_tags[] = $hashtag;
      }
    }

    echo_fail(400, 'ARGUMENT_INVALID', 'Unknown hashtags: ' . pretty_tags_list($missing_tags));
    $conn->close();
    return;
  }

  $row = DatabaseHelper::fetch_one($conn, 'SELECT property_value FROM propagator_settings WHERE property_key=\'suppress_no_op\'', array());
  if($row !== null && $row->property_value !== 'false') {
    echo_fail(400, 'The bot is already configured to re-evaluate reddit-to-meaning. Please wait until the current operation completes.');
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  DatabaseHelper::execute($conn, 'INSERT INTO monitored_subreddits (subreddit, silent, read_only, write_only) VALUES (?, ?, ?, ?)', array(
    array('s', $subreddit),
    array('i', $silent),
    array('i', $read_only),
    array('i', $write_only)
  ));

  $sub_id = $conn->insert_id;

  foreach($hashtag_rows_arr as $hashtag_row) {
    DatabaseHelper::execute($conn, 'INSERT INTO subscribed_hashtags (monitored_subreddit_id, hashtag_id, created_at, deleted_at) VALUES (?, ?, NOW(), NULL)', array(
      array('i', $sub_id),
      array('i', $hashtag_row->id)
    ));
  }

  foreach($remap_modmail_arr as $sub) {
    DatabaseHelper::execute($conn, 'INSERT INTO subreddit_alt_modmail (monitored_subreddit_id, subreddit) VALUES (?, ?)', array(
      array('i', $sub_id),
      array('s', $sub)
    ));
  }

  if($suppress_repropagate !== 1) {
    $reason = sprintf('Add monitored subreddit /r/%s: silent=%s, read_only=%s, write_only=%s, hashtags=[%s], remap_modmail=[%s]',
      $subreddit, strval($silent), strval($read_only), strval($write_only), pretty_tags_list($hashtags_arr), pretty_tags_list($remap_modmail_arr));

    DatabaseHelper::execute($conn, 'INSERT INTO repropagation_requests (requester_person_id, reason, approved, received_at, handled_at) VALUES (?, ?, 0, NOW(), NULL)',
      array(
        array('i', $logged_in_person->id),
        array('s', $reason)
      ));
  }

  $conn->close();
  echo_success(array('subreddit' => $subreddit, 'hashtags' => $hashtags_arr, $remap_modmail => $remap_modmail_arr,
                     'silent' => $silent, 'read_only' => $read_only, 'write_only' => $write_only));
  return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
