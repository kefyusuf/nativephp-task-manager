<?php

/**
 * Live feed child process: polls public APIs and writes tasks.
 *
 * Runs under NativePHP's bundled PHP binary without booting Laravel.
 * Each loop fetches data; if the price changed, insert a row and echo a line.
 * STDOUT lines become MessageReceived events in the webview.
 *
 *   - Coinbase spot price API  → BTC/USD (no key)
 *   - open.er-api.com         → USD/TRY rate (no key)
 *
 * DB path: argv[1] or NATIVEPHP_DB_PATH (passed by NativeAppServiceProvider).
 * Falls back to database/nativephp.sqlite when neither is set.
 */
$dbPath = $argv[1]
    ?: (getenv('NATIVEPHP_DB_PATH') ?: __DIR__.'/../database/nativephp.sqlite');

if (! is_file($dbPath)) {
    fwrite(STDERR, "live-feed: database not found: {$dbPath}".PHP_EOL);
    exit(1);
}

$pdo = new PDO('sqlite:'.$dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

/** Simple GET + JSON decode; returns null on failure so the process never dies. */
function fetchJson(string $url): ?array
{
    $ctx = stream_context_create(['http' => ['timeout' => 8]]);
    $raw = @file_get_contents($url, false, $ctx);

    if ($raw === false) {
        return null;
    }

    $json = json_decode($raw, true);

    return is_array($json) ? $json : null;
}

$lastPrice = null;
$lastTryRate = null;
$i = 0;

while (true) {
    $btc = fetchJson('https://api.coinbase.com/v2/prices/BTC-USD/spot');
    $usdTry = fetchJson('https://open.er-api.com/v6/latest/USD');

    $price = isset($btc['data']['amount']) ? (float) $btc['data']['amount'] : null;

    if (isset($usdTry['rates']['TRY'])) {
        $lastTryRate = (float) $usdTry['rates']['TRY'];
    }

    if ($price === null || $price === $lastPrice) {
        if ($price === null) {
            echo json_encode(['title' => '📡 feed: API unreachable, will retry']), PHP_EOL;
        }

        sleep(30);

        continue;
    }

    $lastPrice = $price;
    $i++;

    $priceStr = '$'.number_format($price, 2);
    $title = "📡 BTC #{$i}: {$priceStr}";

    $body = 'Coinbase spot price — fetched by the live feed process.';
    if ($lastTryRate !== null) {
        $body .= ' USD/TRY: '.number_format($lastTryRate, 2)
            .' → BTC ≈ ₺'.number_format($price * $lastTryRate, 0);
    }

    $now = date('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO tasks (title, body, status, priority, due_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$title, $body, 'pending', 'low', null, $now, $now]);

    echo json_encode(['title' => $title, 'body' => $body]), PHP_EOL;

    sleep(30);
}
