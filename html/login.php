
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
        <div class="container-fluid pt-3 pb-3 p3-5 pr-3 mb-2" id="statusText" hidden></div>
	<form id="login-form">
	  <div class="form-group row">
	    <input type="text" class="form-control" id="username" aria-label="Username" placeholder="Username">
	  </div>
	  <div class="form-group row">
	    <input type="password" class="form-control" id="password" aria-label="Password", placeholder="Password">
	  </div>
	  <div class="form-group row justify-content-between ml-0 mr-0">
	    <label class="font-weight-bold col-sm pl-0" style="flex-grow: 1000">Session Duration <a href="#" data-toggle="tooltip" title="How long before you are forced to login again? Logging out will always delete your session." data-placement="top">&#x1f6c8;</a></label>
	    <div style="flex-basis: 330px; flex-grow: 1">
	      <div class="row justify-content-between">
		<div class="form-check col-auto">
		  <label class="form-check-label">
		    <input class="form-check-input" type="radio" name="durationRadios" id="permanentRadio"> Permanent
		  </label>
		</div>
		<div class="form-check col-auto">
		  <label class="form-check-label">
		    <input class="form-check-input" type="radio" name="durationRadios" id="30daysRadio"> 30 Days
		  </label>
		</div>
		<div class="form-check col-auto">
		  <label class="form-check-label">
		    <input class="form-check-input" type="radio" name="durationRadios" id="1dayRadio" checked> 1 Day
		  </label>
		</div>
	      </div>
	    </div>
	  </div>
	  <div class="form-group row">
	    <p>Don't have an account? <a href="/create_account.php">go here to create one</a></p>
	  </div>
	  <div class="form-group row">
	    <button type="submit" class="col-auto btn btn-primary">Submit</button>
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

      $("#login-form").on('submit', function(e) {
	e.preventDefault();
	
	var username = $("#username").val();
	var password = $("#password").val();
	var duration = "forever";
	if($("#30daysRadio").prop("checked")) {
	  duration = "30days";
	}else if($("#1dayRadio").prop("checked")) {
	  duration = "1day";
	}

	$("#username").removeClass("is-invalid");
	$("#password").removeClass("is-invalid");

	if (username && password && duration) {
	  var statusText = $("#statusText");
	  statusText.removeClass("bg-danger").removeClass("bg-success");
	  statusText.addClass("bg-info");
	  statusText.html("<span class=\"glyphicon glyphicon-refresh glyphicon-refresh-animate\"></span> Logging in...");
	  statusText.removeAttr("hidden");
	  $.post("/api/login.php", { username: username, password: password, duration: duration }, function(data, stat) {
	    window.location.href = "https://universalscammerlist.com";
	  }).fail(function(xhr) {
	    console.log(xhr.responseJSON);

	    var json_resp = xhr.responseJSON;
	    var err_type = json_resp.error_type;
	    var err_mess = json_resp.error_message;
	    console.log(err_type + ": " + err_mess);

	    statusText.removeClass("bg-success").removeClass("bg-info");
	    statusText.addClass("bg-danger");
	    statusText.html("<span class=\"glyphicon glyphicon-remove\"></span> " + err_mess);
	    setTimeout(function() {
	      statusText.attr('hidden', true);
	    }, 5000);
	  });
	}else {
	  if (!username) {
	    $("#username").addClass("is-invalid");
	  }
	  if (!password) {
	    $("#password").addClass("is-invalid");
	  }
	}
      });
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>
