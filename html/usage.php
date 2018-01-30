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
        <h1>Getting Started</h1>

	<p>This guide assumes you have standard settings - your subreddit is on silent mode and is not under any content restrictions
	(such as read-only or write-only). If you are not on silent-mode you will recieve additional messages but they will not effect
	the actions (bans/unbans) that the bot takes. If your subreddit is read-only then the bot will never ban users on your subreddit.
	Finally, if your subreddit is write-only the bot will not consider bans that occur on your subreddit.</p>

	<p>Your subreddit will need to decide which hashtags it considers important. Hashtags are placed in the ban note by the subreddit 
	moderator who bans a user. You can always use a hashtag to ban a user, even if you do not follow that hashtag, and this is strongly
	encouraged to ensure the maximum effectiveness of your ban. In essence, these hashtags correspond to the rule that is shared by many
	subreddits in the coalition. For example, most subreddits in the coalition have some form of real-world currency in exchange for something,
	and they do not allow people to actively attempt to scam people out of their money without giving anything. These subreddits have agreed
	that, if they ban someone for this reason, they will include "#scammer" in the ban note. When the USLBot picks up this tag, it looks over
	all of the other subreddits that follow that tag and ban them there, too.</p>

	<p>Because subreddits must explicitly opt-in to have users banned for matching a specific tag, you are always free to create new tags if 
	you feel a particular reason on your subreddit does not correspond with any of the existing hashtags. Suppose your subreddit has a rule
	against users posting requests that are for other users. This isn't exactly scamming, so the #scammer tag on the ban note would be 
	inappropriate. One option would be to tag these bans with #delegator. From there, you need to explain to other subreddits on the list
	what that tag means, which is typically done through modmail. If other subreddits want to avoid these types of users (which might be
	recruiters) than they could opt-in to tracking the #delegator tag, and those bans would be propagated to that subreddit.</p>

	<p>Now that you understand the general idea behind what choices you have to make as a subreddit, here are some unordered frequently asked
	questions or situations:</p>

	<p class="pt-1"><strong>I am a moderator on two subreddits and I want to ban a user for a shared rule, but the USLBot is spamming 
	me with messages!</strong></p>
	<p>You should let the USLBot ban users on your sister subreddit. If you ban a user on both subreddits, the USLBot will alert you that it
	could not propagate the ban from sister A to sister B, and that it couldn't ban sister B to sister A.</p>

	<p class="pt-1"><strong>How do I unban a user?</strong></p>
	<p>Send a message to the USLBot of the form <code>$unban /u/john</code>. This will issue a request to unban the user from your account,
	but the request will only succeed if you were the most recent <em>actual person</em> to ban that user on any of the subreddits on the 
	universalscammerlist. This is set up to avoid letting a user A be unbanned on subreddit S if subreddit S banned user A. This can be
	overriden if you are a more senior moderator on universalscammerlist than the original user who added him to the list.</p>

	<p class="pt-1"><strong>What are common tags?</strong></p>
	<p>If a user is intentionally scamming users out of money or has a history of not fulfilling promises, use the #scammer tag. If a user
	has not done anything malicious but you have some evidence that they are not acting benevolently, most typically from an unverified 
	report by another user, use the #sketchy tag. If a user ever mentions that a post was made on his account by someone other than himself,
	use the #compromised tag. If a user is just generally attempting to solicit emotional messages from users, use the #troll tag.</p>
      </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
  </body>
</html>
<?php
include 'pageend.php';
?>

