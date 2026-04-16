<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

function api_xml_text(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function api_send_unauthorized(): void
{
    header('WWW-Authenticate: Basic realm="Twitbirth API"');
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Authentication required.';
    exit;
}

function api_auth_user_or_401(): array
{
    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
    if ($user === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = (string) $_SERVER['HTTP_AUTHORIZATION'];
        if (stripos($auth, 'basic ') === 0) {
            $decoded = base64_decode(substr($auth, 6), true);
            if (is_string($decoded) && strpos($decoded, ':') !== false) {
                [$user, $pass] = explode(':', $decoded, 2);
            }
        }
    }
    if ($user === '' || $pass === '') {
        api_send_unauthorized();
    }
    $st = app_pdo()->prepare('SELECT id, username, display_name, email, avatar_path, password_hash FROM users WHERE email = ? COLLATE NOCASE AND is_deleted = 0');
    $st->execute([$user]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($pass, (string) $row['password_hash'])) {
        api_send_unauthorized();
    }
    return $row;
}

function api_get_method_path(): string
{
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($qs !== '') {
        foreach (explode('&', $qs) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $eqPos = strpos($part, '=');
            $token = $eqPos === false ? $part : substr($part, 0, $eqPos);
            $token = rawurldecode($token);
            if ($token !== '') {
                return $token;
            }
        }
    }
    return '';
}

function api_wants_xml(string $methodPath): bool
{
    return (bool) preg_match('/\.xml$/i', $methodPath);
}

function api_statuses_to_payload(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'text' => (string) $r['body'],
            'source' => status_source_label($r),
            'created_at' => (string) $r['created_at'],
            'user' => [
                'id' => (int) $r['user_id'],
                'username' => (string) $r['username'],
                'name' => (string) $r['display_name'],
                'profile_image_url' => avatar_public_url($r['avatar_path'] ?? null),
            ],
        ];
    }
    return $out;
}

function api_users_to_payload(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'username' => (string) $r['username'],
            'name' => (string) $r['display_name'],
            'profile_image_url' => avatar_public_url($r['avatar_path'] ?? null),
            'status' => [
                'text' => (string) ($r['current_status'] ?? ''),
            ],
        ];
    }
    return $out;
}

function api_send_json($payload): void
{
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_send_xml_statuses(array $rows): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<statuses>";
    foreach (api_statuses_to_payload($rows) as $s) {
        echo '<status>';
        echo '<id>' . (int) $s['id'] . '</id>';
        echo '<text>' . api_xml_text((string) $s['text']) . '</text>';
        echo '<source>' . api_xml_text((string) $s['source']) . '</source>';
        echo '<created_at>' . api_xml_text((string) $s['created_at']) . '</created_at>';
        echo '<user>';
        echo '<id>' . (int) $s['user']['id'] . '</id>';
        echo '<username>' . api_xml_text((string) $s['user']['username']) . '</username>';
        echo '<name>' . api_xml_text((string) $s['user']['name']) . '</name>';
        echo '<profile_image_url>' . api_xml_text((string) $s['user']['profile_image_url']) . '</profile_image_url>';
        echo '</user>';
        echo '</status>';
    }
    echo '</statuses>';
    exit;
}

function api_send_xml_users(array $rows): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<users>";
    foreach (api_users_to_payload($rows) as $u) {
        echo '<user>';
        echo '<id>' . (int) $u['id'] . '</id>';
        echo '<username>' . api_xml_text((string) $u['username']) . '</username>';
        echo '<name>' . api_xml_text((string) $u['name']) . '</name>';
        echo '<profile_image_url>' . api_xml_text((string) $u['profile_image_url']) . '</profile_image_url>';
        echo '<status><text>' . api_xml_text((string) $u['status']['text']) . '</text></status>';
        echo '</user>';
    }
    echo '</users>';
    exit;
}

$methodPath = api_get_method_path();
if ($methodPath === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'API method not found.';
    exit;
}

if (!preg_match('/^(public_timeline|friends_timeline|friends|followers|update)\.(json|xml)$/i', $methodPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'API method not found.';
    exit;
}
$methodKey = strtolower(preg_replace('/\.(json|xml)$/i', '', $methodPath));
$asXml = api_wants_xml($methodPath);

if ($methodKey === 'public_timeline') {
    $rows = public_timeline(20);
    if ($asXml) {
        api_send_xml_statuses($rows);
    }
    api_send_json(api_statuses_to_payload($rows));
}

$auth = api_auth_user_or_401();
$meId = (int) $auth['id'];

if ($methodKey === 'friends_timeline') {
    $friends = friends_accepted_list($meId);
    $ids = [$meId];
    foreach ($friends as $f) {
        $ids[] = (int) $f['id'];
    }
    $ids = array_values(array_unique($ids));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = app_pdo()->prepare(
        'SELECT s.id, s.user_id, s.body, s.source, s.created_at, u.username, u.display_name, u.avatar_path
         FROM statuses s
         INNER JOIN users u ON u.id = s.user_id
         WHERE s.user_id IN (' . $placeholders . ')
         ORDER BY s.created_at DESC, s.id DESC LIMIT 200'
    );
    $st->execute($ids);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($asXml) {
        api_send_xml_statuses($rows);
    }
    api_send_json(api_statuses_to_payload($rows));
}

if ($methodKey === 'friends') {
    $rows = friends_accepted_list($meId);
    $userRows = [];
    foreach ($rows as $r) {
        $u = user_by_id((int) $r['id']);
        if ($u !== null) {
            $userRows[] = $u;
        }
    }
    if ($asXml) {
        api_send_xml_users($userRows);
    }
    api_send_json(api_users_to_payload($userRows));
}

if ($methodKey === 'followers') {
    $rows = friend_requests_incoming($meId);
    $userRows = [];
    foreach ($rows as $r) {
        $u = user_by_id((int) $r['id']);
        if ($u !== null) {
            $userRows[] = $u;
        }
    }
    if ($asXml) {
        api_send_xml_users($userRows);
    }
    api_send_json(api_users_to_payload($userRows));
}

if ($methodKey === 'update') {
    $status = isset($_POST['status']) ? trim((string) $_POST['status']) : trim((string) ($_GET['status'] ?? ''));
    if ($status === '') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Missing status.';
        exit;
    }
    $len = function_exists('mb_strlen') ? mb_strlen($status, 'UTF-8') : strlen($status);
    if ($len > 500) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Status is too long (500 characters maximum).';
        exit;
    }
    $pdo = app_pdo();
    $pdo->prepare('INSERT INTO statuses (user_id, body, source, created_at) VALUES (?,?,?,datetime(\'now\'))')->execute([$meId, $status, 'api']);
    $pdo->prepare('UPDATE users SET current_status = ?, status_updated_at = datetime(\'now\') WHERE id = ?')->execute([$status, $meId]);
    if ($asXml) {
        header('Content-Type: application/xml; charset=UTF-8');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo '<response><ok>true</ok><message>Status updated.</message></response>';
        exit;
    }
    api_send_json([
        'ok' => true,
        'message' => 'Status updated.',
    ]);
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'API method not found.';
