<?php
/*
 * One-stop shop to fetch all traditional scammers.
 * Result: { success: true, data: [ { username: 'johndoe', traditional: true, ban_reason: 'grandfathered' }, ... ] }
 *
 *
 * When using this endpoint, PLEASE include your username in the user agent!
 */

require_once 'api/common.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $err_prefix = 'bulk_traditional.php';

    require_once('pagestart.php');

    $sql = 'SELECT ps.username, ts.reason FROM traditional_scammers ts INNER JOIN persons ps ON ts.person_id = ps.id';
    check_db_error($conn, $err_prefix, $stmt = $conn->prepare($sql));
    check_db_error($conn, $err_prefix, $stmt->execute());
    check_db_error($conn, $err_prefix, $res = $stmt->get_result());

    while($row = $res->fetch_array()) {
      $result[] = array( 'username' => $row[0], 'traditional' => true, 'ban_reason' => $row[1] );
    }

    $res->close();
    $stmt->close();
    echo_success($result);
    $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>