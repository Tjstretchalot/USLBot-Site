<?php
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/site_sessions.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $subreddit = null;
  $hashtags = null; //seperated by commas
  $hashtags_arr = null;
  $silent = 1;
  $read_only = 0;
  $write_only = 0;

  /* PARSING ARGUMENTS */
  if(isset($_POST['subreddit'])) {
    $subreddit = $_POST['subreddit'];
  }

  if(isset($_POST['hashtags'])) {
    $hashtags = $_POST['hashtags'];
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

  if($silent !== 0 && $silent !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid value for silent; got ' . strval($silent) . ' expected 0 or 1');
    return;
  }

  if($write_only !== 0 && $write_only !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid value for write_only; got ' . strval($write_only) . ' expected 0 or 1');
    return;
  }

  if($read_only !== 0 && $read_only !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid value for ready_only; got ' . strval($read_only) . ' expected 0 or 1');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  include_once('pagestart.php');
  if($logged_in_person === null) {
    echo_fail(403, 'Authentication is required for this endpoint');
    $conn->close();
    return;
  }

  if($logged_in_person->auth_level < $MODERATOR_PERMISSION) {
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

  DatabaseHelper::execute($conn, 'INSERT INTO monitored_subreddits (subreddit, silent, read_only, write_only) VALUES (?, ?, ?, ?)', array(
    array('s', $subreddit),
    array('i', $silent),
    array('i', $read_only),
    array('i', $write_only)
  ));

  $sub_id = $conn->insert_id;

  foreach($hashtags_arr as $hashtag) {
    DatabaseHelper::execute($conn, 'INSERT INTO subscribed_hashtags (monitored_subreddit_id, hashtag, created_at, deleted_at) VALUES (?, ?, NOW(), NULL)', array(
      array('i', $sub_id),
      array('s', $hashtag)
    ));
  }

  $conn->close();
  echo_success(array('subreddit' => $subreddit, 'hashtags' => $hashtags_arr, 'silent' => $silent, 'read_only' => $read_only, 'write_only' => $write_only));
  return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
