<?php
include 'pagestart.php';
include 'api/common.php';

if($auth_level < $MODERATOR_PERMISSION) {
  http_response_code(403);
  header('Location: https://universalscammerlist.com/403.php');
  $conn->close();
  return;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <title>USL - Search</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <link rel="stylesheet" href="css/footable.standalone.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  </head>
  <body>
    <?php include 'navigation.php'; ?>
    <div class="container mt-5">
      <h1>Manage Subreddit</h1>

      <p>This page can be used to manage the subreddits that the bot follows. **Be careful** and
      read the description for each section; some of these will take days to undo and cause the
      bot to be unresponsive for several hours.</p>

      <h2>View Subreddit List</h2>
      <p>This section allows you to view all the subreddits and their current settings. It only
      views the existing data and does not manipulate it in any way. It is always safe to use and
      requires minimal processing power. The list and basic settings is fetched and cached on page
      load, so you must refresh if you suspect it changed.</p>

      <div class="container-fluid alert" id="view-subreddits-select-status-text" style="display: none"></div>
      <form id="view-subreddits-select-form">
        <div class="form-group row">
          <select id="view-subreddits-select-select">
          </select>
        </div>
      </form>

      <h2>View/Edit/Add Tag Information</h2>
      <p>This section allows you to view, edit, or add to the list of tags which subreddits may
      subscribe to. This section does not affect the actual bot operations, but may be helpful for
      coordinating the use of tags amongst subreddits. It is safe to use and requires minimal
      processing power.</p>

      <h2>Edit Subreddit Settings</h2>
      <div class="card bg-warning mb-3">
        <div class="card-header">Retroactive Action</div>
        <div class="card-body">
          <h5 class="card-title">Requires re-evaluation</h5>
          <p class="card-text">This section performs an action which must be applied retroactively
          in order to be stable but does not do that automatically. Once you are satisfied with your
          changes, you must scroll to the bottom of the page and start the bot re-evaluating the
          existing bans under the new changes. If you completely undo your changes, you do not need
          to re-evaluate.</p>
        </div>
      </div>

      <h2>Subscribe To Tags</h2>
      <div class="card bg-warning mb-3">
        <div class="card-header">Retroactive Action</div>
        <div class="card-body">
          <h5 class="card-title">Requires re-evaluation</h5>
          <p class="card-text">This section performs an action which must be applied retroactively
          in order to be stable but does not do that automatically. Once you are satisfied with your
          changes, you must scroll to the bottom of the page and start the bot re-evaluating the
          existing bans under the new changes. If you completely undo your changes, you do not need
          to re-evaluate.</p>
        </div>
      </div>

      <h2>Unsubscribe From Tags</h2>
      <div class="card bg-warning mb-3">
        <div class="card-header">Retroactive Action</div>
        <div class="card-body">
          <h5 class="card-title">Requires re-evaluation</h5>
          <p class="card-text">This section performs an action which must be applied retroactively
          in order to be stable but does not do that automatically. Once you are satisfied with your
          changes, you must scroll to the bottom of the page and start the bot re-evaluating the
          existing bans under the new changes. If you completely undo your changes, you do not need
          to re-evaluate.</p>
        </div>
      </div>

      <h2>Request Re-evaluate Reddit-to-Meaning</h2>
      <div class="card bg-danger text-white mb-3">
        <div class="card-header">Long-Duration, Irreversible</div>
        <div class="card-body">
          <h5 class="card-title">Irreversible long-duration process</h5>
          <p class="card-text">This section begins a process which takes several hours to complete.
          This modifies a large segment of the database and forces several backup operations to a
          remove server at key points in the process, but outside of **manually** reverting to those
          backups this operation is irreversible.</p>
        </div>
      </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
    <script src="js/status_text_utils.js"></script>

    <script type="text/javascript">
      var subreddits = null;

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();

        var sub_selects = $("#view-subreddits-select-select");
        $.get('https://universalscammerlist.com/api/subreddits.php', {}, function(data, stat) {
          subreddits = data.data.subreddits;
      	  for(var i = 0; i < subreddits.length; i++) {
            sub_selects.append($('<option>', { value: i, text: subreddits[i].subreddit }));
          }
      	}).fail(function(xhr) {
      	  set_status_text_from_xhr($("#view-subreddits-select-status-text"), xhr);
      	});
      });

      /*
      $("form").on('submit', function(e) {
	       e.preventDefault();
      });
      */
    </script>
  </body>
</html>
