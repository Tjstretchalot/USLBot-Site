<?php
include 'pagestart.php';
include 'api/common.php';

if ($auth_level < $MODERATOR_PERMISSION) {
  header('Location: https://universalscammerlist.com/403.php');
  return;
}
?>
<!doctype html>
<html lang="en">
<head>
  <title>USL - Add Subreddit</title>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
  <link rel="stylesheet" href="css/footable.standalone.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
  <?php include 'navigation.php'; ?>
  <div class="container mt-5">
    <h1>Add Subreddit</h1>
    <p>Please note that this may cause poor behavior if the bot is not a moderator of the subreddit! This provides a way for you to get the bot to accept moderator invites, but you should manually check that the bot
      really is a moderator of the subreddit with at least access permissions before accepting.</p>


      <h2>Accept Moderator Invite</h2>
      <div class="container-fluid alert" id="status-text-acc-inv" style="display: none"></div>
      <form id="acc-inv-form">
        <div class="form-group row">
          <input type="text" class="form-control" id="acc-inv-subreddit" aria-label="The subreddit to accept the mod invite for" placeholder="The subreddit to accept moderator invite for" aria-describedby="accInvSubHelp">
          <small id="accInvSubHelp" class="form-text text-muted">The subreddit who you believe has sent the USLBot an invite. The USLBot will attempt to accept the invite, though it might take a few minutes. You will
            receive feedback here for when the USLBot has processed this request, but you should double-check on reddit</small>
          </div>
          <div class="form-group row">
            <button id="acc-inv-but" type="submit" class="col-auto btn btn-primary">Submit</button>
          </div>
        </form>

        <h2>Add subreddit</h2>
        <div class="container-fluid alert" id="status-text-add-sub" style="display: none"></div>
        <form id="add-sub-form">
          <div class="form-group row">
            <input type="text" class="form-control" id="add-subreddit" aria-label="The subreddit to add" placeholder="The subreddit to add">
            <small id="addSubHelp" class="form-text text-muted">The subreddit to add to the USLBot's tracked database. This will take effect on the next loop. Do not do this multiple times. This action is not reversible;
              once a subreddit has been added this way the only way to "remove" it is to mark it write-only and read-only. Please triple-check for typos. <b>There should NOT BE a prefix.</b> Case doesn't matter but it's
              preferred to match the case on reddit. Correct example: Care</small>
            </div>
            <div class="form-group row justify-content-around">
              <div class="form-check col-auto">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" id="silent-checkbox" checked> Silent
                </label>
              </div>
              <div class="form-check col-auto">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" id="write-only-checkbox"> Write-Only
                </label>
              </div>
              <div class="form-check col-auto">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" id="read-only-checkbox"> Read-Only
                </label>
              </div>
            </div>
            <div class="form-group row justify-content-around">
              <div class="form-check col-auto">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" id="scammer-checkbox" checked> #scammer
                </label>
              </div>
              <div class="form-check col-auto">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" id="sketchy-checkbox" checked> #sketchy
                </label>
              </div>
              <div class="form-check col-auto">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" id="troll-checkbox" checked> #troll
                </label>
              </div>
              <div class="form-check col-auto">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" id="compromised-checkbox" checked> #compromised
                </label>
              </div>
            </div>
            <div class="form-group row">
              <button id="add-sub-but" type="submit" class="col-auto btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
        <?php include 'footer.php'; ?>
        <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
        <script src="js/jquery.timeago.js"></script>
        <script src="js/moment.js"></script>
        <script src="js/footable.min.js"></script>

        <script type="text/javascript">
        jQuery(function($){
          $('.table').footable();
          $('[data-toggle="tooltip"]').tooltip();
        });

        function handleXHR(statusText, xhr) {
          console.log(xhr);
          var json_resp = xhr.responseJSON;
          if(json_resp !== null && json_resp !== undefined && json_resp.error_type !== null) {
            console.log(xhr.responseJSON);
            var err_type = json_resp.error_type;
            var err_mess = json_resp.error_message;
            console.log(err_type + ": " + err_mess);

            statusText.fadeOut('fast', function() {
              statusText.removeClass("alert-success").removeClass("alert-info");
              statusText.addClass("alert-danger");
              statusText.html(err_mess);
              statusText.fadeIn('fast', function() {
                $('#acc-inv-but').removeAttr('disabled');
                $('#add-sub-but').removeAttr('disabled');
              });
            });
          }else {
            var err_mess = '';
            if(xhr.status === 0) {
              err_mess = 'You do not appear to be connected to the internet';
            }else {
              err_mess = xhr.status + ' ' + xhr.statusText;
            }

            statusText.fadeOut('fast', function() {
              statusText.removeClass("alert-success").removeClass("alert-info");
              statusText.addClass("alert-danger");
              statusText.html("Oops! Something went wrong. Error: " + err_mess);
              statusText.fadeIn('fast', function() {
                $('#acc-inv-but').removeAttr('disabled');
                $('#add-sub-but').removeAttr('disabled');
              });
            });
          }
        }

        function monitorRequest(reqId, secs) {
          var statusText = $("#status-text-acc-inv");
          if(secs > 0) {
            statusText.html('Request successfully made. Checking for status on request in ' + secs + ' seconds...');
            setTimeout(function() { monitorRequest(reqId, secs - 1) }, 1000);
            return;
          }

          statusText.fadeOut('fast', function() {
            statusText.removeClass('alert-success').removeClass('alert-danger');
            statusText.addClass('alert-info');
            statusText.html('Checking status...');
            statusText.fadeIn('fast', function() {
              $.post('https://universalscammerlist.com/api/check_mod_invite.php', { id: reqId }, function(succ) {
                console.log(succ);
                if(succ.data.fulfilled) {
                  statusText.fadeOut('fast', function() {
                    statusText.removeClass('alert-info');

                    if(succ.data.success) {
                      statusText.addClass('alert-success');
                      statusText.html('Success! Check <a href="https://www.reddit.com/r/' + succ.data.subreddit + '/about/moderators">the moderator page</a> to verify it worked.');
                    }else {
                      statusText.addClass('alert-danger');
                      statusText.html('Failure. The most likely reason is that the bot did not have a moderator invite, but it is possible reddit was just finicky. You can try this again if you are pretty sure it has a pending invite.');
                    }

                    statusText.fadeIn('fast', function() {
                      $("#acc-inv-but").attr('disabled', false);
                      $("#add-sub-but").attr('disabled', false);
                    });
                  });
                }else {
                  monitorRequest(reqId, 5);
                }
              }).fail(function(x) {
                handleXHR(x, statusText);
              });
            });
          });
        }

        $('#acc-inv-form').on('submit', function(e) {
          e.preventDefault();

          var statusText = $("#status-text-acc-inv");
          var subreddit = $("#acc-inv-subreddit").val();

          statusText.slideUp('fast', function() {
            statusText.removeClass('alert-danger').removeClass('alert-success');
            statusText.addClass('alert-info');
            statusText.text('Making request...');
            statusText.slideDown('fast');
            $("#acc-inv-but").attr('disabled', true);
            $("#add-sub-but").attr('disabled', true);
            $.post('https://universalscammerlist.com/api/accept_mod_invite.php', { subreddit: subreddit }, function(succ) {
              console.log(succ);
              statusText.fadeOut('fast', function() {
                statusText.removeClass("alert-info").removeClass("alert-danger");
                statusText.addClass("alert-success");
                statusText.html('Request successfully made. Checking for status on request in 5 seconds...');
                statusText.fadeIn('fast', function() {
                  setTimeout(function() { monitorRequest(succ.data.request_id, 4) }, 1000);
                });
              });
            }).fail(function(x) {
              handleXHR(statusText, x);
            });
          });
        });

        $('#add-sub-form').on('submit', function(e) {
          e.preventDefault();

          var statusText = $("#status-text-add-sub");
          var subreddit = $("#add-subreddit").val();
          var silent = $("#silent-checkbox").is(":checked") ? 1 : 0;
          var readOnly = $("#read-only-checkbox").is(":checked") ? 1 : 0;
          var writeOnly = $("#write-only-checkbox").is(":checked") ? 1 : 0;

          var hashtags = [];
          if($("#scammer-checkbox").is(":checked")) {
            hashtags.push("#scammer");
          }
          if($("#sketchy-checkbox").is(":checked")) {
            hashtags.push("#sketchy");
          }
          if($("#troll-checkbox").is(":checked")) {
            hashtags.push("#troll");
          }
          if($("#compromised-checkbox").is(":checked")) {
            hashtags.push("#compromised");
          }

          statusText.slideUp('fast', function() {
            statusText.removeClass("alert-danger").removeClass("alert-success");
            statusText.addClass("alert-info");
            statusText.text("Making request...");
            $("#acc-inv-but").attr("disabled", true);
            $("#add-sub-but").attr("disabled", true);
            statusText.slideDown("fast", function() {
              $.post('https://universalscammerlist.com/api/add_subreddit.php', { subreddit: subreddit, hashtags: hashtags.join(' '), silent: silent, read_only: readOnly, write_only: writeOnly }, function(succ) {
                console.log(succ);
                statusText.fadeOut('fast', function() {
                  statusText.removeClass("alert-info").removeClass("alert-danger");
                  statusText.addClass("alert-success");
                  statusText.html("Success! The subreddit has been added.");
                  statusText.fadeIn('fast');
                });
              }).fail(function(x) {
                handleXHR(statusText, x);
              });
            });
          });
        });
        </script>
      </body>
      </html>
