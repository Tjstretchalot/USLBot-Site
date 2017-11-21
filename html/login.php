
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
    <div class="container mt-5">
      <form id="login-form">
        <div class="form-group row">
	  <input type="text" class="form-control" id="username" aria-label="Username" placeholder="Username">
	</div>
	<div class="form-group row">
	  <input type="password" class="form-control" id="password" aria-label="Password", placeholder="Password">
	</div>
	<div class="form-group row justify-content-around">
	  <div class="form-check col-auto">
	    <label class="form-check-label">
	      <input class="form-check-input" type="radio" name="durationRadios" id="permanentRadio" checked> Permanent
	    </label>
	  </div>
	  <div class="form-check col-auto">
	    <label class="form-check-label">
	      <input class="form-check-input" type="radio" name="durationRadios" id="30daysRadio" checked> 30 Days
	    </label>
	  </div>
	  <div class="form-check col-auto">
	    <label class="form-check-label">
	      <input class="form-check-input" type="radio" name="durationRadios" id="1dayRadio" checked> 1 Day
	    </label>
	  </div>
	</div>
	<input type="submit" class="sr-only" />
      </form>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>

    <script type="text/javascript">
      $("#login-form").on('submit', function(e) {
	e.preventDefault();
	console.log('login form submitted');
      });
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>
