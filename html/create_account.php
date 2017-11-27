<?php
include 'pagestart.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <title>USL - Login</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
  </head>
  <body>
    <?php include 'navigation.php'; ?>
    <div class="pl-1 pr-1">
      <div class="container mt-5">
        <div class="container-fluid alert" id="statusText" style="display: none"></div>
	<form id="create-account-form">
	  <div class="form-group row">
	    <input type="text" class="form-control" id="username" aria-label="Username" placeholder="Username" aria-describedby="usernameHelpBlock">
	    <small id="usernameHelpBlock" class="form-text text-muted">Your reddit username. A message will be sent to this reddit account to confirm your identity and give you next steps.</small>
	  </div>
	  <div class="form-group row">
	    <button id="submit-button" type="submit" class="col-auto btn btn-primary">Submit</button>
	  </div>
	</form>
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

      $("#create-account-form").on('submit', function(e) {
	e.preventDefault();
	var username = $("#username").val();

	$("#username").removeClass("is-invalid");
	if(!username) {
	  $("#username").addClass("is-invalid");
	  return;
	}

	var statusText = $("#statusText");

	statusText.removeClass("alert-success").removeClass("alert-danger");
	statusText.addClass("alert-info");
	statusText.html("<span class=\"glyphicon glyphicon-refresh glyphicon-refresh-animate\"></span> Submitting request...");
	if(!statusText.is(":visible")) {
	  statusText.slideToggle();
	}
	$("#submit-button").attr('disabled', true);

	$.post("/api/create_account_1.php", { username: username }, function(data, stat) {
	  statusText.fadeOut('fast', function() {
	    statusText.removeClass("alert-info").removeClass("alert-danger");
	    statusText.addClass("alert-success");
	    statusText.html("<span class=\"glyphicon glyphicon-ok\"></span> " + data.data.message);
	    statusText.fadeIn('fast');
	  });
	}).fail(function(xhr) {
	  console.log(xhr.responseJSON);

	  var json_resp = xhr.responseJSON;
	  var err_type = json_resp.error_type;
	  var err_mess = json_resp.error_message;
	  console.log(err_type + ": " + err_mess);

          statusText.fadeOut('fast', function() {
	    statusText.removeClass("alert-success").removeClass("alert-info");
	    statusText.addClass("alert-danger");
	    statusText.html("<span class=\"glyphicon glyphicon-remove\"></span> " + err_mess);
	    $("#submit-button").removeAttr('disabled');
	  });
	});
      });
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>
