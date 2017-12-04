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
    <link rel="stylesheet" href="css/footable.standalone.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  </head>
  <body>
    <?php include 'navigation.php'; ?>
    <div class="container mt-5">
      <form id="search-form">
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
        <input type="submit" class="sr-only" />
      </form>
      <hr />
      <div id="output-not-grandfathered">
        <table id="not-gfather-table" class="table">
          <thead>
            <tr>
              <th>Kind</th>
              <th>Subreddit</th>
              <th data-breakpoints="xs sm md lg">Description</th>
              <th data-breakpoints="xs sm md">Details</th>
              <th data-breakpoints="xs sm md">Time</th>
            </tr>
          </thead>
          <tbody id="not-gfather-tbody">
          </tbody>
        </table>
      </div>
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
      });
      $("#search-form").on('submit', function(e) {
        e.preventDefault();
        
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

        $("#search_for").attr('disabled', true);

        $.get("/api/query.php", { query: $("#search_for").val(), hashtags: hashtags.join(','), format: 2 }, function(data, stat) {
          if(!data.data.grandfathered) {
	    var wrapper = $("#output-not-grandfathered");
            var table = $("#not-gfather-table");
            var tbody = $("#not-gfather-tbody");
            wrapper.slideUp('fast', function() {
              tbody.empty();

              var new_html = "";
              for(var i = 0; i < data.data.history.length; i++) {
                var ele = data.data.history[i];
                new_html += "<tr>";
                new_html += "<td>" + ele.kind + "</td>";
                new_html += "<td>" + ele.subreddit + "</td>";
                new_html += "<td>" + ele.description + "</td>";
                new_html += "<td>" + ele.details + "</td>";
                new_html += "<td>" + $.timeago(new Date(ele.time * 1000)) + "</td>";
                new_html += "</tr>";
              }
              tbody.html(new_html);
              table.footable();
              wrapper.slideDown('fast', function() { 
                $("#search_for").removeAttr('disabled');
              });
            });
          }
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
            $("#search_for").removeAttr('disabled');
            statusText.fadeIn('fast');
          });
        });
        
        
      });
    </script>
  </body>
</html>
<?php
include 'pageend.php';
?>
