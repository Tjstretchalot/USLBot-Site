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
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>

