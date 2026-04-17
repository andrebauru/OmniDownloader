<?php
/**
 * Teste de Download Múltiplo do Instagram
 * Use este script para diagnosticar problemas de múltiplos downloads
 */

require_once __DIR__ . '/config.php';

echo "=== OmniDownloader — Instagram Multiple Download Test ===\n\n";

// 1. Verificar arquivo de cookies
echo "1. Verificando arquivo de cookies...\n";
if (is_file(__DIR__ . '/cookies.txt')) {
    $size = filesize(__DIR__ . '/cookies.txt');
    $lines = count(file(__DIR__ . '/cookies.txt'));
    echo "   ✓ cookies.txt encontrado ($size bytes, $lines linhas)\n";
    
    // Verificar conteúdo mínimo
    $content = file_get_contents(__DIR__ . '/cookies.txt');
    if (strpos($content, 'instagram.com') !== false && strpos($content, 'sessionid') !== false) {
        echo "   ✓ Contém dados do Instagram (sessionid encontrado)\n";
    } else {
        echo "   ⚠ Arquivo não contém sessionid do Instagram\n";
    }
} else {
    echo "   ✗ cookies.txt NÃO encontrado\n";
    echo "   ⚠ CRÍTICO: Sem cookies.txt, Instagram bloqueará após 1º download\n";
    echo "   📖 Veja instruções em config.php\n";
}

// 2. Verificar sessão persistente
echo "\n2. Verificando sessão persistente...\n";
$sessionFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omnidownloader_instagram_session.txt';
if (is_file($sessionFile)) {
    $mtime = filemtime($sessionFile);
    $age = time() - $mtime;
    $hours = floor($age / 3600);
    $minutes = floor(($age % 3600) / 60);
    echo "   ✓ Arquivo de sessão encontrado\n";
    echo "   Idade: $hours h ${minutes} min\n";
    if ($age < 86400) {
        echo "   ✓ Ainda válido (< 24h)\n";
    } else {
        echo "   ✗ Expirado (> 24h)\n";
    }
} else {
    echo "   - Nenhuma sessão salva (será criada no primeiro download bem-sucedido)\n";
}

// 3. Verificar yt-dlp
echo "\n3. Verificando yt-dlp...\n";
$output = [];
$returnCode = 0;
exec('yt-dlp --version 2>&1', $output, $returnCode);
if ($returnCode === 0 && !empty($output)) {
    echo "   ✓ yt-dlp: " . trim($output[0]) . "\n";
} else {
    echo "   ✗ yt-dlp não encontrado\n";
}

// 4. Testar com URL do Instagram
echo "\n4. Instruções para testar múltiplos downloads:\n";
echo "   1. Certifique-se de que cookies.txt existe e é válido\n";
echo "   2. Faça primeiro download de um vídeo do Instagram\n";
echo "   3. Aguarde 2-3 segundos\n";
echo "   4. Faça segundo download (deve funcionar)\n";
echo "   5. Se falhar, verifique o arquivo de erro:\n";
echo "      " . sys_get_temp_dir() . "/omnidownloader_error.log\n";

echo "\n5. Problemas comuns:\n";
echo "   • \"Login required\" → cookies.txt inválido ou expirado\n";
echo "   • \"Rate limit\" → aguarde 5-10 minutos e tente novamente\n";
echo "   • \"Private account\" → a conta é privada, não há acesso\n";

echo "\n=== Fim do Teste ===\n";
?>
