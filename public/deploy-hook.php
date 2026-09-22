<?php
/**
 * Recebe um POST com o token certo e extrai ../_deploy.zip (enviado por FTP antes)
 * por cima da aplicação. Existe pra evitar mandar milhares de arquivos um por um
 * pelo FTP — só sobe um zip e o próprio servidor descompacta.
 *
 * O token fica guardado em ../.deploy-secret (fora do public/, nunca acessível
 * pela web), com o mesmo valor do secret DEPLOY_TOKEN no GitHub.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$secretFile = __DIR__.'/../.deploy-secret';

if (! is_file($secretFile)) {
    http_response_code(500);
    exit('Sem arquivo de segredo configurado no servidor.');
}

$expected = trim((string) file_get_contents($secretFile));
// Manda no corpo do POST (campo "token"), não num header customizado — alguns
// firewalls (Mod_Security) desconfiam de headers fora do padrão.
$given = trim((string) ($_POST['token'] ?? $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? ''));

if ($expected === '' || ! hash_equals($expected, $given)) {
    http_response_code(403);
    // Diagnostico temporario: mostra só tamanho e pontas, nunca o valor inteiro.
    $mask = fn (string $s) => $s === '' ? '(vazio)' : strlen($s).' chars, comeca "'.substr($s, 0, 4).'" termina "'.substr($s, -4).'"';
    exit("Token invalido.\nEsperado: {$mask($expected)}\nRecebido: {$mask($given)}\nPOST bruto: ".substr(file_get_contents('php://input'), 0, 200));
}

$zipPath = __DIR__.'/../_deploy.zip';

if (! is_file($zipPath)) {
    http_response_code(404);
    exit('Nenhum _deploy.zip encontrado em ../.');
}

$zip = new ZipArchive;
$open = $zip->open($zipPath);

if ($open !== true) {
    http_response_code(500);
    exit('Falha ao abrir o zip (codigo '.$open.').');
}

$destination = dirname(__DIR__); // pasta "agenda", raiz da aplicação
$ok = $zip->extractTo($destination);
$zip->close();

if (! $ok) {
    http_response_code(500);
    exit('Falha ao extrair o zip.');
}

unlink($zipPath);

echo 'OK - implantado com sucesso em '.date('Y-m-d H:i:s');
