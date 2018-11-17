<?php
include 'pagestart.php';
include 'api/common.php'; 

  if($auth_level < $MODERATOR_PERMISSION) {
    http_response_code(401);
    echo '<html><head><title>Not Authorized</title></head><body><p>You are not authorized to view this page. <a href="/index.php">Go Back</a></p></body></html>';
    $conn->close();
    return;
  }
?>
<!doctype html>
<html lang="en">
  <head>
    <title>USL - Logs</title>
    <?php include('metatags.php'); ?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <link rel="stylesheet" href="css/footable.standalone.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/logs.css">
  </head>
  <body>
    <?php include('navigation.php'); ?>
    <div class="container px-2 py-5">
      <section>
        <div class="container-fluid alert" id="status-text" style="display: none"></div>

        <form id="controls-form">
          <div class="form-group row justify-content-around">
            <button id="download-button" type="button" class="col-auto btn btn-primary">Download</button>
            <button id="fetch-latest-button" type="button" class="col-auto btn btn-secondary">Fetch latest</button>
            <button id="apply-filters-button" type="button" class="col-auto btn btn-secondary">Apply filters</button>
            <button id="restart-bot-button" type="button" class="col-auto btn btn-danger">Restart Bot</button>
          </div>
          <div class="form-group row">
            <input id="after-time" name="timestamp" type="time" step="1" class="form-control" aria-label="After Time" aria-describedby="after-time-helpblock" value="00:00">
            <small id="after-time-helpblock" class="form-text text-muted">Limit to responses after this time today. Press apply filters to apply</small>
          </div>
          <div class="form-group row">
            <select class="form-control filter-control" id="severity" aria-label="Minimum severity" aria-describedby="severity-helpblock" value="trace">
              <option value="trace">Trace</option>
              <option value="debug">Debug</option>
              <option value="info">Info</option>
              <option value="warn">Warn</option>
              <option value="error">Error</option>
            </select>
            <small id="severity-helpblock" class="form-text text-muted">Limit to messages of the specified severity or higher</small>
          </div>
        </form>
        <ul id="log-list">
        </ul>
      </section>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
    <script src="js/jquery.timeago.js"></script>
    <script src="js/moment.js"></script>
    <script src="js/moment-timezone.js"></script>
    <script src="js/footable.min.js"></script>
    <script src="js/status_text_utils.js"></script>
    <script type="text/javascript">
      var latest_raw = null;
      var latest_parsed = null;
      var filters = [
        function() {
          var after_time = $("#after-time").val();
          var after_date = new Date();
          after_date.setMilliseconds(0);
          after_date.setSeconds(0);
          after_date.setMinutes(parseInt(after_time.slice(3)));
          after_date.setHours(parseInt(after_time.slice(0, 2)));
          return function(parsed_line) {
            return parsed_line.timestamp >= after_date;
          };
        },
        function() {
          var filter_severity = $("#severity").val();

          function sev_to_num(sev) {
            var lowered = sev.toLowerCase();

            if(lowered === 'trace')
              return 0;
            else if(lowered === 'debug')
              return 1;
            else if(lowered === 'info')
              return 2;
            else if(lowered === 'warn')
              return 3;
            else if(lowered === 'error')
              return 4;
            else
              return 5;
          }

          var filter_severity_num = sev_to_num(filter_severity); 
          return function(parsed_line) {
            return sev_to_num(parsed_line.level) >= filter_severity_num;
          }
        }
      ];

      // Return promise for raw log file
      function fetch_raw() {
        return new Promise(function(resolve, reject) {
          $.get( "https://universalscammerlist.com/api/logs.php", {}, function(data, stat) {
            resolve(data);
          }).fail(function(xhr) {
            var err_mess = 'Unknown';
            if(typeof(xhr.responseJSON) !== 'undefined') {
              var json_resp = xhr.responseJSON;
              var err_type = json_resp.errors[0].error_type;
              var err_mess = json_resp.errors[0].error_message;
            }else {
              err_mess = xhr.status + ": " + xhr.statusText;
            }
            reject(err_mess);
          });
        });
      }

      // Return promise for raw log file
      function fetch_raw_with_status_text() {
        return new Promise(function(resolve, reject) {
          var st_div = $("#status-text");
          set_status_text(st_div, LOADING_GLYPHICON + ' Fetching raw log file..', 'info', true).then(function() {
            fetch_raw().then(function(data) {
              set_status_text(st_div, SUCCESS_GLYPHICON + ' Success!', 'success', true).then(function() {
                resolve(data);
              });
            }, function(err_mess) {
              set_status_text(st_div, FAILURE_GLYPHICON + ' Error: ' + err_mess, 'danger', false).finally(function() {
                reject(err_mess);
              });
            });
          });
        });
      }

      // some things will print out multiple lines, but we only get timestamp info
      // for the first line when they do that. This copies all the meta-info from the
      // previous line but keeps the text from the current line, assuming it has
      // a last line
      function parse_continue_line(line, last_line) {
	if(!last_line) {
	  return null;
	}
	return { timestamp: last_line.timestamp, type: last_line.time, level: last_line.level, file: last_line.file, text: line }
      }

      // parses one raw line into
      // { timestamp: Date, type: text, level: string, file: string, text: string }
      function parse_raw_line(line, last_line) {
        // starts with something like 2018-Jan-13 16:36:12 PM
        // always 23 characters
        if(line.length < 24)
          return parse_continue_line(line, last_line); // malformed
        var timestamp_str = line.slice(0, 23);
        var timestamp = moment.tz(timestamp_str, "YYYY-MMM-DD hh:mm:ss A", "America/New_York").toDate();
        if(isNaN(timestamp.getTime()))
          return parse_continue_line(line, last_line); // malformed

        var ch_ind = 25;
        while(line[ch_ind] != ']') {
          if(ch_ind >= line.length)
            return parse_continue_line(line, last_line); // malformed
          ch_ind++;
        }
        var type = line.slice(25, ch_ind);

    
        ch_ind += 2; // skip space
        var level_start = ch_ind;
        while(line[ch_ind] != ' ') {
          if(ch_ind >= line.length)
            return parse_continue_line(line, last_line); // malformed

          ch_ind++;
        }

        var level = line.slice(level_start, ch_ind);
        
        while(line[ch_ind] === ' ') {
          ch_ind++; // sometimes theres multiple spaces here
        }

        var file_start = ch_ind;
        while(line[ch_ind] != ' ') {
          if(ch_ind >= line.length)
            return parse_continue_line(line, last_line); // malformed
          
          ch_ind++;
        }

        var file = line.slice(file_start, ch_ind);
        ch_ind += 3; // skip " - "
        var text = line.slice(ch_ind);
        return { timestamp: timestamp, type: type, level: level, file: file, text: text };
      }

      // returns a promise to parse the specified raw
      function parse_raw(raw) {
        return new Promise(function(resolve, reject) {
          var parsed = [];
          var spl_on_line = raw.split("\n");
	  var last_line = null;
          for(var i = 0, len = spl_on_line.length; i < len; i++) {
            var parsed_line = parse_raw_line(spl_on_line[i], last_line);

            if(parsed_line !== null) {
	      last_line = parsed_line;
              parsed.push(parsed_line);
            }
          }
          resolve(parsed);
        });
      }

      // returns a promise to set ul to raw
      function set_ul_to_parsed(all_parsed) {
        return new Promise(function(resolve, reject) {
          var filter_fns = [];
          for(var i = 0, len = filters.length; i < len; i++) {
            filter_fns.push(filters[i]());
          }

          var ul = $("#log-list");
          ul.slideUp('fast', function() {
            setTimeout(function() { // hiding a lot of nodes requires more time than jquery thinks
              ul.empty();
              for(var i = 0, len = all_parsed.length; i < len; i++) {
                var parsed = all_parsed[i];
                var filtered_out = false;
                for(var j = 0, len2 = filter_fns.length; j < len2; j++) {
                  if(!filter_fns[j](parsed)) {
                    filtered_out = true;
                    break;
                  }
                }
                if(filtered_out)
                  continue;

                var li = $("<li>");
                li.addClass("level-" + parsed.level.toLowerCase());
                li.addClass("li-log");
                var time = $("<span>");
                time.addClass("short-timestamp");
                time.attr("data-toggle", "tooltip");
                time.attr("title", parsed.timestamp.toLocaleString());
                time.html(parsed.timestamp.toLocaleTimeString());
                li.append(time);
                var text = $("<span>");
                text.addClass("log-message");
                text.html(parsed.text);
                li.append(text);
                ul.append(li);
                time.tooltip();
              }
              ul.slideDown('fast', function() {
                resolve(true);
              });
            }, 50);
          });
        });
      }

      // returns a promise to parsed
      function set_ul_to_raw_with_status_text(raw) {
        return new Promise(function(resolve, reject) {
          var st_div = $("#status-text");
          set_status_text(st_div, LOADING_GLYPHICON + ' Parsing raw log file..', 'info', true).then(function() {
            parse_raw(raw).then(function(parsed) {
              set_status_text(st_div, LOADING_GLYPHICON + ' Formatting parsed log file..', 'info', true).then(function() {
                set_ul_to_parsed(parsed).then(function() {
                  set_status_text(st_div, SUCCESS_GLYPHICON + ' Success!', 'success', true).then(function() {
                    resolve(parsed);
                  });
                });
              })
            }, function(reject_reason) {
              reject(reject_reason)
            });
          });
        });
      }

      function on_filter_changed() {
        var st_div = $("#status-text");
        set_status_text(st_div, LOADING_GLYPHICON + ' Formatting parsed log file..', 'info', true).then(function() {
          set_ul_to_parsed(latest_parsed).then(function() {
            set_status_text(st_div, SUCCESS_GLYPHICON + ' Success', 'success', true);
          }, function(rej_reason) {
            set_status_text(st_div, FAILURE_GLYPHICON + ' Something went wrong! ' + rej_reason, 'danger', true);
          });
        });
      }

      $("#fetch-latest-button").on('click', function(e) {
        e.preventDefault();

        var b = $("#controls-form div button");
        b.attr("disabled", true);
        fetch_raw_with_status_text().then(function(raw) {
          latest_raw = raw;
          set_ul_to_raw_with_status_text(latest_raw).then(function(parsed) {
            latest_parsed = parsed;
          }).finally(function() {
            b.attr("disabled", false);
          });
        });
      });

      $("#download-button").on('click', function(e) {
        e.preventDefault();

        var st_div = $("#status-text");
        if(typeof(latest_raw) !== 'string') {
          set_status_text(st_div, FAILURE_GLYPHICON + ' No logs loaded! Press fetch latest', 'danger', true);
          return;
        }

        set_status_text(st_div, LOADING_GLYPHICON + ' Downloading..', 'info', true).then(function() {
          // modified from https://stackoverflow.com/questions/3665115/create-a-file-in-memory-for-user-to-download-not-through-server
          var element = document.createElement('a');
          element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(latest_raw));
          element.setAttribute('download', 'logs.txt');

          element.style.display = 'none';
          document.body.appendChild(element);

          element.click();

          document.body.removeChild(element);
          return set_status_text(st_div, SUCCESS_GLYPHICON + ' Success!', 'success', true);
        }); 
      });

      $("#apply-filters-button").on('click', function(e) {
        e.preventDefault();
        on_filter_changed();
      });

      $("#restart-bot-button").on('click', function(e) {
        e.preventDefault();
        var st_div = $("#status-text");
        set_status_text(st_div, LOADING_GLYPHICON + ' Requesting restart..', 'info', true).then(function() {
          $.post('https://universalscammerlist.com/api/restart.php', {}, function(data) {
            set_status_text(st_div, SUCCESS_GLYPHICON + ' Restart requested (watch logs to see if it worked)', 'success', true);
          }).fail(function(xhr) {
            var err_mess = 'Unknown';
            if(typeof(xhr.responseJSON) !== 'undefined') {
              err_mess = xhr.responseJSON.errors[0].error_message;
            }else {
              err_mess = xhr.status + ": " + xhr.statusText;
            }

            set_status_text(st_div, FAILURE_GLYPHICON + ' Something went wrong: ' + err_mess, 'danger', true);
          });
        });
      });

      $(".filter-control").change(on_filter_changed);
    </script>
  </body>
</html>
<?php
  $conn->close();
?>
