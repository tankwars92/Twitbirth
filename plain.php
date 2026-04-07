<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$me = current_user();
$p = isset($_GET['p']) ? trim((string) $_GET['p']) : '';
$p = preg_replace('#[^a-z0-9_-]#i', '', $p);

$titles = [
    'about' => 'Twitbirth / About Us',
    'contact' => 'Twitbirth / Contact Us',
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
<?php } ?>
			</div>
		</div>
	<?php render_site_footer(); ?>
</div>
</body>
</html>
