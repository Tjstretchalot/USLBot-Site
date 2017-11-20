<?php
include 'pagestart.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <title>USL - Search</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
  </head>
  <body>
    <?php include 'navigation.php'; ?>
    <div class="container mt-5">
      <form action="#" method="get">
        <div class="form-group row">
	  <input type="text" class="form-control" id="search_for" aria-label="Who to search for" placeholder="Who?">
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
	</div>
	<input type="submit" hidden="true" />
      </form>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
  </body>
</html>
<?php
include 'pageend.php';
?>
