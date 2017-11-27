<?php
include 'pagestart.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <title>USL - Create Account (part 2)</title>

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
	    <input type="number" class="form-control" id="user-id" aria-label="User ID" placeholder="User ID" aria-describedby="useridHelpBlock" min="1" step="1" required>
	    <small id="useridHelpBlock" class="form-text text-muted w-100">Your USL unique ID. This should have been provided to you in the message.</small>
	    <div class="invalid-feedback">
	      Please provide the ID sent to you by the USLBot
	    </div>
	  </div>
	  <div class="form-group row">
	    <input type="text" class="form-control" id="token" aria-label="Token" placeholder="Token" aria-describedby="tokenHelpBlock" required>
	    <small id="tokenHelpBlock" class="form-text text-muted w-100">The token sent to you by the USLBot</small>
	    <div class="invalid-feedback">
	      Please provide the token sent to you by the USLBot
	    </div>
	  </div>
	  <div class="form-group row">
	    <input type="password" class="form-control" id="password-1" aria-label="Password" aria-describedby="password1HelpBlock" pattern="[0-9a-zA-Z]{8,}$" required>
	    <small id="password1HelpBlock" class="form-text text-muted w-100">The new password for your USL account. Must be at least 8 characters long.</small>
	    <div class="invalid-feedback">
	      Please provide your new password (at least 8 characters long)
	    </div>
	  </div>
	  <div class="form-group row">
	    <input type="password" class="form-control" id="password-2" aria-label="Repeat Password" aria-describedby="password2HelpBlock" pattern="[0-9a-zA-Z]{8,}$" required>
	    <small id="password2HelpBlock" class="form-text text-muted w-100">Retype your password</small>
	    <div class="invalid-feedback">
	      Please repeat your password exactly
	    </div>
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
      var form = $("#create-account-form");
      form.on('submit', function(e) {
	if (!form[0].checkValidity()) {
	  e.preventDefault();
	  e.stopPropagation();
	  return;
	}


	e.preventDefault();
	var valid = true;
	var id = parseInt($("#user-id").val());
	var token = $("#token").val();
	var pass1 = $("#password-1").val();
	var pass2 = $("#password-2").val();

	if(pass1 != pass2) {
	  $("#password-2")[0].setCustomValidity('This does not match the other password field!');
	  valid = false;
	}

	form.addClass('was-validated');

	if(!valid) {
	  e.stopPropagation();
	  return;
	}

	var statusText = $("#statusText");

	statusText.removeClass("alert-success").removeClass("alert-danger");
	statusText.addClass("alert-info");
	statusText.html("<span class=\"glyphicon glyphicon-refresh glyphicon-refresh-animate\"></span> Submitting request...");
	if(!statusText.is(":visible")) {
	  statusText.slideToggle();
	}
	$("#submit-button").disable(true);

	$.post("/api/create_account_2.php", { id: id, token: token, password: pass1 }, function(data, stat) {
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
	    $("#submit-button").disable(false);
	  });
	});
      });
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>
