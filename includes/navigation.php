<?php
include_once 'api/common.php';

$pages = array(
  array(
    'link' => "/index.php",
    'name' => "Index"
  ),
  array(
    'link' => "/search.php",
    'name' => "Search"
  ),
  array(
    'link' => '/usage.php',
    'name' => 'Usage'
  ),
  array(
    'link' => '/status.php',
    'name' => 'Status'
  )
);

if($logged_in_person === null) {
  $pages[] = array(
    'link' => "/login.php",
    'name' => "Login"
  );
}else {
  if($auth_level < $MODERATOR_PERMISSION) {
    $pages[] = array(
      'link' => '/request_auth.php',
      'name' => 'Request Auth'
    );
  }else {
    $pages[] = array(
      'link' => '/logs.php',
      'name' => 'Logs'
    );
    $pages[] = array(
      'link' => '/add_subreddit.php',
      'name' => 'Add Sub'
    );
  }

  $pages[] = array(
    'link' => '/logout.php',
    'name' => 'Logout'
  );
}
?>
<!-- navigation start -->

<div class="container-fluid">
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">USL</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle Navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav mr-auto">
        <?php foreach($pages as $key=>$page): ?>
	<?php if ($_SERVER['REQUEST_URI'] == $page['link'] || ($_SERVER['REQUEST_URI'] == '/' && $page['link'] == '/index.php')): ?>
	  <li class="nav-item active"> 
	    <a class="nav-link" href="#"><?= $page['name'] ?> <span class="sr-only">(current)</span></a>
	  </li>
	<?php else: ?>
	  <li class="nav-item">
	    <a class="nav-link" href="<?= $page['link'] ?>"><?= $page['name'] ?></a>
	  </li>
	<?php endif; ?>
	<?php endforeach; ?>
      </ul>
    </div>
  </nav>
</div>

<!-- navigation end -->
