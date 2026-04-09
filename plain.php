<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$me = current_user();
$p = isset($_GET['p']) ? trim((string) $_GET['p']) : '';
$p = preg_replace('#[^a-z0-9_-]#i', '', $p);

$titles = [
    'about' => 'Twitbirth / About Us',
    'contact' => 'Twitbirth / Contact Us',
    'api' => 'Twitbirth / API',
];

if ($p === '' || !isset($titles[$p])) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Not found</title></head><body><p>Page not found. <a href="index.php">Home</a></p></body></html>';
    exit;
}

$pageTitle = $titles[$p];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="Content-Language" content="en-us">
	<title><?= esc_html($pageTitle) ?></title>
	<link href="stylesheets/screen.css" media="screen, projection" rel="Stylesheet" type="text/css">
	<link href="stylesheets/handheld.css" media="handheld" rel="Stylesheet" type="text/css">
	<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
</head>
<body class="account" id="front">

	<ul id="accessibility">
		<li><a href="#content" accesskey="2">Skip to content</a></li>
	</ul>

	<div id="container" class="subpage plain">
		<?php render_site_header_top_row($me); ?>

		<div id="content">
			<div class="wrapper">
<?php if ($p === 'about') { ?>
			<b><font size="3" class="h2_heading">About Us</font></b>

			<p>
				Twitbirth is a website that copies Twitter from 2006 and works in <b>Internet Explorer 6</b> browsers and above. The source code is completely open, you can find it <a href="https://github.com/tankwars92/Twitbirth/" target="_blank">here</a>.
			</p>
<?php } elseif ($p === 'contact') { ?>
			<b><font size="3" class="h2_heading">Contact Us</font></b>

			<p>
				You can contact us at <a href="mailto:bitbybyte@w10.site">bitbybyte@w10.site</a> / <a href="http://kicq.ru/reg117623/">KICQ</a> <b>3-739-186</b>.
			</p>
<?php } elseif ($p === 'api') { ?>
			<b><font size="3" class="h2_heading">Twitbirth API</font></b>
			<p>Want to make a badge, timeline visualization, or your own client? Twitbirth exposes part of internals via JSON and XML.</p>
			<p>All methods (except Public Timeline) require HTTP Basic Auth. Username is your account email, password is your account password.</p>

			<p><b>Public Timeline</b></p>
			<p><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?public_timeline.json</code><br><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?public_timeline.xml</code></p>

			<p><b>Timeline of you and your friends</b></p>
			<p><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?friends_timeline.json</code><br><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?friends_timeline.xml</code></p>

			<p><b>A list of your friends and their current update</b></p>
			<p><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?friends.json</code><br><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?friends.xml</code></p>

			<p><b>A list of your followers and their current update</b></p>
			<p><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?followers.json</code><br><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?followers.xml</code></p>

			<p><b>Updating your status</b></p>
			<p>Best done with HTTP POST (GET also works):</p>
			<p><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?update.json&amp;status=testing</code><br><code>http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?update.xml&amp;status=testing</code></p>
			
			<br>
			<p><b>Request examples</b></p>
			<p><code>GET /api.php?public_timeline.json</code></p>
			<p><code>GET /api.php?friends_timeline.json</code> (Basic Auth required)</p>
			<p><code>POST /api.php?update.json</code> with body <code>status=Hello+World</code> (Basic Auth required)</p>
			<p><code>curl -u "YOUR_EMAIL:YOUR_PASSWORD" -d "status=Hello+World" "http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?update.json"</code></p>
			<p><code>curl -u "YOUR_EMAIL:YOUR_PASSWORD" "http://<?= $_SERVER['HTTP_HOST'] ?>/api.php?friends.json"</code></p>

			<p>That is all for now. More to come.</p>
<?php } ?>
			</div>
		</div>
	<?php render_site_footer(); ?>
</div>
</body>
</html>
