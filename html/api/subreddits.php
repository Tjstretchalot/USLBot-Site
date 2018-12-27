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
  // This could be a join but the logic is more confusing
  $subreddits = null;
  if($subreddit !== null) {
    $row = DatabaseHelper::fetch_one($conn, 'SELECT id, subreddit, silent, read_only, write_only FROM monitored_subreddits WHERE subreddit=? LIMIT 1',
      array(array('s', $subreddit)));
    if($row === null) {
      echo_fail(404, 'The bot does not follow /r/' . $subreddit);
      $conn->close();
      return;
    }
    $subreddits = array($row);
  }else {
    $subreddits = DatabaseHelper::fetch_all($conn, 'SELECT id, subreddit, silent, read_only, write_only FROM monitored_subreddits', array());
  }

  $full_result = array();
  foreach($subreddits as $sub) {
    $row = array(
      'subreddit' => $sub->subreddit,
      'silent' => $sub->silent,
      'read_only' => $sub->read_only,
      'write_only' => $sub->write_only
    );

    $alt_modmails = DatabaseHelper::fetch_all($conn, 'SELECT subreddit FROM subreddit_alt_modmail WHERE monitored_subreddit_id=?', array(array('i', $sub->id)));
    $row['alt_modmails'] = $alt_modmails;

    $full_result[] = $row;
  }

  echo_success(array('subreddits' => $full_result));
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
