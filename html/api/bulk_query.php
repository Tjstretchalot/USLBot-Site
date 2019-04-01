<?php
/**
 * Performs a bulk query of users who are on the universal scammer list. Only available while the
 * bot is not propagating. This API endpoint was changed around 03/31/2019 thus you must pass
 * the version parameter to get the new behavior.
 *
 * This endpoint assumes you are querying for the common tags '#scammer', '#sketchy', and '#troll'.
 * You paginate this endpoint by checking the biggest id returned
 *
 * Arguments:
 *      - version (int, default 1): the version of this endpoint. Current behavior is '2'
 *      - start_id (int, default 0): the start id (exclusive). This takes the place of "offset" in
 *          the original endpoint. You will get at least "limit" results. For traditional scammers
 *          you should use the "bulk_traditional.php" endpoint.
 *      - limit (int, default 250, max 250, min 1): the number of results to return. You may
 *          get fewer results
 *
 * Returns:
 * {
 *   success: true,
 *   data: {
 *      bans: [ { ... }, { ... }, ... ],
 *      next_id: int
 *   }
 * }
 *
 * Where each ban item is of the form:
 *      { username: 'johndoe', ban_reason: '#scammer on /r/Gameswap', banned_at: <utc timestamp in ms> }
 *
 * When using this endpoint, PLEASE include your username in the user agent!
 */

require_once 'api/common.php';
require_once 'database/persons.php';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    // DEFAULT ARGUMENTS
    $version = 1;
    $start_id = 0;
    $limit = 250;

    if(isset($_GET['version']) && is_numeric($_GET['version'])) {
        $version = intval($_GET['version']);
    }

    if($version === 1) {
        include_once 'api/bulk_query_old.php';
        return;
    }

    if($version !== 2) {
        echo_fail(400, 'INVALID_ARGUMENT', 'version must be either 1 (old version) or 2 (current version)');
        return;
    }

    if(isset($_GET['start_id']) && is_numeric($_GET['start_id'])) {
        $start_id = intval($_GET['start_id']);
    }

    if(isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = intval($_GET['limit']);
    }

    // VALIDATING ARGUMENTS
    if($limit <= 0) {
        echo_fail(400, 'INVALID_ARGUMENT', 'limit must be positive');
        return;
    }

    // PERFORMING REQUEST
    require_once 'pagestart.php';
    require_once 'database/helper.php';

    $is_prop_row = DatabaseHelper::fetch_one($conn, 'SELECT property_value FROM propagator_settings WHERE property_key=?',
      array(array('s', 'suppress_no_op')));
    if($is_prop_row !== null && $is_prop_row->property_value !== 'false') {
        echo_fail(503, 'SERVICE_UNAVAILABLE', 'The bot is currently repropagating and this endpoint is not available until that concludes.');
        $conn->close();
        return;
    }

    $raw_hashtags = DatabaseHelper::fetch_all($conn, 'SELECT id FROM hashtags WHERE tag IN (?, ?, ?) LIMIT 3',
                        array(array('s', '#scammer'), array('s', '#sketchy'), array('s', '#troll')));

    $hashtag_params = array();
    foreach($raw_hashtags as $ht) {
        $hashtag_params[] = array('i', $ht->id);
    }

    $bot_ban_ids = DatabaseHelper::fetch_all($conn, 'SELECT id FROM persons WHERE username=\'USLBot\'', array());

    $blacklist_mods = array();
    foreach($bot_ban_ids as $bbid) {
        $blacklist_mods[] = array('i', $bbid->id);
    }

    $all_params = array();
    foreach($hashtag_params as $ht) { $all_params[] = $ht; }
    foreach($blacklist_mods as $bm) { $all_params[] = $bm; }
    $all_params[] = array('i', $start_id);
    $all_params[] = array('i', $limit);

    $raw_actions = DatabaseHelper::fetch_all($conn, <<<SQL
SELECT  max(usl_actions.id)+1 as big_id,
        persons.username as username,
        CONCAT(GROUP_CONCAT(DISTINCT hashtags.tag SEPARATOR ', '), ' from /r/', GROUP_CONCAT(monitored_subreddits.subreddit SEPARATOR ', /r/')) AS ban_reason,
        UNIX_TIMESTAMP(min(handled_modactions.occurred_at))*1000 as banned_at
FROM usl_actions
JOIN persons ON persons.id = usl_actions.person_id
JOIN usl_action_hashtags ON usl_action_hashtags.usl_action_id = usl_actions.id
JOIN usl_action_ban_history ON usl_action_ban_history.usl_action_id = usl_actions.id
JOIN ban_histories ON ban_histories.id = usl_action_ban_history.ban_history_id
JOIN handled_modactions ON handled_modactions.id = ban_histories.handled_modaction_id
JOIN monitored_subreddits ON monitored_subreddits.id = handled_modactions.monitored_subreddit_id
JOIN hashtags ON hashtags.id = usl_action_hashtags.hashtag_id
WHERE usl_action_hashtags.hashtag_id IN (?, ?, ?) AND ban_histories.mod_person_id NOT IN (?)
    AND usl_actions.id > ? AND usl_actions.is_latest = 1 AND usl_actions.is_ban = 1
    AND monitored_subreddits.read_only = 0
GROUP BY persons.id
LIMIT ?
SQL
    , $all_params);

    // We will find the biggest id and strip it
    $next_id = 0;
    $new_actions = array();
    foreach($raw_actions as $act) {
        $new_actions[] = array('username' => $act->username, 'ban_reason' => $act->ban_reason, 'banned_at' => $act->banned_at);
        if($act->big_id > $next_id) {
            $next_id = $act->big_id;
        }
    }

    echo_success(array('bans' => $new_actions, 'next_id' => $next_id));
    $conn->close();
    return;
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a GET request at this endpoint');
}
?>