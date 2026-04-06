<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$me = current_user();
if ($me === null) {
    header('Location: login.php', true, 302);
    exit;
}

$errors = [];
$notice = '';

$form = [
    'display_name' => (string) ($me['display_name'] ?? ''),
    'email' => (string) ($me['email'] ?? ''),
    'time_zone' => (string) ($me['time_zone'] ?? ''),
    'bio' => (string) ($me['bio'] ?? ''),
    'location' => (string) ($me['location'] ?? ''),
    'web' => (string) ($me['web'] ?? ''),
    'exclude_public' => ((int) ($me['exclude_from_public_timeline'] ?? 0)) === 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['user'] ?? [];
    $fileProfile = null;
    if (isset($_FILES['user']['tmp_name']['profile_image'])) {
        $fileProfile = [
            'name' => $_FILES['user']['name']['profile_image'] ?? '',
            'type' => $_FILES['user']['type']['profile_image'] ?? '',
            'tmp_name' => $_FILES['user']['tmp_name']['profile_image'] ?? '',
            'error' => (int) ($_FILES['user']['error']['profile_image'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($_FILES['user']['size']['profile_image'] ?? 0),
        ];
    }

    $form['display_name'] = trim((string) ($u['name'] ?? ''));
    $form['email'] = trim((string) ($u['email'] ?? ''));
    $form['time_zone'] = trim((string) ($u['time_zone'] ?? ''));
    $form['bio'] = trim((string) ($u['bio'] ?? ''));
    $form['location'] = trim((string) ($u['location'] ?? ''));
    $form['web'] = trim((string) ($u['web'] ?? ''));
    $form['exclude_public'] = isset($u['exclude_public']) && (string) $u['exclude_public'] === '1';

    if ($form['display_name'] === '') {
        $errors[] = 'Please enter your name.';
    } elseif (strlen($form['display_name']) > 200) {
        $errors[] = 'Name must be 200 characters or less.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (strlen($form['bio']) > 500) {
        $errors[] = 'Bio must be 500 characters or less.';
    }
    if (strlen($form['location']) > 160) {
        $errors[] = 'Location must be 160 characters or less.';
    }
    if (strlen($form['web']) > 500) {
        $errors[] = 'Web must be 500 characters or less.';
    }

    $wantAvatar = $fileProfile !== null && $fileProfile['error'] !== UPLOAD_ERR_NO_FILE;
    if ($wantAvatar) {
        if ($fileProfile['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Could not upload picture.';
        } else {
            $info = @getimagesize($fileProfile['tmp_name']);
            $mime = is_array($info) ? ($info['mime'] ?? '') : '';
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if ($info === false || !in_array($mime, $allowed, true)) {
                $errors[] = 'Picture must be JPG, PNG, GIF, or WebP.';
            }
        }
    }

    if ($errors === []) {
        $pdo = app_pdo();
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ? COLLATE NOCASE AND id != ?');
        $st->execute([$form['email'], $me['id']]);
        if ($st->fetch()) {
            $errors[] = 'That email address is already in use.';
        } else {
            $newAvatarPath = null;
            if ($wantAvatar) {
                $av = save_avatar_for_existing_user((int) $me['id'], $fileProfile);
                if (!$av['ok']) {
                    $errors[] = (string) $av['error'];
                } elseif ($av['path'] !== null) {
                    $newAvatarPath = $av['path'];
                }
            }
            if ($errors === []) {
                $tz = $form['time_zone'] !== '' ? $form['time_zone'] : null;
                $bio = $form['bio'] !== '' ? $form['bio'] : null;
                $loc = $form['location'] !== '' ? $form['location'] : null;
                $web = $form['web'] !== '' ? $form['web'] : null;
                $excludePublic = $form['exclude_public'] ? 1 : 0;
                if ($newAvatarPath !== null) {
                    $up = $pdo->prepare('UPDATE users SET display_name = ?, email = ?, time_zone = ?, bio = ?, location = ?, web = ?, avatar_path = ?, exclude_from_public_timeline = ? WHERE id = ?');
                    $up->execute([$form['display_name'], $form['email'], $tz, $bio, $loc, $web, $newAvatarPath, $excludePublic, $me['id']]);
                } else {
                    $up = $pdo->prepare('UPDATE users SET display_name = ?, email = ?, time_zone = ?, bio = ?, location = ?, web = ?, exclude_from_public_timeline = ? WHERE id = ?');
                    $up->execute([$form['display_name'], $form['email'], $tz, $bio, $loc, $web, $excludePublic, $me['id']]);
                }
                $me = user_by_id((int) $me['id']);
                if ($me !== null) {
                    $form['display_name'] = (string) ($me['display_name'] ?? '');
                    $form['email'] = (string) ($me['email'] ?? '');
                    $form['time_zone'] = (string) ($me['time_zone'] ?? '');
                    $form['bio'] = (string) ($me['bio'] ?? '');
                    $form['location'] = (string) ($me['location'] ?? '');
                    $form['web'] = (string) ($me['web'] ?? '');
                    $form['exclude_public'] = ((int) ($me['exclude_from_public_timeline'] ?? 0)) === 1;
                }
                $notice = 'Your settings were saved.';
            }
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="Content-Language" content="en-us">
	<title>Twitbirth / Settings</title>
	<link href="stylesheets/screen.css" media="screen, projection" rel="Stylesheet" type="text/css">
	<link href="stylesheets/handheld.css" media="handheld" rel="Stylesheet" type="text/css">
	<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
	
</head>
<body class="account" id="front">
	<ul id="accessibility">
		<li><a href="#navigation" accesskey="2">Skip to navigation</a></li>
		<li><a href="#side">Skip to sidebar</a></li>
	</ul>
	<div id="container" class="subpage">
		<?php render_site_header_top_row($me); ?>
		<div id="content"><div class="wrapper">
			<b><font size="3" class="h2_heading">Settings</font></b>

<?php if ($notice !== '') { ?>
			<div class="notify" style="margin-bottom:1em;border-color:#060;">
				<p><?= esc_html($notice) ?></p>
			</div>
<?php } ?>
<?php if ($errors !== []) { ?>
			<div class="notify" style="margin-bottom:1em;border-color:#c00;">
				<?php foreach ($errors as $msg) { ?>
					<p><?= esc_html($msg) ?></p>
				<?php } ?>
			</div>
<?php } ?>

<form action="settings.php" enctype="multipart/form-data" method="post" name="settings_form">
<fieldset>
	<table cellspacing="0">
		<tbody>
		<tr>
			<th><label for="user_username_ro">Username</label></th>
			<td><input id="user_username_ro" name="user_username_ro" size="30" type="text" value="<?= esc_html($me['username']) ?>" readonly="readonly" style="background:#eee;"> <small>Cannot be changed</small></td>
		</tr>
		<tr>
			<th><label for="user_name">Name</label></th>
			<td><input id="user_name" name="user[name]" size="30" type="text" value="<?= esc_html($form['display_name']) ?>"> <small>Real name or nick name</small></td>
		</tr>
		<tr>
			<th><label for="user_email">Email Address</label></th>
			<td><input id="user_email" name="user[email]" size="30" type="text" value="<?= esc_html($form['email']) ?>"> <small>In case you forget your password!</small></td>
		</tr>
		<tr>
			<th><label for="user_time_zone">Time Zone</label></th>
			<td><select id="user_time_zone" name="user[time_zone]"><?php readfile(__DIR__ . '/includes/timezone_select_options.inc.html'); ?></select></td>
		</tr>
		<tr>
			<th><label for="user_bio">Bio</label></th>
			<td><textarea id="user_bio" name="user[bio]" rows="4" cols="50"><?= esc_html($form['bio']) ?></textarea> <small>Short description (max 500 characters)</small></td>
		</tr>
		<tr>
			<th><label for="user_location">Location</label></th>
			<td><input id="user_location" name="user[location]" size="40" type="text" value="<?= esc_html($form['location']) ?>"> <small>City, country, etc.</small></td>
		</tr>
		<tr>
			<th><label for="user_web">Web</label></th>
			<td><input id="user_web" name="user[web]" size="50" type="text" value="<?= esc_html($form['web']) ?>"> <small>Your blog or homepage URL</small></td>
		</tr>
		<tr>
			<th><label for="user_profile_image">Picture</label></th>
			<td>
				<p><img src="<?= esc_html(avatar_public_url($me['avatar_path'] ?? null)) ?>" alt="" width="48" height="48" style="vertical-align:middle;border:1px solid #999;margin-right:8px;" /></p>
				<input id="user_profile_image" name="user[profile_image]" size="30" type="file">
				<p><small>New image replaces your current avatar (JPG, PNG, GIF, WebP).</small></p>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<input id="user_exclude_public" name="user[exclude_public]" type="checkbox" value="1"<?= $form['exclude_public'] ? ' checked="checked"' : '' ?>> <label for="user_exclude_public">Don't show my updates on the public timeline</label>
				<p><small>Your profile and RSS stay available; only the site-wide public timeline and "Featured" list will hide your posts.</small></p>
			</td>
		</tr>
		<tr>
			<th></th>
			<td><input name="commit" type="submit" value="Save"></td>
		</tr>
		</tbody>
	</table>
</fieldset>
</form>
<fieldset>
	<table cellspacing="0">
		<tbody>
<tr>
    <th></th>
    <td>
<div style="margin: 0 0 0 0;">
	<form action="delete_account.php" method="post" onsubmit="return confirm('Delete your account? This cannot be undone. Your updates will remain, but your profile will disappear.');">
		<input type="submit" value="Delete your account" style="font-weight:bold;color:#c00;background:transparent;border:0;padding:0;text-decoration:underline;cursor:pointer;" />
	</form>
</div>
</td>
</tr>
</tbody>
</table>
</fieldset>
<script type="text/javascript">
//<![CDATA[
var _tz = document.getElementById('user_time_zone');
if (_tz) { _tz.value = <?= json_encode($form['time_zone'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; }
//]]>
</script>
		</div></div>
		<?php render_site_footer(); ?>
	</div>
</body>
</html>
