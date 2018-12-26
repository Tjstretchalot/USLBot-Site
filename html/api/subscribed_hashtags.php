<?php
require_once 'api/common.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  /* DEFAULT ARGUMENTS */
  $subreddit = null;

  /* PARSING ARGUMENTS */
  if(isset($_GET['subreddit'])) {
    $subreddit = $_GET['subreddit'];
  }

  /* VALIDATING ARGUMENTS */
  if($subreddit === null) {
      echo_fail(400, 'ARGUMENT_INVALID', 'subreddit is required for this endpoint');
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

  /* PERFORMING REQUEST */
  $result = DatabaseHelper::fetch_all($conn, join(' \n', array(
    'SELECT hashtags.tag as tag, unix_timestamp(subscribed_hashtags.created_at) * 1000 as created_at FROM subscribed_hashtags ',
    'INNER JOIN monitored_subreddits ON monitored_subreddits.id = subscribed_hashtags.monitored_subreddit_id ',
    'INNER JOIN hashtags ON hashtags.id = subscribed_hashtags.hashtag_id ',
    'WHERE (subscribed_hashtags.deleted_at IS NULL) AND (monitored_subreddits.subreddit = ?)'
  )), array(array('s', $subreddit)));

  echo_success(array('hashtags' => $result));
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
