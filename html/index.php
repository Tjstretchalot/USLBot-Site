<?php
include 'pagestart.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <title>USL Main Page</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
  </head>
  <body>
    <?php include 'navigation.php'; ?>
    <div class="container mt-5">
      <h1>What is this?</h1>

      <p>This acts as the website-portion for the subreddit <a href="https://reddit.com/r/universalscammerlist">/r/universalscammerlist</a>. That subreddit, in conjuction with this website and a reddit bot, manages a list of malicious reddit accounts and minimizes the damage they can deal. This list is referred to as the "USL" for short.</p>

      <h2>How it works</h2>

      <p>The USL is a joint-effort between many subreddits - each participating subreddit allows the <a href="https://reddit.com/u/USLBot">USLBot</a> access to their moderator log and permission to ban/unban users on their subreddit. A simplification of what is done with this information is as follows: The USLBot tracks any bans that the subreddit makes and saves them locally. If the ban note (which is only visible to moderators on the subreddit) contains certain keywords (typically #scammer) the USLBot will ban the user on all participating subreddits.</p>

      <p>The USL is made a bit more complicated when you take into account that not all participating subreddits want to have users banned for the same reasons. That is to say, some subreddits are very risk-averse and want to ban any users that seem sketchy but haven't done anything directly against the rules. Other subreddits only want to ban users who have concrete evidence against them. To accomplish both of these goals, the USLBot maintains a list of subscribed hashtags for each participating subreddit. Before subscribing to a new hashtag, all participating subreddits agree to use that tag for agreed upon circumstances - e.g. to use the #compromised tag if a user has admitted to or been caught having multiple users pilot a single account (intentionally or not). Then participating subreddits will subscribe to that tag - #compromised. If <em>other</em> participating subreddits ban a user with that tag, <em>regardless</em> of if they themself listen on that tag, the USLBot will ban that user on subreddits who <em>do</em> subscribe to that tag.</p>

      <p>In addition to the core behavior the USL has many safety features to prevent malicious use. Firstly, subreddits must be manually approved to join the USL and subscribing to tags must be manually approved. If multiple subreddits ban an account individually, both subreddits are notified of the collision. All bans by the USLBot are maintained in a database that is backed up daily along with which moderator triggered the ban, so for example it is possible to reverse all bans that were propagated by the USLBot due to a specific moderators action between two timestamps. The USLBot is reboot-friendly, it can continue where it left off even after reboots and it limits how much time it spends handling a specific subreddit before getting around to other users. That and many more safety checks are built in, but most importantly the USLBot is <a href="https://github.com/Tjstretchalot/USLBot">open-source</a>.</p>

      <h2>How to use this website</h2>

      <p>The most common way that you would use this website is to search if a particular user has been banned by the universal scammer list. By using the search feature in the navigation-bar you can search users by specific pre-approved tags. The USLBot maintains a complete list of all ban/unban actions that have taken place since 3 months prior to any subreddit joining the USL (3 months is how much history reddit saves), however most of these bans are not relevant to other subreddits and thus searching is restricted to pre-approved tags except by USL moderators.</p>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
  </body>
</html>
<?php
include 'pageend.php';
?>
