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
    <title>USL - Manage Subreddit</title>

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
      <form id="view-subreddits-select-form" class="mb-3">
        <select id="view-subreddits-select-select">
        </select>
      </form>

      <div class="card bg-light mb-3" id="view-subreddit-result-card" style="display: none;">
        <div class="card-header" id="view-subreddit-result-header">Subreddit</div>
        <div class="card-body">
          <h3>Basic Settings</h3>
          <table id="view-subreddit-result-config" class="table">
            <thead>
              <th>Silent</th>
              <th>Read-Only</th>
              <th>Write-Only</th>
            </thead>
            <tbody id="view-subreddit-result-config-body">
            </tbody>
          </table>

          <h3>Modmail Redirections</h3>
          <p>As a reminder, if the original subreddit is in this list, then the bot sends to their
          modmail instead of posting on the subreddit. If the list is empty, the bot sends to their
          modmail. In all other cases, the bot just posts on the listed subreddits to notify them.
          </p>

          <table id="view-subreddit-result-modmail" class="table">
            <thead>
              <th>Subreddit</th>
            </thead>
            <tbody id="view-subreddit-result-modmail-body">
            </tbody>
          </table>

          <h3>Subscribed Tags</h3>
          <table id="view-subreddit-result-tags" class="table">
            <thead>
              <th>Tag</th>
              <th>Since</th>
            </thead>
            <tbody id="view-subreddit-result-tags-body">
            </tbody>
          </table>
        </div>
      </div>

      <h2>View/Edit/Add Tag Information</h2>
      <p>This section allows you to view, edit, or add to the list of tags which subreddits may
      subscribe to. This section does not affect the actual bot operations, but may be helpful for
      coordinating the use of tags amongst subreddits. It is safe to use and requires minimal
      processing power.</p>

      <div class="container-fluid alert" id="view-tags-select-status-text" style="display: none"></div>
      <form id="view-tags-select-form" class="mb-3">
        <button type="button" class="btn btn-primary" id="view-tags-fetch-btn">Fetch Tags</button>

        <select id="view-tags-select-select">
        </select>
      </form>

      <div class="card bg-light mb-3" id="view-tag-result-card" style="display: none">
        <div class="card-header" id="view-tag-result-header">Subreddit</div>
        <div class="card-body">
          <div id="view-tag-result-description"></div>

          <form id="view-tag-edit-form" class="mt-3 mb-3">
            <div class="form-group row">
        	    <textarea class="form-control" id="tag-description-markdown" rows=10 aria-label="Markdown description" placeholder="Markdown description"></textarea>
        	  </div>
            <div class="form-group row">
              <button type="button" class="btn btn-primary mr-3" id="tag-edit-description-btn">Update Description</button>
              <button type="button" class="btn btn-secondary" id="tag-preview-description-btn">Preview</button>
            </div>
          </form>
        </div>
      </div>

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
    <script src="js/moment.js"></script>
    <script src="js/moment-timezone.js"></script>
    <script src="js/footable.min.js"></script>

    <script type="text/javascript">
      var subreddits = null;
      var cached_tags = null;

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


      $("#view-subreddits-select-select").change(function() {
	       if(subreddits === null) { return; }

         var ind = parseInt(this.value);
         var sub = subreddits[ind];

         var st_div = $('#view-subreddits-select-status-text');
         var card = $('#view-subreddit-result-card');
         var header = $('#view-subreddit-result-header');
         var config_tbl = $('#view-subreddit-result-config');
         var config = $('#view-subreddit-result-config-body');
         var alt_modmail_tbl = $('#view-subreddit-result-modmail');
         var alt_modmail = $('#view-subreddit-result-modmail-body');
         var tags_tbl = $('#view-subreddit-result-tags');
         var tags = $('#view-subreddit-result-tags-body');

         var card_fadeout_prom = null;
         if(card.is(":hidden")) {
           card_fadeout_prom = new Promise(function(resolve, reject) { resolve(); })
         }else {
           card_fadeout_prom = new Promise(function(resolve, reject) { card.fadeOut('slow', function() { resolve() })});
         }

         $.get('https://universalscammerlist.com/api/subscribed_hashtags.php', { subreddit: sub.subreddit }, function(data, stat) {
           var subs_tags = data.data.hashtags;

           card_fadeout_prom.then(function() {
             header.text(sub.subreddit);
             config.html(`<tr><td>${sub.silent === 1 ? 'Yes' : 'No'}</td><td>${sub.read_only === 1 ? 'Yes' : 'No'}</td><td>${sub.write_only === 1 ? 'Yes' : 'No'}</td></tr>`);
             config_tbl.footable();

             var am_html = "";
             for(var i = 0; i < sub.alt_modmails.length; i++) {
               am_html += `<tr><td>${sub.alt_modmails[i].subreddit}</td></tr>`;
             }
             alt_modmail.html(am_html);
             alt_modmail_tbl.footable();

             var tg_html = "";
             for(var i = 0; i < subs_tags.length; i++) {
               tg_html += `<tr><td>${subs_tags[i].tag}</td><td>${moment.unix(subs_tags[i].created_at / 1000).calendar()}</td></tr>`;
             }
             tags.html(tg_html);
             tags_tbl.footable();

             card.fadeIn('fast');
           });
         }).fail(function(xhr) {
       	   set_status_text_from_xhr($("#view-subreddits-select-status-text"), xhr);
         });
      });

      $("#view-tags-fetch-btn").click(function(e) {
        e.preventDefault();

        var st_div = $("#view-tags-select-status-text");
        var tag_selects = $("#view-tags-select-select");
        $.get('https://universalscammerlist.com/api/hashtags.php', {}, function(data, stat) {
          tag_selects.empty();

          cached_tags = data.data.hashtags;
          for(var i = 0; i < cached_tags.length; i++) {
            tag_selects.append($("<option>", { value: i, text: cached_tags[i].tag }));
          }
        }).fail(function(xhr) {
      	  set_status_text_from_xhr(st_div, xhr);
      	});
      });

      function refresh_tag(ind) {
        var st_div = $("#view-tags-select-status-text");
        var card = $("#view-tag-result-card");
        var header = $("#view-tag-result-header");
        var desc = $("#view-tag-result-description");
        var edit_desc = $("#tag-description-markdown");

        var tag = cached_tags[ind];

        var card_fadeout_prom = null;
        if(card.is(":hidden")) {
          card_fadeout_prom = new Promise(function(resolve, reject) { resolve(); })
        }else {
          card_fadeout_prom = new Promise(function(resolve, reject) { card.fadeOut('slow', function() { resolve() })});
        }

        return new Promise(function(resolve, reject) {
          $.post('https://universalscammerlist.com/api/markdown.php', { markdown: tag.description }, function(data, stat) {
            var html = data.data.html;

            card_fadeout_prom.then(function() {
              header.text(tag.tag);
              desc.html(html);
              edit_desc.text(tag.description);
              card.fadeIn('fast');
              resolve();
            });
          }).fail(function(xhr) {
            set_status_text_from_xhr(st_div, xhr);
            reject();
          });
        });
      }

      $("#view-tags-select-select").change(function() {
        if(cached_tags === null) { return; }
        var ind = parseInt(this.value);
        refresh_tag(ind);
      });

      $("#tag-edit-description-btn").click(function(e) {
        e.preventDefault();

        var ind = parseInt($("#view-tags-select-select").val());
        var tag = cached_tags[ind];

        var st_div = $("#view-tags-select-status-text");
        var card = $("#view-tag-result-card");
        var header = $("#view-tag-result-header");
        var desc = $("#view-tag-result-description");
        var edit_desc = $("#tag-description-markdown");

        var new_desc = edit_desc.val();
    	  set_status_text(st_div, LOADING_GLYPHICON + 'Editting hashtag...', 'info', true);
        $.post('https://universalscammerlist.com/api/edit_hashtag.php', { hashtag: tag.tag, description: new_desc }, function(data, stat) {
          set_status_text(st_div, SUCCESS_GLYPHICON + 'Success! Fetching the description from server to verify..', 'success', true);
          $.get('https://universalscammerlist.com/api/hashtags.php', { hashtag: tag.tag }, function(data, stat) {
            cached_tags[ind] = data.data.hashtags[0];
            set_status_text(st_div, SUCCESS_GLYPHICON + 'Successfully fetched from server, reloading..');
            refresh_tag(ind);
          }).fail(function(xhr) {
        	  set_status_text_from_xhr(st_div, xhr);
          });
        }).fail(function(xhr) {
      	  set_status_text_from_xhr(st_div, xhr);
      	});
      });

      $("#tag-preview-description-btn").click(function(e) {
        e.preventDefault();

        var desc = $("#view-tag-result-description");
        var edit_desc = $("#tag-description-markdown");

        var new_desc = edit_desc.val();
        $.post('https://universalscammerlist.com/api/markdown.php', { markdown: new_desc }, function(data, stat) {
          desc.html(data.data.html);
        }).fail(function(xhr) {
      	  set_status_text_from_xhr(st_div, xhr);
      	});
      });
    </script>
  </body>
</html>
