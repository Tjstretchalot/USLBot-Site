<?php
require_once 'api/common.php';
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
  $silent = -1;
  $read_only = -1;
  $write_only = -1;
  $remap_modmail = null;
  $suppress_repropagate = 0;

  /* PARSING ARGUMENTS */
  if(isset($_POST['subreddit'])) {
    $subreddit = $_POST['subreddit'];
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

  if(isset($_POST['remap_modmail'])) {
    $remap_modmail = $_POST['remap_modmail'];
  }

  if(isset($_POST['suppress_repropagate']) && is_numeric($_POST['suppress_repropagate'])) {
    $suppress_repropagate = intval($_POST['suppress_repropagate']);
  }

  /* VALIDATING ARGUMENTS */
  if($subreddit === null) {
    echo_fail(400, 'ARGUMENT_MISSING', 'subreddit is a required argument for this endpoint');
    return;
  }

  if($silent !== -1 && $silent !== 0 && $silent !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'invalid value for silent, expected -1 (keep the same), 0, or 1 but got ' . strval($silent));
    return;
  }

  if($read_only !== -1 && $read_only !== 0 && $read_only !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'invalid value for read_only, expected -1 (keep the same), 0, or 1 but got ' . strval($read_only));
    return;
  }

  if($write_only !== -1 && $write_only !== 0 && $write_only !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'invalid value for write_only, expected -1 (keep the same), 0, or 1 but got ' . strval($write_only));
    return;
  }

  $remap_modmail_arr = null;
  if($remap_modmail !== null) {
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
  }

  if($silent === -1 && $read_only === -1 && $write_only === -1 && $remap_modmail_arr === null) {
    echo_fail(400, 'ARGUMENT_INVALID', 'This request makes no changes!');
    return;
  }

  if($suppress_repropagate !== 0 && $suppress_repropagate !== 1) {
    echo_fail(400, 'ARGUMENT_INVALID', 'invalid value for suppress_repropagate, expected 0 or 1 but got ' . strval($suppress_repropagate));
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
  /* SECONDARY VALIDATING ARGUMENTS */
  $old_subreddit = DatabaseHelper::fetch_one($conn, 'SELECT id, subreddit, silent, read_only, write_only FROM monitored_subreddits WHERE subreddit=? LIMIT 1',
    array(array('s', $subreddit)));

  if($old_subreddit === null) {
    echo_fail(404, 'Not currently tracking /r/' . $subreddit . ', perhaps you want add_subreddit?');
    $conn->close();
    return;
  }

  $reprop_required = ($read_only !== -1 && $read_only !== $old_subreddit->read_only)
                  || ($write_only !== -1 && $write_only !== $old_subreddit->write_only);

  if($silent === -1) { $silent = $old_subreddit->silent; }
  if($read_only === -1) { $read_only = $old_subreddit->read_only; }
  if($write_only === -1) { $write_only = $old_subreddit->write_only; }

  $old_remap_modmail = DatabaseHelper::fetch_all($conn, 'SELECT subreddit FROM subreddit_alt_modmail WHERE monitored_subreddit_id=?', array(array('i', $old_subreddit->id)));

  $remap_subreddits_to_add = array();
  $remap_subreddits_to_remove = array();

  if($remap_modmail_arr !== null) {
    foreach($old_remap_modmail as $old_mm) {
      $found = false;
      foreach($remap_modmail_arr as $new_mm) {
        if($old_mm->subreddit === $new_mm) {
          $found = true;
          break;
        }
      }
      if(!$found) {
        $remap_subreddits_to_remove[] = $old_mm->subreddit;
      }
    }
    foreach($remap_modmail_arr as $new_mm) {
      $found = false;
      foreach($old_remap_modmail as $old_mm) {
        if($old_mm->subreddit === $new_mm) {
          $found = true;
          break;
        }
      }

      if(!$found) {
        $remap_subreddits_to_add[] = $new_mm;
      }
    }
  }

  if($silent === $old_subreddit->silent && $read_only === $old_subreddit->read_only && $write_only === $old_subreddit->write_only
      && count($remap_subreddits_to_add) === 0 && count($remap_subreddits_to_remove) === 0) {
    echo_fail(400, 'ARGUMENT_INVALID', 'The requested changes already match the existing values!');
    $conn->close();
    return;
  }

  if($reprop_required) {
    $row = DatabaseHelper::fetch_one($conn, 'SELECT property_value FROM propagator_settings WHERE property_key=\'suppress_no_op\'', array());
    if($row !== null && $row->property_value !== 'false') {
      echo_fail(400, 'The bot is already configured to re-evaluate reddit-to-meaning. Please wait until the current operation completes.');
      $conn->close();
      return;
    }
  }

  /* PERFORMING REQUEST */
  DatabaseHelper::execute($conn, 'UPDATE monitored_subreddits SET silent=?, read_only=?, write_only=? WHERE id=?',
    array(array('i', $silent), array('i', $read_only), array('i', $write_only), array('i', $old_subreddit->id)));

  foreach($remap_subreddits_to_add as $to_add) {
    DatabaseHelper::execute($conn, 'INSERT INTO subreddit_alt_modmail (monitored_subreddit_id, subreddit) VALUES (?, ?)',
      array(array('i', $old_subreddit->id), array('s', $to_add)));
  }

  foreach($remap_subreddits_to_remove as $to_rem) {
    DatabaseHelper::execute($conn, 'DELETE FROM subreddit_alt_modmail WHERE monitored_subreddit_id=? AND subreddit=?',
      array(array('i', $old_subreddit->id), array('s', $to_rem)));
  }

  if($reprop_required && $suppress_repropagate !== 1) {
    $reason = sprintf('Edit monitored subreddit /r/%s: silent=%s -> %s, read_only=%s -> %s, write_only=%s -> %s, remap_subreddits_to_add=[%s], remap_subreddits_to_remove=[%s]',
      $subreddit,
      strval($old_subreddit->$silent), strval($silent),
      strval($old_subreddit->read_only), strval($read_only),
      strval($old_subreddit->write_only), strval($write_only),
      pretty_tags_list($remap_subreddits_to_add), pretty_tags_list($remap_subreddits_to_remove));

    DatabaseHelper::execute($conn, 'INSERT INTO repropagation_requests (requester_person_id, reason, approved, received_at, handled_at) VALUES (?, ?, 0, NOW(), NULL)',
      array(
        array('i', $logged_in_person->id),
        array('s', $reason)
      ));
  }

  echo_success(array());
  $conn->close();
}else {
  echo_fail(405, 'METHOD_NOT_ALLOWED', 'You must use a POST request at this endpoint');
}
?>
