<?php
/**
  Returns a json array that contains one of two types of element, wrapped like so:

  { 
    success: true,
    data: [ { ... }, { ... }, ... ]
  {
  
  New-style ban: { username: 'johndoe', ban_reason: '#scammer on /r/Gameswap', banned_at: <utc timestamp in ms> }
  Old-style ban: { username: 'johndoe', traditional: true, ban_reason: 'grandfathered' }

  This is meant to be queried for other apis. It has one optional parameter, 'since', which only returns bans
  which occurred after a specified date. You can use the since parameter to update internal databases. Because
  dates are based of reddit times, it may be a good idea to leave some wiggle room in the updates. For example,
  if you want to update your database every day, fetch the history from up to two days ago and merge it in. The
  usernames are unique.


  When using this endpoint, PLEASE include your username in the user agent!
*/

require_once 'api/common.php';
require_once 'database/persons.php';
require_once 'database/register_account_requests.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  /* CONSTANTS */
  $err_prefix = 'bulk_query.php';

  // The hashtags that non-moderators are allowed to search for
  $NON_MODERATOR_HASHTAGS = array( '#sketchy', '#scammer', '#troll' );

  /* DEFAULT ARGUMENTS */
  $since = null; 

  /* PARSING ARGUMENTS */
  if(isset($_GET['since']) && is_numeric($_GET['since'])) {
    $since = intval($_GET['since']);
  }

  if($since !== null && $since < 0) {
    echo_fail(400, 'ARGUMENT_INVALID', 'Invalid \'since\' argument (must be nonnegative). Simply do not specify this parameter for the full history');
    return;
  }

  $result = array();

  require_once('pagestart.php');
  
  // If they don't specify a since, we start off with all the traditional scammers.
  if($since === null) {
    $sql = 'SELECT ps.username, ts.reason FROM traditional_scammers ts INNER JOIN persons ps ON ts.person_id = ps.id'; 
    check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
    check_db_error($conn, $err_prefix, $stmt->execute());
    check_db_error($conn, $err_prefix, $res = $stmt->get_result());
    
    while($row = $res->fetch_array()) {
      $result[] = array( 'username' => $row[0], 'traditional' => true, 'ban_reason' => $row[1] );
    }
    
    $res->close();
    $stmt->close();
  }

  // Now we loop through all the things

  // Building the sql command
  $sql =  'select ps.username, MIN(bh.ban_description), MIN(hma.occurred_at) from ban_histories bh ';
  $sql .=     'inner join persons ps on bh.banned_person_id = ps.id ';
  $sql .=     'inner join handled_modactions hma on bh.handled_modaction_id = hma.id ';
  $sql .=     'where ';
  $sql .=         'bh.banned_person_id not in (';
  $sql .=             'select ubh.unbanned_person_id from unban_histories ubh ';
  $sql .=                 'inner join handled_modactions uhma on ubh.handled_modaction_id = uhma.id ';
  $sql .=                 'where ';
  $sql .=                     'ubh.unbanned_person_id = bh.banned_person_id and ';
  $sql .=                     'uhma.occurred_at > hma.occurred_at) and ';
  $sql .=         'length(bh.ban_description) = (';
  $sql .=             'select max(length(bh2.ban_description)) ';
  $sql .=             'from ban_histories bh2 ';
  $sql .=             'where bh2.banned_person_id = bh.banned_person_id ';
  $sql .=         ') and ';
  $sql .=         '(bh.ban_description like \'%#sketchy%\' or bh.ban_description like \'%#scammer%\' or bh.ban_description like \'%#troll%\') and ';
  $sql .=         '(? is NULL or hma.occurred_at > ?) ';
  $sql .=     'group by bh.banned_person_id ';

  // Running the sql command
  check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
  if($since !== null) {
    $asstr = date('Y-m-d H:i:s', $since / 1000);
    check_db_error($conn, $err_prefix, $stmt->bind_param('ss', $asstr, $asstr));
  }else {
    check_db_error($conn, $err_prefix, $stmt->bind_param('ss', $since, $since));
  }
  check_db_error($conn, $err_prefix, $stmt->execute());
  check_db_error($conn, $err_prefix, $res = $stmt->get_result());

  // Pushing the results to memory
  while($row = $res->fetch_array()) {
    $result[] = array( 'username' => $row[0], 'ban_reason' => $row[1], 'banned_at' => strtotime($row[2]) * 1000 ); 
  }
  $res->close();
  $stmt->close();

  echo_success($result);
  $conn->close();
  return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>
