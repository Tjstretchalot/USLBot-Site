<?php
/**
* API Endpoint for determining if a user is currently banned on a particular subreddit. This is
* accessible for moderators and individuals who have a specific release in the sub_pers_banned_release
* table. This endpoint is mostly used for other bots to piggyback off the banlist information stored
* here, so the sub_pers_banned_release table is mostly for bots.
*
* This is a simple yes/no query with LIKE searching capabilities.
*
* Example negative response, where we did manage to find a username that matched the query
* { success: true, data: { username: 'john', banned: false, found: true } }
*
* Example negative response, where we are not sure who they are talking about (this means not
* banned for string literal searches)
* { success: true, data: { username: 'john', banned: false, found: false } }
*
* Example positive responses:
* { success: true, data: { username: 'john', banned: true, found: true, details: 'permanent', occurred_at: 1549129199000 }}
* { success: true, data: { username: 'john', banned: true, found: true, details: '30 days', occurred_at: 1549129199000 }}
*/
require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/helper.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  /* DEFAULT ARGUMENTS */
  $subreddit = null;
  $username = null;

  /* PARSING ARGUMENTS */
  if(isset($_GET['subreddit'])) {
    $subreddit = $_GET['subreddit'];
  }

  if(isset($_GET['username'])) {
    $username = $_GET['username'];
  }

  /* VALIDATING ARGUMENTS */
  if($username === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter username');
    return;
  }

  if(strlen($username) < 1) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter username! (too short)');
    return;
  }

  if($subreddit === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'Missing or invalid parameter subreddit');
    return;
  }

  /* VALIDATING AUTHORIZATION */
  include_once('pagestart.php');
  if($logged_in_person === null) {
    echo_fail(403, 'FORBIDDEN', 'Authentication is required for this endpoint');
    $conn->close();
    return;
  }

  $monsub = DatabaseHelper::fetch_one($conn, 'SELECT id FROM monitored_subreddits WHERE subreddit=? LIMIT 1', array(array('s', $subreddit)));
  if($auth_level < $MODERATOR_PERMISSION) {
    if($monsub === null) {
      echo_fail(403, 'INSUFFICIENT PERMISSIONS', 'You provided authentication, but your account has insufficient permission to perform the specified command');
      $conn->close();
      return;
    }

    $release = DatabaseHelper::fetch_one($conn, 'SELECT 1 FROM sub_pers_banned_release WHERE monitored_subreddit_id=? AND person_id=? LIMIT 1',
      array(array('i', $monsub->id), array('i', $logged_in_person->id)));

    if($release === null) {
        echo_fail(403, 'INSUFFICIENT PERMISSIONS', 'You provided authentication, but your account has insufficient permission to perform the specified command');
        $conn->close();
        return;
    }
  }

  /* SECONDARY VALIDATING ARGUMENTS */
  if($monsub === null) {
    echo_fail(400, 'ARGUMENT_INVALID', 'There is no monitored subreddit by that name!');
    $conn->close();
    return;
  }

  $person = DatabaseHelper::fetch_one($conn, 'SELECT id, username FROM persons WHERE username LIKE ? LIMIT 1', array(array('s', $username)));
  if($person === null) {
    echo_success(array('username' => str_replace('%', '', $username), 'banned' => false, 'found' => false));
    $conn->close();
    return;
  }

  /* PERFORMING REQUEST */
  $q = <<<'SQL'
SELECT ban_histories.ban_details, UNIX_TIMESTAMP(handled_modactions.occurred_at)*1000 AS occurred_at FROM ban_histories
INNER JOIN handled_modactions ON handled_modactions.id = ban_histories.handled_modaction_id
WHERE ban_histories.banned_person_id = ? AND handled_modactions.monitored_subreddit_id = ?
ORDER BY handled_modactions.occurred_at DESC
LIMIT 1
SQL;
  $latest_ban = DatabaseHelper::fetch_one($conn, $q, array(array('i', $person->id), array('i', $monsub->id)));

  if($latest_ban === null) {
    echo_success(array('username' => $person->username, 'banned' => false, 'found' => true));
    $conn->close();
    return;
  }

  $q = <<<'SQL'
SELECT 1 FROM unban_histories
INNER JOIN handled_modactions ON handled_modactions.id = unban_histories.handled_modaction_id
WHERE handled_modactions.occurred_at >= FROM_UNIXTIME(? / 1000)
  AND handled_modactions.monitored_subreddit_id = ?
  AND unban_histories.unbanned_person_id = ?
SQL;
  $newer_unban = DatabaseHelper::fetch_one($conn, $q,
    array(array('i', $latest_ban->occurred_at), array('i', $monsub->id), array('i', $person->id)));
  if($newer_unban !== null) {
    echo_success(array('username' => $person->username, 'banned' => false, 'found' => true));
  }else {
    if($latest_ban->ban_details !== "permanent" && $latest_ban->ban_details !== "changed to permanent") {
      // One of two formats: XX days or changed to XX days
      if(preg_match('/(^|\W)(\d+) days/', $latest_ban->ban_details, $matches) === 1) {
        $ban_duration_days = intval($matches[2]);
        $ban_duration_seconds = 86400 * $ban_duration_days;
        $ban_finished_at = $latest_ban->occurred_at + $ban_duration_seconds;
        if($ban_finished_at < time() * 1000) {
          echo_success(array('username' => $person->username, 'banned' => false, 'found' => true));
          $conn->close();
          return;
        }
      }
    }
    echo_success(array(
      'username' => $person->username,
      'banned' => true,
      'found' => true,
      'details' => $latest_ban->ban_details,
      'occurred_at' => $latest_ban->occurred_at));
  }
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
