<?php
require_once 'api/common.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  /* DEFAULT ARGUMENTS */
  $subreddit = null;
  $hashtag = null;

  /* PARSING ARGUMENTS */
  if(isset($_POST['subreddit'])) {
    $subreddit = $_POST['subreddit'];
  }

  if(isset($_POST['hashtag'])) {
    $hashtag = $_POST['hashtag'];
  }

  /* VALIDATING ARGUMENTS */
  if($subreddit === null) {
    echo_fail(400, 'ARGUMENT_INVALID', 'subreddit is a required parameter at this endpoint');
    return;
  }

  if($hashtag === null) {
    echo_fail(400, 'ARGUMENT_INVALID', 'hashtag is a required parameter at this endpoint');
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

  /* SECONDARY VALIDATION */
  $sub_row = DatabaseHelper::fetch_one($conn, 'SELECT id FROM monitored_subreddits WHERE subreddit = ?', array(array('s', $subreddit)));
  if($sub_row === null) {
    echo_fail(404, 'ARGUMENT_INVALID', 'The bot does not track /r/' . $subreddit);
    $conn->close();
    return;
  }

  $tag_row = DatabaseHelper::fetch_one($conn, 'SELECT id FROM hashtags WHERE tag = ?', array(array('s', $hashtag)));
  if($tag_row === null) {
    echo_fail(404, 'ARGUMENT_INVALID', "You must register the tag '$hashtag' before you can subscribe to it");
    $conn->close();
    return;
  }

  $existing_tag_row = DatabaseHelper::fetch_one($conn, 'SELECT 1 FROM subscribed_hashtags WHERE monitored_subreddit_id = ? AND hashtag_id = ? AND deleted_at IS NULL',
    array(array('i', $sub_row->id), array('i', $tag_row->id)));
  if($existing_tag_row !== null) {
    echo_fail(400, 'ARGUMENT_INVALID', "/r/$subreddit is already subscribed to the tag '$hashtag'");
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  DatabaseHelper::execute($conn, 'INSERT INTO subscribed_hashtags (monitored_subreddit_id, hashtag_id, created_at, deleted_at) VALUES (?, ?, NOW(), NULL)',
    array(array('i', $sub_row->id), array('i', $tag_row->id)));

  echo_success(array());
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
