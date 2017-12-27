<?php
include 'pagestart.php';


$err_prefix = "status.php";
check_db_error($conn, $err_prefix, $stmt = $conn->prepare("SELECT monsub.subreddit AS sub, smp.updated_at AS upd_at, smp.last_time_had_full_history as full_hist_at FROM monitored_subreddits monsub INNER JOIN subreddit_modqueue_progress smp ON monsub.id = smp.monitored_subreddit_id"));
//check_db_error($conn, $err_prefix, $stmt->bind_param("i", $id));
check_db_error($conn, $err_prefix, $stmt->execute());
check_db_error($conn, $err_prefix, $res = $stmt->get_result());

$subs = array();
while(($row = $res->fetch_assoc()) != null) {
  $subs[] = $row;
}

$res->close();
$stmt->close();
?>
<!doctype html>
<html lang="en">
  <head>
    <title>USL - Status</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
  </head>
  <body>
    <?php include 'navigation.php'; ?>
    <div class="pl-1 pr-1">
      <div class="container mt-5">
        <div class="container-fluid alert" id="statusText" style="display: none"></div>
	<h1>Modqueue Progress by Subreddit</h1>
	<p>If these are older than 1-2 hours then the bot is likely experiencing issues. The times are in US-East coast / New York time.</p>

	<ul>
	  <?php foreach($subs as $sub): ?>
	  <li><b><?= $sub['sub'] ?></b> - Last updated at <?= $sub['upd_at'] ?>, last full history at <?= $sub['full_hist_at'] ?></li>
	  <?php endforeach; ?>
	</ul>

	<h1>Current bot actions</h1>
	<ul id="bot-actions-ul">
	</ul>
      </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>

    <script type="text/javascript">
      $(function () {
	  $('[data-toggle="tooltip"]').tooltip();
      });

      var latest_action_id = 1;

      function fetch_actions(after, succ_callback, fail_callback) {
	after = after || 1;
	$.get('https://universalscammerlist.com/api/actionlog.php', { after: after }, function(data, stat) {
	  succ_callback(data.data, stat);
	}).fail(function(xhr) {
	  console.log(xhr);
	  fail_callback(xhr);
	});
      }

      // clears the UL of actions thats being displayed
      function empty_local_list() {
	$("#bot-actions-ul").empty();
      }

      function is_sigstart(action) {
	return action.action === '{SIGSTART}';
      }

      function clean_action(action, persons, subreddits) {
	var time = new Date(action.created_at);
	var time_str = $.timeago(time);
	return "<b class=\"w-25\">" + time_str + "</b> " + action.action;
      }

      function append_cleaned_action(cleaned_action) {
	var ul = $("#bot-actions-ul");
	var tmp = $("<li>");
	tmp.html(cleaned_action);
	ul.append(tmp);
      }

      $(function() {
	var me = null;
	me = setInterval(function() {
	  fetch_actions(latest_action_id, function(data, stat) {
	    var actions = data.actions;
	    for(var i = 0, len = actions.length; i < len; i++) {
	      var act = actions[i];
	      if(is_sigstart(act)) {
		empty_local_list();
	      }else {
		var cleaned = clean_action(act, data.persons, data.subreddits);
		append_cleaned_action(cleaned);
	      }
	    }
	  }, function(xhr) {
	    clearInterval(me);
	  });
	}, 1000);
      });
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>

