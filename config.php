<?php
/**
 * OmniDownloader Configuration
 * 
 * Instruções para múltiplos downloads do Instagram:
 * 
 * 1. CRIAR ARQUIVO cookies.txt na raiz do projeto:
 *    - Faça login no Instagram via browser
 *    - Instale extensão "EditThisCookie"
 *    - Clique em exportar e salve como "cookies.txt" na raiz do OmniDownloader
 *    
 * 2. FORMATO DO ARQUIVO:
 *    # Netscape HTTP Cookie File
 *    instagram.com	TRUE	/	FALSE	1735689600	sessionid	VALOR_AQUI
 *    instagram.com	TRUE	/	FALSE	1735689600	ds_user_id	NUMERO_AQUI
 *    instagram.com	TRUE	/	FALSE	1735689600	mid	VALOR_AQUI
 *    
 * 3. TESTE:
 *    - Com cookies.txt na raiz, downloads consecutivos funcionarão
 *    - A sessão persiste por 24h automaticamente
 *    
 * NOTA: Sem um arquivo cookies.txt válido, Instagram bloqueará após o primeiro download
 */

// Configurações de Instagram
define('INSTAGRAM_SESSION_TTL', 86400); // 24 horas em segundos
define('INSTAGRAM_MIN_DELAY', 1);      // 1 segundo entre tentativas
define('INSTAGRAM_MAX_DELAY', 3);      // 3 segundos máximo
define('INSTAGRAM_MAX_RETRIES', 4);    // Máximo de tentativas

// Configurações de Upload
define('MAX_FILE_SIZE', 52428800); // 50MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_DIR', __DIR__ . '/storage/uploads/');

// Criar diretório de uploads se não existir
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
?>
