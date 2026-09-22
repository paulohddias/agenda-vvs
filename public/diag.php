<?php
// Diagnostico temporario - apague este arquivo depois de usar.

$headersSentBefore = headers_sent($f1, $l1);

$cookieOk = setcookie('diag_test', 'valor123', ['expires' => time() + 300, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);

$headersSentAfterCookie = headers_sent($f2, $l2);

header('Content-Type: text/plain; charset=utf-8');

echo "PHP version: " . PHP_VERSION . "\n";
echo "headers_sent() antes de qualquer coisa: " . ($headersSentBefore ? "SIM (em $f1:$l1)" : "nao") . "\n\n";

echo "--- Tentando enviar um cookie de teste (antes de qualquer output) ---\n";
echo "setcookie() retornou: " . ($cookieOk ? "true" : "false") . "\n";
echo "headers_sent() depois do setcookie(): " . ($headersSentAfterCookie ? "SIM (em $f2:$l2)" : "nao") . "\n\n";

echo "--- Cookies recebidos nesta requisicao ---\n";
print_r($_COOKIE);

echo "\n--- session_start() nativo ---\n";
$sessionOk = @session_start();
echo "session_start() retornou: " . ($sessionOk ? "true" : "false") . "\n";
echo "session_id(): " . session_id() . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";

echo "\n--- Extensoes carregadas relevantes ---\n";
foreach (['pdo_mysql', 'session', 'openssl', 'mbstring'] as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "sim" : "NAO CARREGADA") . "\n";
}

echo "\n--- output_buffering (php.ini) ---\n";
echo "output_buffering: " . ini_get('output_buffering') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
