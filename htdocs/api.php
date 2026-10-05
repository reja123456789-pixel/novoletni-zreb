<?php
// Novoletni žreb oblačil — API.  Klici: api.php?a=<akcija>

declare(strict_types=1);
date_default_timezone_set('Europe/Ljubljana');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$cfg = require __DIR__ . '/config.php';
require __DIR__ . '/mailer.php';

const SIZES = ['S', 'M', 'L', 'XL', 'XXL'];
const GENDERS = ['moški', 'ženska', 'drugo'];
const MAIL_BATCH = 5;

// ---------- pomožne ----------

function out(array $data, int $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $msg, int $status = 400)
{
    out(['error' => $msg], $status);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    global $cfg;
    $pdo = new PDO($cfg['db']['dsn'], $cfg['db']['user'] ?? null, $cfg['db']['pass'] ?? null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $charset = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' DEFAULT CHARSET=utf8mb4' : '';
    $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
        id VARCHAR(32) PRIMARY KEY,
        code VARCHAR(8) NOT NULL UNIQUE,
        name VARCHAR(40) NOT NULL,
        email VARCHAR(120) NOT NULL,
        size VARCHAR(4) NOT NULL,
        gender VARCHAR(10) NOT NULL,
        note VARCHAR(140) NOT NULL DEFAULT '',
        created_at VARCHAR(30) NOT NULL,
        target_id VARCHAR(32) NULL,
        mail_status VARCHAR(10) NOT NULL DEFAULT '',
        mail_error VARCHAR(255) NOT NULL DEFAULT ''
    )$charset");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (k VARCHAR(32) PRIMARY KEY, v TEXT NOT NULL)$charset");
    return $pdo;
}

function settings(): array
{
    $s = [
        'title' => 'Novoletni modni žreb',
        'drawDate' => '2026-12-01T20:00',
        'partyDate' => '2026-12-31T21:00',
        'drawDoneAt' => '',
    ];
    foreach (db()->query('SELECT k, v FROM settings') as $r) $s[$r['k']] = $r['v'];
    return $s;
}

function setSetting(string $k, string $v): void
{
    db()->prepare('REPLACE INTO settings (k, v) VALUES (?, ?)')->execute([$k, $v]);
}

function participants(): array
{
    return db()->query('SELECT * FROM participants ORDER BY created_at')->fetchAll();
}

function findBy(string $col, string $val): ?array
{
    $st = db()->prepare("SELECT * FROM participants WHERE $col = ?");
    $st->execute([$val]);
    return $st->fetch() ?: null;
}

function makeCode(): string
{
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // brez 0/O in 1/I
    do {
        $c = '';
        for ($i = 0; $i < 6; $i++) $c .= $abc[random_int(0, strlen($abc) - 1)];
        $c = substr($c, 0, 3) . '-' . substr($c, 3);
    } while (findBy('code', $c));
    return $c;
}

function fmtDate(string $s): string
{
    $t = strtotime($s);
    if (!$t) return $s;
    $m = ['januarja', 'februarja', 'marca', 'aprila', 'maja', 'junija', 'julija',
          'avgusta', 'septembra', 'oktobra', 'novembra', 'decembra'];
    return date('j', $t) . '. ' . $m[(int) date('n', $t) - 1] . ' ' . date('Y', $t) . ' ob ' . date('H:i', $t);
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function isAdmin(): bool
{
    return !empty($_SESSION['admin']);
}

function stats(array $people): array
{
    $bySize = array_fill_keys(SIZES, 0);
    $byGender = array_fill_keys(GENDERS, 0);
    foreach ($people as $p) {
        $bySize[$p['size']]++;
        $byGender[$p['gender']]++;
    }
    return ['count' => count($people), 'bySize' => $bySize, 'byGender' => $byGender];
}

// ---------- maili ----------

function mailLayout(string $title, string $body): string
{
    global $cfg;
    $url = h($cfg['site_url']);
    return <<<HTML
<div style="background:linear-gradient(120deg,#7b2ff7,#ff3d9a,#ff7a18);padding:30px 12px;font-family:Arial,Helvetica,sans-serif">
  <div style="max-width:520px;margin:0 auto;background:#fff;border:4px solid #1b1036;border-radius:20px;padding:26px;color:#1b1036">
    <div style="font-size:26px;font-weight:bold;margin-bottom:14px">🎉 $title</div>
    $body
    <p style="margin-top:24px;font-size:13px;color:#666">🎆 <a href="$url" style="color:#7b2ff7">$url</a></p>
  </div>
</div>
HTML;
}

function confirmMail(array $p, array $s): array
{
    $body = '<p style="font-size:16px">Živjo <b>' . h($p['name']) . '</b>! 👋</p>'
        . '<p style="font-size:16px">Uspešno si se prijavil/a v žreb. Tvoji podatki: velikost <b>' . h($p['size'])
        . '</b>, spol <b>' . h($p['gender']) . '</b>.</p>'
        . '<div style="background:#1b1036;color:#ffd23f;border-radius:14px;padding:16px;text-align:center;font-size:28px;font-weight:bold;letter-spacing:3px">'
        . h($p['code']) . '</div>'
        . '<p style="font-size:15px">Žreb bo <b>' . h(fmtDate($s['drawDate'])) . '</b>. Takrat ti pošljemo mail s tem, koga oblačiš. '
        . 'Rezultat lahko s to kodo preveriš tudi na strani.</p>';
    return ['✅ Prijava v ' . $s['title'], mailLayout(h($s['title']), $body)];
}

function resultMail(array $p, array $t, array $s): array
{
    $note = $t['note'] !== '' ? '<p style="font-size:15px">💬 Opomba: „' . h($t['note']) . '”</p>' : '';
    $body = '<p style="font-size:16px">' . h($p['name']) . ', žreb je izveden! 🎰 Ti oblačiš …</p>'
        . '<div style="background:#fff7d6;border:3px dashed #1b1036;border-radius:14px;padding:18px;text-align:center">'
        . '<div style="font-size:32px;font-weight:bold;color:#ff3d9a">' . h($t['name']) . '</div>'
        . '<div style="font-size:17px;margin-top:8px">👕 velikost <b>' . h($t['size']) . '</b> · spol <b>' . h($t['gender']) . '</b></div>'
        . '</div>' . $note
        . '<p style="font-size:15px">Outfit prinesi na žurko <b>' . h(fmtDate($s['partyDate'])) . '</b>. 🤫 Ne povej nikomur!</p>';
    return ['🎁 Koga oblačiš? — ' . $s['title'], mailLayout(h($s['title']), $body)];
}

// ---------- usmerjanje ----------

session_start();
$a = $_GET['a'] ?? '';
$in = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

try {
    if ($a === 'status') {
        $s = settings();
        $count = (int) db()->query('SELECT COUNT(*) FROM participants')->fetchColumn();
        out([
            'settings' => $s,
            'count' => $count,
            'drawDone' => $s['drawDoneAt'] !== '',
            'sizes' => SIZES,
            'genders' => GENDERS,
        ]);
    }

    if (!$isPost) fail('Neznana zahteva.', 404);

    if ($a === 'register') {
        $s = settings();
        if ($s['drawDoneAt'] !== '') fail('Žreb je že bil izveden, prijave so zaprte.');
        $name = mb_substr(trim((string) ($in['name'] ?? '')), 0, 40);
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $note = mb_substr(trim((string) ($in['note'] ?? '')), 0, 140);
        $size = (string) ($in['size'] ?? '');
        $gender = (string) ($in['gender'] ?? '');

        if (mb_strlen($name) < 2) fail('Vpiši svoje ime.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) fail('Vpiši veljaven e-mail.');
        if (!in_array($size, SIZES, true)) fail('Izberi velikost.');
        if (!in_array($gender, GENDERS, true)) fail('Izberi spol.');

        $st = db()->prepare('SELECT COUNT(*) FROM participants WHERE LOWER(name) = LOWER(?)');
        $st->execute([$name]);
        if ($st->fetchColumn()) fail('To ime je že prijavljeno. Dodaj priimek ali vzdevek.');
        if (findBy('email', $email)) fail('S tem mailom je nekdo že prijavljen.');

        $p = [
            'id' => bin2hex(random_bytes(16)),
            'code' => makeCode(),
            'name' => $name,
            'email' => $email,
            'size' => $size,
            'gender' => $gender,
            'note' => $note,
            'created_at' => date('c'),
        ];
        db()->prepare('INSERT INTO participants (id, code, name, email, size, gender, note, created_at)
                       VALUES (:id, :code, :name, :email, :size, :gender, :note, :created_at)')->execute($p);

        // potrditveni mail — prijava velja, tudi če mail ne uspe
        $mailSent = true;
        try {
            $smtp = new Smtp($cfg['mail']);
            [$subj, $html] = confirmMail($p, $s);
            $smtp->send($email, $subj, $html);
            $smtp->close();
        } catch (Throwable $e) {
            $mailSent = false;
        }
        out(['code' => $p['code'], 'name' => $name, 'mailSent' => $mailSent]);
    }

    if ($a === 'result') {
        $me = findBy('code', strtoupper(trim((string) ($in['code'] ?? ''))));
        if (!$me) fail('Te kode ne poznamo. Preveri, ali si jo pravilno vpisal/a.', 404);
        $s = settings();
        if (!$me['target_id']) out(['name' => $me['name'], 'drawDone' => false, 'drawDate' => $s['drawDate']]);
        $t = findBy('id', $me['target_id']);
        out([
            'name' => $me['name'],
            'drawDone' => true,
            'target' => $t ? ['name' => $t['name'], 'size' => $t['size'], 'gender' => $t['gender'], 'note' => $t['note']] : null,
        ]);
    }

    // ----- admin -----

    if ($a === 'login') {
        if (!hash_equals((string) $cfg['admin_password'], (string) ($in['password'] ?? ''))) {
            sleep(1);
            fail('Napačno geslo.', 401);
        }
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        out(['ok' => true]);
    }

    if ($a === 'logout') {
        $_SESSION = [];
        session_destroy();
        out(['ok' => true]);
    }

    if (!isAdmin()) fail('Prijava potrebna.', 401);

    if ($a === 'overview') {
        $people = participants();
        $byId = array_column($people, null, 'id');
        $s = settings();
        $pairs = [];
        foreach ($people as $p) {
            if ($p['target_id'] && isset($byId[$p['target_id']])) {
                $t = $byId[$p['target_id']];
                $pairs[] = ['giver' => $p['name'], 'receiver' => $t['name'], 'size' => $t['size'], 'gender' => $t['gender']];
            }
        }
        out([
            'settings' => $s,
            'stats' => stats($people),
            'participants' => array_map(fn($p) => [
                'id' => $p['id'], 'name' => $p['name'], 'email' => $p['email'], 'size' => $p['size'],
                'gender' => $p['gender'], 'note' => $p['note'], 'createdAt' => $p['created_at'],
                'mailStatus' => $p['mail_status'], 'mailError' => $p['mail_error'],
            ], $people),
            'draw' => $s['drawDoneAt'] !== '' ? ['doneAt' => $s['drawDoneAt'], 'pairs' => $pairs] : null,
            'mailFrom' => $cfg['mail']['user'],
        ]);
    }

    if ($a === 'settings') {
        foreach (['title', 'drawDate', 'partyDate'] as $k) {
            if (isset($in[$k]) && is_string($in[$k])) setSetting($k, mb_substr($in[$k], 0, 80));
        }
        out(['ok' => true]);
    }

    if ($a === 'draw') {
        if (settings()['drawDoneAt'] !== '') fail('Žreb je že izveden. Najprej ga ponastavi.');
        $ids = array_column(participants(), 'id');
        if (count($ids) < 3) fail('Za žreb potrebuješ vsaj 3 udeležence.');

        // Fisher–Yates, nato krog: vsak dobi naslednjega → nihče ne dobi sebe
        for ($i = count($ids) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        }
        $pdo = db();
        $pdo->beginTransaction();
        $st = $pdo->prepare("UPDATE participants SET target_id = ?, mail_status = 'pending', mail_error = '' WHERE id = ?");
        foreach ($ids as $i => $id) $st->execute([$ids[($i + 1) % count($ids)], $id]);
        setSetting('drawDoneAt', date('c'));
        $pdo->commit();
        out(['ok' => true]);
    }

    // Pošlje nekaj čakajočih mailov naenkrat; admin stran kliče, dokler ni vseh.
    if ($a === 'send_batch') {
        $s = settings();
        $byId = array_column(participants(), null, 'id');
        $pending = array_values(array_filter($byId, fn($p) => $p['mail_status'] === 'pending' && $p['target_id']));
        $batch = array_slice($pending, 0, MAIL_BATCH);
        $upd = db()->prepare('UPDATE participants SET mail_status = ?, mail_error = ? WHERE id = ?');
        $smtp = new Smtp($cfg['mail']);
        $sent = 0;
        foreach ($batch as $p) {
            try {
                [$subj, $html] = resultMail($p, $byId[$p['target_id']], $s);
                $smtp->send($p['email'], $subj, $html);
                $upd->execute(['sent', '', $p['id']]);
                $sent++;
            } catch (Throwable $e) {
                $upd->execute(['error', mb_substr($e->getMessage(), 0, 250), $p['id']]);
            }
        }
        $smtp->close();
        out(['sent' => $sent, 'failed' => count($batch) - $sent, 'remaining' => count($pending) - count($batch)]);
    }

    if ($a === 'resend') {
        if (settings()['drawDoneAt'] === '') fail('Žreb še ni izveden.');
        if (!empty($in['id'])) {
            db()->prepare("UPDATE participants SET mail_status = 'pending' WHERE id = ? AND target_id IS NOT NULL")->execute([$in['id']]);
        } else {
            db()->exec("UPDATE participants SET mail_status = 'pending' WHERE mail_status = 'error' AND target_id IS NOT NULL");
        }
        out(['ok' => true]);
    }

    if ($a === 'test_mail') {
        $to = $cfg['mail']['user'];
        $smtp = new Smtp($cfg['mail']);
        $smtp->send($to, '🧪 Testni mail — ' . settings()['title'],
            mailLayout('Testni mail', '<p style="font-size:16px">Če to bereš, pošiljanje mailov deluje. 🎉</p>'));
        $smtp->close();
        out(['ok' => true, 'to' => $to]);
    }

    if ($a === 'reset_draw') {
        db()->exec("UPDATE participants SET target_id = NULL, mail_status = '', mail_error = ''");
        setSetting('drawDoneAt', '');
        out(['ok' => true]);
    }

    if ($a === 'remove') {
        if (settings()['drawDoneAt'] !== '') fail('Po žrebu ne moreš odstranjevati udeležencev. Najprej ponastavi žreb.');
        db()->prepare('DELETE FROM participants WHERE id = ?')->execute([(string) ($in['id'] ?? '')]);
        out(['ok' => true]);
    }

    if ($a === 'wipe') {
        db()->exec('DELETE FROM participants');
        db()->exec('DELETE FROM settings');
        out(['ok' => true]);
    }

    fail('Neznana zahteva.', 404);
} catch (PDOException $e) {
    fail('Napaka baze podatkov. Preveri nastavitve v config.php.', 500);
} catch (Throwable $e) {
    fail($e->getMessage(), 500);
}
