<?php
include 'pagestart.php';
include 'api/common.php'; 

$all_auth = ($auth_level >= $MODERATOR_PERMISSION);
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
      <div class="container-fluid alert" id="statusText" style="display: none"></div>

      <h2>Request Authorization</h2>

      <p>You can request temporary authorization to some restricted features of this website if you are a moderator of the universal scammer list. This request will be passed to the USLBot code which will process your 
      request and send you feedback via Reddit. This authorization will last 1 month, then you must request the USLBot reverifies your status.</p>

      <form id="request_auth_form">
        <button class="btn btn-primary" type="submit" id="req_auth_btn">Request Authorization</button>
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
      
      $("#request-auth-form").on('submit', function(e) {
        e.preventDefault();

	$("#req_auth_btn").attr('disabled', true);
	var statusText = $('#statusText');
	statusText.removeClass('alert-success').removeClass('alert-danger');
	statusText.addClass('alert-info');
	statusText.html('<span class="glyphicon glyphicon-repeat fast-right-spinner"></span> Requesting authorization..');
	statusText.slideDown('fast', function() {
	  setTimeout(function() {
	    // wait a second because this is an important operation so they will feel better if they see a spinner for a sec
	    $.get('/api/request_auth.php', {}, function(data, stat) {
	      statusText.fadeOut('fast', function() {
		statusText.removeClass('alert-info');
		statusText.addClass('alert-success');
		statusText.html('<span class="glyphicon glyphicon-ok"></span> Request made. Monitor your reddit inbox');
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
		$("#req_auth_btn").removeAttr('disabled');
		statusText.fadeIn('fast');
	      });
	    });
	  }, 2000);
	});
      });
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>
