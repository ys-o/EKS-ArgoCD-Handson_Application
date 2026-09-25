<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/status.php';
header('Cache-Control: no-store');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!in_array($method, ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}
if (!in_array($path, ['/', '/health', '/ready'], true)) {
    http_response_code(404);
    exit;
}
if ($path === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo '{"status":"ok","service":"php"}';
    exit;
}
$db = databaseStatus();
http_response_code($db['ok'] ? 200 : 503);
if ($path === '/ready') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $db['ok'] ? 'ready' : 'unavailable']);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Three Layer / EKS Lab</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<main>
    <header><a class="brand" href="/">THREE LAYER <span>/ EKS LAB</span></a><span class="tag">LIVE CHECK</span></header>
    <section class="intro">
        <p class="eyebrow">FROM REQUEST TO DATABASE</p>
        <h1>ひとつのリクエスト。<br>3つのレイヤー。</h1>
        <p class="lead">NginxからPHP、そしてMySQLへ。<br>このページを開いた時点の疎通結果を表示します。</p>
    </section>
    <section class="summary <?= $db['ok'] ? 'success' : 'failure' ?>" aria-live="polite">
        <span class="signal"></span>
        <div><strong><?= $db['ok'] ? 'すべてのレイヤーが応答しています' : 'データベースへの接続を確認してください' ?></strong>
        <p><?= $db['ok'] ? 'HTTP → FastCGI → SQL の疎通を確認しました。' : 'PHPは動作しています。DB設定、接続経路、認証情報を確認してください。' ?></p></div>
        <span class="status-code"><?= $db['ok'] ? '200 OK' : '503 UNAVAILABLE' ?></span>
    </section>
    <section class="layers" aria-label="各レイヤーの状態">
        <article><div class="layer-top"><span>01 / WEB</span><b class="good">応答中</b></div><h2>Nginx</h2><p>HTTPリクエストの受付と<br>PHP-FPMへの転送</p><dl><dt>INTERFACE</dt><dd>HTTP → FastCGI</dd></dl></article>
        <article><div class="layer-top"><span>02 / APPLICATION</span><b class="good">応答中</b></div><h2>PHP</h2><p>画面の生成と<br>データベースへの問い合わせ</p><dl><dt>RUNTIME VERSION</dt><dd><?= escape(PHP_VERSION) ?></dd></dl></article>
        <article><div class="layer-top"><span>03 / DATABASE</span><b class="<?= $db['ok'] ? 'good' : 'bad' ?>"><?= $db['ok'] ? '接続成功' : '接続失敗' ?></b></div><h2>MySQL</h2><p>PDO経由の接続と<br><code>SELECT VERSION()</code>の実行</p><dl><dt>DATABASE VERSION</dt><dd><?= escape($db['version'] ?? '取得できません') ?></dd></dl></article>
    </section>
    <section class="details"><div><span>RELEASE</span><strong><?= escape(setting('APP_VERSION', 'development')) ?></strong></div><div><span>DB TRANSPORT</span><strong><?= $db['ok'] ? ($db['tls'] ? 'TLS / ' . escape($db['tls']) : 'TLSなし（ローカル向け）') : '未確認' ?></strong></div><div><span>CHECKED AT / UTC</span><strong><?= escape(gmdate('Y-m-d H:i:s')) ?></strong></div></section>
    <footer><span>Original learning application · Nginx / PHP-FPM / MySQL</span><a href="/">再チェック ↗</a></footer>
</main>
</body>
</html>
