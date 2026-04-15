<?php
/**
 * OmniDownloader — Main Page
 * Multi-language: pt-BR (default), en, es, ja
 */

// ---- Language Detection ------------------------------------------------- //

$supported = ['pt', 'en', 'es', 'ja'];
$lang = 'pt';

if (isset($_GET['lang']) && in_array($_GET['lang'], $supported, true)) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $supported, true)) {
    $lang = $_COOKIE['lang'];
} else {
    $al = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    if     (preg_match('/\bja\b/', $al)) $lang = 'ja';
    elseif (preg_match('/\bes\b/', $al)) $lang = 'es';
    elseif (preg_match('/\ben\b/', $al)) $lang = 'en';
}

// ---- Translations ------------------------------------------------------- //

$langs = [
    'pt' => [
        'lang_attr'   => 'pt-BR',
        'html_title'  => 'OmniDownloader — Baixar Vídeos e Músicas do YouTube, TikTok e Instagram',
        'meta_desc'   => 'Baixe vídeos em MP4 e músicas em MP3 do YouTube, TikTok, Instagram, Twitter, Facebook e mais de 1000 plataformas. Grátis, rápido e sem instalar nada.',
        'meta_kw'     => 'baixar vídeo youtube, baixar mp3 youtube, download youtube, baixar tiktok, baixar instagram, youtube downloader, conversor youtube mp3, baixar música grátis, download vídeo online, yt-dlp online',
        'og_title'    => 'OmniDownloader — Baixar Vídeos e Músicas Online',
        'og_desc'     => 'Baixe vídeos em MP4 e músicas em MP3 do YouTube, TikTok, Instagram e mais de 1000 sites. Grátis, sem cadastro, funciona no celular.',
        'og_locale'   => 'pt_BR',
        'hero_title'  => 'Baixe vídeos e músicas',
        'hero_sub'    => 'YouTube, TikTok, Instagram e mais de 1000 plataformas',
        'stats_label' => 'downloads realizados',
        'input_ph'    => 'Digite para buscar ou cole um link...',
        'paste_btn'   => 'Colar',
        'aria_paste'  => 'Colar da área de transferência',
        'aria_clear'  => 'Limpar campo',
        'aria_input'  => 'Busca ou URL do vídeo',
        'fmt_vl'      => 'Vídeo',
        'fmt_vd'      => 'MP4 · Melhor qualidade',
        'fmt_al'      => 'Áudio',
        'fmt_ad'      => 'MP3 · 192 kbps',
        'fmt_aria'    => 'Formato de download',
        'fmt_legend'  => 'Escolha o formato',
        'dl_btn'      => 'Baixar Agora',
        'loading_t'   => 'Processando download...',
        'stage1'      => 'Conectando ao servidor...',
        'stage2'      => 'Obtendo informações do vídeo...',
        'stage3'      => 'Baixando arquivo...',
        'stage4'      => 'Preparando para download...',
        'loading_w'   => 'Por favor, aguarde.',
        'err_def'     => 'Ocorreu um erro ao processar o download.',
        'try_again'   => 'Tentar novamente',
        'plat_label'  => 'Plataformas suportadas',
        'faq_title'   => 'Perguntas Frequentes',
        'faq_q1'      => 'Como baixar vídeos do YouTube?',
        'faq_a1'      => 'Cole o link do vídeo do YouTube no campo acima, selecione o formato <strong>Vídeo MP4</strong>, clique em <strong>Baixar Agora</strong> e escolha onde salvar.',
        'faq_q2'      => 'Como converter YouTube para MP3?',
        'faq_a2'      => 'Cole o link do YouTube, selecione <strong>Áudio MP3</strong> e clique em <strong>Baixar Agora</strong>. O áudio é extraído em 192 kbps automaticamente.',
        'faq_q3'      => 'Funciona para TikTok e Instagram?',
        'faq_a3'      => 'Sim! Cole o link de qualquer vídeo do TikTok ou Instagram. O OmniDownloader suporta mais de 1000 plataformas via yt-dlp.',
        'faq_q4'      => 'O download é gratuito e sem cadastro?',
        'faq_a4'      => 'Sim, o OmniDownloader é 100% gratuito, não exige cadastro, instalação de aplicativos ou extensões de navegador.',
        'faq_q5'      => 'Posso pesquisar vídeos sem ter o link?',
        'faq_a5'      => 'Sim! Digite o nome do vídeo ou artista. O OmniDownloader buscará no YouTube e mostrará resultados com thumbnail e duração.',
        'footer_copy' => 'Todos os direitos reservados',
        'footer_disc' => 'Este site não armazena vídeos. Todo o conteúdo é de responsabilidade de seus respectivos donos.',
        'prev_page'   => 'Anterior',
        'next_page'   => 'Próxima',
        'dl_result'   => 'Baixar',
        'srch_load'   => 'Buscando "%s"...',
        'srch_empty'  => 'Nenhum resultado para "%s".',
        'srch_res'    => 'Resultados para "%s" · %d encontrados',
        'srch_err'    => 'Erro ao buscar. Verifique se yt-dlp está instalado.',
        'page_info'   => 'Página %d de %d',
        'timeout_e'   => "O download está demorando mais que o esperado.\nVerifique se a URL é válida e tente novamente.",
        'cookie_hint' => 'YouTube: o servidor tenta cookies automaticamente para contornar bloqueios anti-bot. Se ainda falhar, pode ser limitação temporária do próprio YouTube.',
        'app_name'    => 'OmniDownloader',
        'app_desc_ld' => 'Baixe vídeos em MP4 e músicas em MP3 do YouTube, TikTok, Instagram, Twitter, Facebook e mais de 1000 plataformas.',
        'features'    => ['Download de vídeos do YouTube em MP4','Download de músicas do YouTube em MP3','Download de vídeos do TikTok','Download de vídeos do Instagram','Suporte a mais de 1000 plataformas','Sem necessidade de cadastro','Interface responsiva para celular'],
        'ld_faq'      => [
            ['q'=>'Como baixar vídeos do YouTube?','a'=>'Cole o link do vídeo do YouTube no campo de busca, selecione MP4, clique em Baixar Agora e escolha onde salvar.'],
            ['q'=>'Como converter YouTube para MP3?','a'=>'Cole o link do YouTube, selecione Áudio MP3 · 192 kbps e clique em Baixar Agora.'],
            ['q'=>'Quais plataformas são suportadas?','a'=>'YouTube, TikTok, Instagram, Twitter/X, Facebook, Twitch, SoundCloud e mais de 1000 plataformas via yt-dlp.'],
            ['q'=>'O download é gratuito?','a'=>'Sim, o OmniDownloader é totalmente gratuito e não exige cadastro ou instalação.'],
        ],
    ],
    'en' => [
        'lang_attr'   => 'en',
        'html_title'  => 'OmniDownloader — Download Videos & Music from YouTube, TikTok & Instagram',
        'meta_desc'   => 'Download MP4 videos and MP3 music from YouTube, TikTok, Instagram, Twitter, Facebook and 1000+ platforms. Free, fast, no installation needed.',
        'meta_kw'     => 'download youtube video, youtube to mp3, youtube downloader, download tiktok, download instagram, video downloader online, free youtube converter, download music free',
        'og_title'    => 'OmniDownloader — Download Videos & Music Online',
        'og_desc'     => 'Download MP4 videos and MP3 music from YouTube, TikTok, Instagram and 1000+ sites. Free, no sign-up, works on mobile.',
        'og_locale'   => 'en_US',
        'hero_title'  => 'Download videos and music',
        'hero_sub'    => 'YouTube, TikTok, Instagram and 1000+ platforms',
        'stats_label' => 'downloads completed',
        'input_ph'    => 'Search or paste a link...',
        'paste_btn'   => 'Paste',
        'aria_paste'  => 'Paste from clipboard',
        'aria_clear'  => 'Clear field',
        'aria_input'  => 'Search or video URL',
        'fmt_vl'      => 'Video',
        'fmt_vd'      => 'MP4 · Best quality',
        'fmt_al'      => 'Audio',
        'fmt_ad'      => 'MP3 · 192 kbps',
        'fmt_aria'    => 'Download format',
        'fmt_legend'  => 'Choose format',
        'dl_btn'      => 'Download Now',
        'loading_t'   => 'Processing download...',
        'stage1'      => 'Connecting to server...',
        'stage2'      => 'Fetching video info...',
        'stage3'      => 'Downloading file...',
        'stage4'      => 'Preparing file...',
        'loading_w'   => 'Please wait.',
        'err_def'     => 'An error occurred while processing the download.',
        'try_again'   => 'Try again',
        'plat_label'  => 'Supported platforms',
        'faq_title'   => 'Frequently Asked Questions',
        'faq_q1'      => 'How to download YouTube videos?',
        'faq_a1'      => 'Paste the YouTube link above, select <strong>Video MP4</strong>, click <strong>Download Now</strong> and choose where to save.',
        'faq_q2'      => 'How to convert YouTube to MP3?',
        'faq_a2'      => 'Paste the YouTube link, select <strong>Audio MP3</strong> and click <strong>Download Now</strong>. Audio is extracted at 192 kbps automatically.',
        'faq_q3'      => 'Does it work for TikTok and Instagram?',
        'faq_a3'      => 'Yes! Paste any TikTok or Instagram video link. OmniDownloader supports 1000+ platforms via yt-dlp.',
        'faq_q4'      => 'Is it free and without registration?',
        'faq_a4'      => 'Yes, OmniDownloader is 100% free, requires no registration, app installation or browser extensions.',
        'faq_q5'      => 'Can I search for videos without a link?',
        'faq_a5'      => 'Yes! Just type the video name or artist. OmniDownloader will search YouTube and show results with thumbnails and duration.',
        'footer_copy' => 'All rights reserved',
        'footer_disc' => 'This site does not store videos. All content belongs to their respective owners.',
        'prev_page'   => 'Previous',
        'next_page'   => 'Next',
        'dl_result'   => 'Download',
        'srch_load'   => 'Searching "%s"...',
        'srch_empty'  => 'No results for "%s".',
        'srch_res'    => 'Results for "%s" · %d found',
        'srch_err'    => 'Search error. Check if yt-dlp is installed.',
        'page_info'   => 'Page %d of %d',
        'timeout_e'   => "Download is taking longer than expected.\nCheck if the URL is valid and try again.",
        'cookie_hint' => 'YouTube: the server automatically tries browser cookies to bypass anti-bot checks. If it still fails, this may be a temporary YouTube limitation.',
        'app_name'    => 'OmniDownloader',
        'app_desc_ld' => 'Download MP4 videos and MP3 music from YouTube, TikTok, Instagram, Twitter, Facebook and 1000+ platforms. Free, fast, no installation needed.',
        'features'    => ['YouTube video download in MP4','YouTube music download in MP3','TikTok video download','Instagram video download','1000+ platform support','No sign-up required','Responsive mobile interface'],
        'ld_faq'      => [
            ['q'=>'How to download YouTube videos?','a'=>'Paste the YouTube link in the search field, select MP4 format and click Download Now.'],
            ['q'=>'How to convert YouTube to MP3?','a'=>'Paste the YouTube link, select Audio MP3 · 192 kbps and click Download Now.'],
            ['q'=>'Which platforms are supported?','a'=>'YouTube, TikTok, Instagram, Twitter/X, Facebook, Twitch, SoundCloud and 1000+ platforms via yt-dlp.'],
            ['q'=>'Is the download free?','a'=>'Yes, OmniDownloader is completely free and requires no registration or software installation.'],
        ],
    ],
    'es' => [
        'lang_attr'   => 'es',
        'html_title'  => 'OmniDownloader — Descargar Vídeos y Música de YouTube, TikTok e Instagram',
        'meta_desc'   => 'Descarga vídeos en MP4 y música en MP3 de YouTube, TikTok, Instagram, Twitter, Facebook y más de 1000 plataformas. Gratis, rápido y sin instalación.',
        'meta_kw'     => 'descargar vídeo youtube, youtube a mp3, descargador youtube, descargar tiktok, descargar instagram, descargador de vídeos online, convertidor youtube mp3, descargar música gratis',
        'og_title'    => 'OmniDownloader — Descargar Vídeos y Música Online',
        'og_desc'     => 'Descarga vídeos en MP4 y música en MP3 de YouTube, TikTok, Instagram y más de 1000 sitios. Gratis, sin registro, funciona en el móvil.',
        'og_locale'   => 'es_ES',
        'hero_title'  => 'Descarga vídeos y música',
        'hero_sub'    => 'YouTube, TikTok, Instagram y más de 1000 plataformas',
        'stats_label' => 'descargas realizadas',
        'input_ph'    => 'Escribe para buscar o pega un enlace...',
        'paste_btn'   => 'Pegar',
        'aria_paste'  => 'Pegar del portapapeles',
        'aria_clear'  => 'Limpiar campo',
        'aria_input'  => 'Búsqueda o URL del vídeo',
        'fmt_vl'      => 'Vídeo',
        'fmt_vd'      => 'MP4 · Mejor calidad',
        'fmt_al'      => 'Audio',
        'fmt_ad'      => 'MP3 · 192 kbps',
        'fmt_aria'    => 'Formato de descarga',
        'fmt_legend'  => 'Elige el formato',
        'dl_btn'      => 'Descargar Ahora',
        'loading_t'   => 'Procesando descarga...',
        'stage1'      => 'Conectando al servidor...',
        'stage2'      => 'Obteniendo información del vídeo...',
        'stage3'      => 'Descargando archivo...',
        'stage4'      => 'Preparando descarga...',
        'loading_w'   => 'Por favor, espera.',
        'err_def'     => 'Ocurrió un error al procesar la descarga.',
        'try_again'   => 'Intentar de nuevo',
        'plat_label'  => 'Plataformas compatibles',
        'faq_title'   => 'Preguntas Frecuentes',
        'faq_q1'      => '¿Cómo descargar vídeos de YouTube?',
        'faq_a1'      => 'Pega el enlace de YouTube arriba, selecciona <strong>Vídeo MP4</strong>, haz clic en <strong>Descargar Ahora</strong> y elige dónde guardar.',
        'faq_q2'      => '¿Cómo convertir YouTube a MP3?',
        'faq_a2'      => 'Pega el enlace de YouTube, selecciona <strong>Audio MP3</strong> y haz clic en <strong>Descargar Ahora</strong>. El audio se extrae a 192 kbps automáticamente.',
        'faq_q3'      => '¿Funciona para TikTok e Instagram?',
        'faq_a3'      => '¡Sí! Pega el enlace de TikTok o Instagram. OmniDownloader soporta más de 1000 plataformas vía yt-dlp.',
        'faq_q4'      => '¿Es gratuito y sin registro?',
        'faq_a4'      => 'Sí, OmniDownloader es 100% gratuito, no requiere registro, instalación de aplicaciones ni extensiones de navegador.',
        'faq_q5'      => '¿Puedo buscar vídeos sin tener el enlace?',
        'faq_a5'      => '¡Sí! Escribe el nombre del vídeo o artista. OmniDownloader buscará en YouTube y mostrará resultados con miniatura y duración.',
        'footer_copy' => 'Todos los derechos reservados',
        'footer_disc' => 'Este sitio no almacena vídeos. Todo el contenido pertenece a sus respectivos dueños.',
        'prev_page'   => 'Anterior',
        'next_page'   => 'Siguiente',
        'dl_result'   => 'Descargar',
        'srch_load'   => 'Buscando "%s"...',
        'srch_empty'  => 'Sin resultados para "%s".',
        'srch_res'    => 'Resultados para "%s" · %d encontrados',
        'srch_err'    => 'Error de búsqueda. Verifica si yt-dlp está instalado.',
        'page_info'   => 'Página %d de %d',
        'timeout_e'   => "La descarga está tardando más de lo esperado.\nVerifica si la URL es válida e inténtalo de nuevo.",
        'cookie_hint' => 'YouTube: el servidor intenta cookies automáticamente para evitar bloqueos anti-bot. Si aún falla, puede ser una limitación temporal de YouTube.',
        'app_name'    => 'OmniDownloader',
        'app_desc_ld' => 'Descarga vídeos en MP4 y música en MP3 de YouTube, TikTok, Instagram, Twitter, Facebook y más de 1000 plataformas. Gratis, rápido y sin instalación.',
        'features'    => ['Descarga de vídeos de YouTube en MP4','Descarga de música de YouTube en MP3','Descarga de vídeos de TikTok','Descarga de vídeos de Instagram','Soporte de más de 1000 plataformas','Sin registro requerido','Interfaz responsiva para móvil'],
        'ld_faq'      => [
            ['q'=>'¿Cómo descargar vídeos de YouTube?','a'=>'Pega el enlace de YouTube, selecciona MP4 y haz clic en Descargar Ahora.'],
            ['q'=>'¿Cómo convertir YouTube a MP3?','a'=>'Pega el enlace de YouTube, selecciona Audio MP3 · 192 kbps y haz clic en Descargar Ahora.'],
            ['q'=>'¿Qué plataformas son compatibles?','a'=>'YouTube, TikTok, Instagram, Twitter/X, Facebook, Twitch, SoundCloud y más de 1000 plataformas vía yt-dlp.'],
            ['q'=>'¿La descarga es gratuita?','a'=>'Sí, OmniDownloader es totalmente gratuito y no requiere registro ni instalación.'],
        ],
    ],
    'ja' => [
        'lang_attr'   => 'ja',
        'html_title'  => 'OmniDownloader — YouTube・TikTok・Instagramの動画・音楽をダウンロード',
        'meta_desc'   => 'YouTube、TikTok、Instagram、Twitter、Facebookなど1000以上のプラットフォームからMP4動画・MP3音楽を無料でダウンロード。インストール不要、登録不要。',
        'meta_kw'     => 'youtube 動画ダウンロード, youtube mp3変換, youtubeダウンローダー, tiktokダウンロード, instagramダウンロード, 動画ダウンローダー, 音楽ダウンロード 無料',
        'og_title'    => 'OmniDownloader — 動画・音楽をオンラインでダウンロード',
        'og_desc'     => 'YouTube、TikTok、Instagramなど1000以上のサイトからMP4動画・MP3音楽を無料でダウンロード。登録不要、スマホ対応。',
        'og_locale'   => 'ja_JP',
        'hero_title'  => '動画・音楽をダウンロード',
        'hero_sub'    => 'YouTube、TikTok、Instagramなど1000以上のプラットフォーム対応',
        'stats_label' => 'ダウンロード完了',
        'input_ph'    => '検索するかリンクを貼り付けてください...',
        'paste_btn'   => '貼り付け',
        'aria_paste'  => 'クリップボードから貼り付け',
        'aria_clear'  => 'フィールドをクリア',
        'aria_input'  => '検索またはビデオURL',
        'fmt_vl'      => '動画',
        'fmt_vd'      => 'MP4 · 最高画質',
        'fmt_al'      => '音声',
        'fmt_ad'      => 'MP3 · 192 kbps',
        'fmt_aria'    => 'ダウンロード形式',
        'fmt_legend'  => '形式を選択',
        'dl_btn'      => '今すぐダウンロード',
        'loading_t'   => 'ダウンロードを処理中...',
        'stage1'      => 'サーバーに接続中...',
        'stage2'      => '動画情報を取得中...',
        'stage3'      => 'ファイルをダウンロード中...',
        'stage4'      => 'ファイルを準備中...',
        'loading_w'   => 'しばらくお待ちください。',
        'err_def'     => 'ダウンロード処理中にエラーが発生しました。',
        'try_again'   => 'もう一度試す',
        'plat_label'  => '対応プラットフォーム',
        'faq_title'   => 'よくある質問',
        'faq_q1'      => 'YouTubeの動画をダウンロードするには？',
        'faq_a1'      => '上のフィールドにYouTube動画のリンクを貼り付け、<strong>動画MP4</strong>を選択し、<strong>今すぐダウンロード</strong>をクリックして保存先を選択してください。',
        'faq_q2'      => 'YouTubeをMP3に変換するには？',
        'faq_a2'      => 'YouTubeリンクを貼り付け、<strong>音声MP3</strong>を選択して<strong>今すぐダウンロード</strong>をクリックします。192 kbpsで自動的に音声が抽出されます。',
        'faq_q3'      => 'TikTokとInstagramにも対応していますか？',
        'faq_a3'      => 'はい！TikTokやInstagramのリンクを貼り付けるだけです。OmniDownloaderはyt-dlp経由で1000以上のプラットフォームに対応しています。',
        'faq_q4'      => '無料・登録不要ですか？',
        'faq_a4'      => 'はい、OmniDownloaderは完全無料で、登録、アプリのインストール、ブラウザ拡張機能は一切不要です。',
        'faq_q5'      => 'リンクなしで動画を検索できますか？',
        'faq_a5'      => 'はい！検索フィールドに動画名またはアーティスト名を入力するだけです。OmniDownloaderが自動的にYouTubeを検索し、サムネイルと再生時間付きで結果を表示します。',
        'footer_copy' => 'All rights reserved',
        'footer_disc' => 'このサイトは動画を保存しません。すべてのコンテンツはそれぞれの所有者に帰属します。',
        'prev_page'   => '前へ',
        'next_page'   => '次へ',
        'dl_result'   => 'DL',
        'srch_load'   => '"%s" を検索中...',
        'srch_empty'  => '"%s" の結果がありません。',
        'srch_res'    => '"%s" の検索結果 · %d件',
        'srch_err'    => '検索エラー。yt-dlpがインストールされているか確認してください。',
        'page_info'   => '%d / %d ページ',
        'timeout_e'   => "ダウンロードに予想以上の時間がかかっています。\nURLが有効か確認して、もう一度お試しください。",
        'cookie_hint' => 'YouTube: サーバー側でCookieを自動的に試行して anti-bot を回避します。失敗する場合は、YouTube側の一時的な制限の可能性があります。',
        'app_name'    => 'OmniDownloader',
        'app_desc_ld' => 'YouTube、TikTok、Instagram、Twitter、Facebookなど1000以上のプラットフォームからMP4動画・MP3音楽を無料でダウンロード。',
        'features'    => ['YouTubeのMP4動画ダウンロード','YouTubeのMP3音楽ダウンロード','TikTok動画ダウンロード','Instagram動画ダウンロード','1000以上のプラットフォーム対応','登録不要','レスポンシブなモバイルインターフェース'],
        'ld_faq'      => [
            ['q'=>'YouTubeの動画をダウンロードするには？','a'=>'YouTube動画のリンクを検索フィールドに貼り付け、MP4形式を選択し、今すぐダウンロードをクリックしてください。'],
            ['q'=>'YouTubeをMP3に変換するには？','a'=>'YouTubeリンクを貼り付け、音声MP3 · 192 kbps形式を選択して今すぐダウンロードをクリックします。'],
            ['q'=>'対応しているプラットフォームは？','a'=>'OmniDownloaderはYouTube、TikTok、Instagram、Twitter/X、Facebook、Twitch、SoundCloudとyt-dlp経由で1000以上のプラットフォームに対応しています。'],
            ['q'=>'ダウンロードは無料ですか？','a'=>'はい、OmniDownloaderは完全無料で、登録やソフトウェアのインストールは不要です。'],
        ],
    ],
];

$t = $langs[$lang];

$downloadToken = bin2hex(random_bytes(16));
require_once __DIR__ . '/includes/counter.php';
$downloadCount = getDownloadCount();

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'omnidownloader.andretsc.dev';
$siteUrl   = $protocol . '://' . $host;
$canonical = $siteUrl . '/' . ($lang !== 'pt' ? '?lang=' . $lang : '');
$ogImage   = $siteUrl . '/assets/img/og-image.png';

// Build JSON-LD strings
$featuresJson = json_encode($t['features'], JSON_UNESCAPED_UNICODE);
$faqLdItems   = implode(",\n        ", array_map(function ($f) {
    return '{"@type":"Question","name":' . json_encode($f['q'], JSON_UNESCAPED_UNICODE)
         . ',"acceptedAnswer":{"@type":"Answer","text":' . json_encode($f['a'], JSON_UNESCAPED_UNICODE) . '}}';
}, $t['ld_faq']));

// JS translations (exclude PHP-only keys)
$jsT     = array_diff_key($t, array_flip(['ld_faq', 'features', 'app_name', 'app_desc_ld']));
$jsTJson = json_encode($jsT, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);

function he(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?><!DOCTYPE html>
<html lang="<?= he($t['lang_attr']) ?>" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ===== Primary SEO ===== -->
    <title><?= he($t['html_title']) ?></title>
    <meta name="description" content="<?= he($t['meta_desc']) ?>">
    <meta name="keywords"    content="<?= he($t['meta_kw']) ?>">
    <meta name="author"      content="Andre Silva">
    <meta name="application-name" content="OmniDownloader">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="robots"      content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#0D47A1">
    <link rel="canonical"    href="<?= he($canonical) ?>">

    <!-- ===== Favicon & Icons ===== -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png" sizes="32x32">
    <link rel="icon" type="image/png" href="assets/img/favicon-192.png" sizes="192x192">
    <link rel="icon" type="image/png" href="assets/img/favicon-512.png" sizes="512x512">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/favicon.png">
    <link rel="manifest" href="manifest.json">>

    <!-- ===== hreflang — one URL per language ===== -->
    <link rel="alternate" hreflang="pt"      href="<?= he($siteUrl) ?>/">
    <link rel="alternate" hreflang="en"      href="<?= he($siteUrl) ?>/?lang=en">
    <link rel="alternate" hreflang="es"      href="<?= he($siteUrl) ?>/?lang=es">
    <link rel="alternate" hreflang="ja"      href="<?= he($siteUrl) ?>/?lang=ja">
    <link rel="alternate" hreflang="x-default" href="<?= he($siteUrl) ?>/">

    <!-- ===== Open Graph ===== -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= he($canonical) ?>">
    <meta property="og:title"       content="<?= he($t['og_title']) ?>">
    <meta property="og:description" content="<?= he($t['og_desc']) ?>">
    <meta property="og:image"       content="<?= he($ogImage) ?>">
    <meta property="og:image:type"   content="image/png">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"    content="<?= he($t['og_title']) ?>">
    <meta property="og:locale"       content="<?= he($t['og_locale']) ?>">
    <meta property="og:site_name"    content="OmniDownloader">

    <!-- ===== Additional SEO: Google & Bing ===== -->
    <meta name="google-site-verification" content="">
    <meta name="msvalidate.01" content="">
    <meta name="revisit-after" content="7 days">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">

    <!-- ===== Twitter Card ===== -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= he($t['og_title']) ?>">
    <meta name="twitter:description" content="<?= he($t['og_desc']) ?>">
    <meta name="twitter:image"       content="<?= he($ogImage) ?>">
    <meta name="twitter:site"        content="@andretsc">
    <meta name="twitter:creator"     content="@andretsc">

    <!-- ===== JSON-LD: WebApplication ===== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": <?= json_encode($t['app_name'], JSON_UNESCAPED_UNICODE) ?>,
        "url": <?= json_encode($siteUrl, JSON_UNESCAPED_UNICODE) ?>,
        "description": <?= json_encode($t['app_desc_ld'], JSON_UNESCAPED_UNICODE) ?>,
        "applicationCategory": "MultimediaApplication",
        "operatingSystem": "All",
        "inLanguage": [
            {"@type":"Language","name":"Portuguese"},
            {"@type":"Language","name":"English"},
            {"@type":"Language","name":"Spanish"},
            {"@type":"Language","name":"Japanese"}
        ],
        "offers": {"@type":"Offer","price":"0","priceCurrency":"BRL"},
        "featureList": <?= $featuresJson ?>,
        "author": {"@type":"Person","name":"Andre Silva","url":"https://andretsc.dev"}
    }
    </script>

    <!-- ===== JSON-LD: WebSite ===== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "OmniDownloader",
        "url": <?= json_encode($siteUrl, JSON_UNESCAPED_UNICODE) ?>,
        "inLanguage": <?= json_encode($t['lang_attr'], JSON_UNESCAPED_UNICODE) ?>,
        "potentialAction": {
            "@type": "SearchAction",
            "target": <?= json_encode($siteUrl . '/?q={search_term_string}', JSON_UNESCAPED_UNICODE) ?>,
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <!-- ===== JSON-LD: FAQPage ===== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
        <?= $faqLdItems ?>
        ]
    }
    </script>

    <!-- ===== JSON-LD: BreadcrumbList ===== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": <?= json_encode($siteUrl . '/', JSON_UNESCAPED_UNICODE) ?>
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Download",
                "item": <?= json_encode($siteUrl . '/#download', JSON_UNESCAPED_UNICODE) ?>
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "Search",
                "item": <?= json_encode($siteUrl . '/#search', JSON_UNESCAPED_UNICODE) ?>
            },
            {
                "@type": "ListItem",
                "position": 4,
                "name": "FAQ",
                "item": <?= json_encode($siteUrl . '/#faq', JSON_UNESCAPED_UNICODE) ?>
            }
        ]
    }
    </script>

    <!-- ===== JSON-LD: Organization ===== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "OmniDownloader",
        "url": <?= json_encode($siteUrl, JSON_UNESCAPED_UNICODE) ?>,
        "logo": <?= json_encode($siteUrl . '/assets/img/favicon.png', JSON_UNESCAPED_UNICODE) ?>,
        "description": "Uma aplicação web para baixar vídeos e áudio de YouTube, TikTok, Instagram e 1000+ plataformas",
        "sameAs": [
            "https://github.com/andrebauru/OmniDownloader"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Technical Support",
            "url": "https://github.com/andrebauru/OmniDownloader/issues"
        }
    }
    </script>

    <!-- ===== Performance ===== -->
    <link rel="preconnect" href="https://i.ytimg.com">
    <link rel="dns-prefetch" href="https://i.ytimg.com">
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="preload" href="assets/js/app.js"     as="script">

    <!-- ===== Google Analytics 4 ===== -->
    <!-- Replace YOUR_GA_ID with your actual Google Analytics 4 Measurement ID -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=YOUR_GA_ID"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'YOUR_GA_ID', {
        'page_path': window.location.pathname,
        'page_title': document.title
    });
    // Track custom events
    function trackDownload(platform, format) {
        gtag('event', 'download', {
            'event_category': 'engagement',
            'event_label': platform + '_' + format,
            'value': 1
        });
    }
    function trackSearch(query, platform) {
        gtag('event', 'search', {
            'event_category': 'engagement',
            'search_term': query,
            'platform': platform
        });
    }
    </script>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">

        <!-- ======= Header ======= -->
        <header class="header">
            <div class="container">
                <div class="header-inner">
                    <div class="logo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span>OmniDownloader</span>
                    </div>
                    <nav class="lang-switcher" aria-label="Language / Idioma">
                        <a href="?lang=pt" class="btn-lang<?= $lang === 'pt' ? ' active' : '' ?>" hreflang="pt" title="Português">🇧🇷 PT</a>
                        <a href="?lang=en" class="btn-lang<?= $lang === 'en' ? ' active' : '' ?>" hreflang="en" title="English">🇺🇸 EN</a>
                        <a href="?lang=es" class="btn-lang<?= $lang === 'es' ? ' active' : '' ?>" hreflang="es" title="Español">🇪🇸 ES</a>
                        <a href="?lang=ja" class="btn-lang<?= $lang === 'ja' ? ' active' : '' ?>" hreflang="ja" title="日本語">🇯🇵 JA</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- ======= Main ======= -->
        <main class="main">
            <div class="container">

                <!-- Hero -->
                <div class="hero">
                    <h1 class="hero-title"><?= he($t['hero_title']) ?></h1>
                    <p class="hero-subtitle"><?= he($t['hero_sub']) ?></p>
                    <div class="stats-widget" aria-live="polite">
                        <span class="stats-dot" aria-hidden="true"></span>
                        <span id="downloadCount" class="stats-number"
                              data-target="<?= $downloadCount ?>">0</span>
                        <span class="stats-label"><?= he($t['stats_label']) ?></span>
                    </div>
                </div>

                <!-- Download Card -->
                <div class="card">

                    <!-- Form (hidden during loading/error) -->
                    <form id="downloadForm" action="download.php" method="POST" target="downloadFrame">
                        <input type="hidden" name="token" value="<?= he($downloadToken) ?>">

                        <!-- URL / Search Input -->
                        <div class="input-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                </svg>
                                <input
                                    type="text"
                                    name="url"
                                    id="urlInput"
                                    placeholder="<?= he($t['input_ph']) ?>"
                                    autocomplete="off"
                                    spellcheck="false"
                                    aria-label="<?= he($t['aria_input']) ?>"
                                >
                                <button type="button" id="clearBtn" class="btn-clear hidden"
                                        title="<?= he($t['aria_clear']) ?>"
                                        aria-label="<?= he($t['aria_clear']) ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                            <button type="button" id="pasteBtn" class="btn-paste"
                                    aria-label="<?= he($t['aria_paste']) ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="9" y="2" width="6" height="4" rx="1" ry="1"/>
                                    <path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/>
                                    <path d="M16 17l-4-4-4 4"/><path d="M12 13v8"/>
                                </svg>
                                <?= he($t['paste_btn']) ?>
                            </button>
                        </div>

                        <!-- Video Info Preview -->
                        <div id="videoPreview" class="video-preview hidden" aria-live="polite">
                            <img id="videoThumb" src="" alt="Thumbnail">
                            <div class="video-meta">
                                <p id="videoTitle" class="video-title"></p>
                                <p id="videoDuration" class="video-duration"></p>
                                <span id="videoPlatform" class="result-platform hidden"></span>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div id="searchSection" class="search-section hidden">
                            <div class="search-platform-bar">
                                <button type="button" class="btn-platform active" data-platform="youtube">
                                    <span class="platform-dot" style="background:#FF0000"></span>YouTube
                                </button>
                                <button type="button" class="btn-platform" data-platform="tiktok">
                                    <span class="platform-dot" style="background:#010101"></span>TikTok
                                </button>
                                <button type="button" class="btn-platform" data-platform="soundcloud">
                                    <span class="platform-dot" style="background:#FF5500"></span>SoundCloud
                                </button>
                            </div>
                            <div id="searchStatus" class="search-status"></div>
                            <div id="resultsGrid" class="results-grid"></div>
                            <div id="pagination" class="pagination hidden">
                                <button type="button" id="prevPageBtn" class="btn-page" disabled>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <polyline points="15 18 9 12 15 6"/>
                                    </svg>
                                    <?= he($t['prev_page']) ?>
                                </button>
                                <span id="pageInfo" class="page-info"></span>
                                <button type="button" id="nextPageBtn" class="btn-page">
                                    <?= he($t['next_page']) ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Download Section -->
                        <div id="downloadSection">
                            <fieldset class="format-group" aria-label="<?= he($t['fmt_aria']) ?>">
                                <legend class="sr-only"><?= he($t['fmt_legend']) ?></legend>

                                <label class="format-option">
                                    <input type="radio" name="format" value="video" checked>
                                    <span class="format-card">
                                        <span class="format-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="23 7 16 12 23 17 23 7"/>
                                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                                            </svg>
                                        </span>
                                        <span class="format-text">
                                            <span class="format-label"><?= he($t['fmt_vl']) ?></span>
                                            <span class="format-desc"><?= he($t['fmt_vd']) ?></span>
                                        </span>
                                    </span>
                                </label>

                                <label class="format-option">
                                    <input type="radio" name="format" value="mp3">
                                    <span class="format-card">
                                        <span class="format-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 18V5l12-2v13"/>
                                                <circle cx="6" cy="18" r="3"/>
                                                <circle cx="18" cy="16" r="3"/>
                                            </svg>
                                        </span>
                                        <span class="format-text">
                                            <span class="format-label"><?= he($t['fmt_al']) ?></span>
                                            <span class="format-desc"><?= he($t['fmt_ad']) ?></span>
                                        </span>
                                    </span>
                                </label>
                            </fieldset>

                            <button type="submit" id="downloadBtn" class="btn-download">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                <?= he($t['dl_btn']) ?>
                            </button>
                        </div><!-- /#downloadSection -->
                    </form>

                    <!-- Loading State: Progress Bar -->
                    <div id="loadingState" class="loading-state hidden" aria-live="assertive" role="status">
                        <div class="progress-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                        </div>
                        <p class="loading-title" id="loadingTitle"><?= he($t['loading_t']) ?></p>
                        <div class="progress-wrapper">
                            <div class="progress-track">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                            <span class="progress-pct" id="progressPct">0%</span>
                        </div>
                        <p class="loading-stage" id="loadingStage"><?= he($t['stage1']) ?></p>
                        <p class="loading-wait"><?= he($t['loading_w']) ?></p>
                    </div>

                    <!-- Error State -->
                    <div id="errorState" class="error-state hidden" aria-live="assertive" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <p id="errorMessage"><?= he($t['err_def']) ?></p>
                        <button type="button" id="tryAgainBtn" class="btn-try-again"><?= he($t['try_again']) ?></button>
                    </div>

                </div><!-- /.card -->

                <!-- Supported Platforms -->
                <div class="platforms">
                    <p class="platforms-label"><?= he($t['plat_label']) ?></p>
                    <div class="platform-list" role="list">
                        <span class="platform-badge" role="listitem">YouTube</span>
                        <span class="platform-badge" role="listitem">TikTok</span>
                        <span class="platform-badge" role="listitem">Instagram</span>
                        <span class="platform-badge" role="listitem">Twitter / X</span>
                        <span class="platform-badge" role="listitem">Facebook</span>
                        <span class="platform-badge" role="listitem">Twitch</span>
                        <span class="platform-badge" role="listitem">SoundCloud</span>
                        <span class="platform-badge" role="listitem">+1000</span>
                    </div>
                </div>

                <!-- FAQ -->
                <section class="faq" aria-label="<?= he($t['faq_title']) ?>">
                    <h2 class="faq-title"><?= he($t['faq_title']) ?></h2>

                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <details class="faq-item">
                        <summary class="faq-question"><?= he($t["faq_q{$i}"]) ?></summary>
                        <p class="faq-answer"><?= $t["faq_a{$i}"] ?></p>
                    </details>
                    <?php endfor; ?>
                </section>

            </div>
        </main>

        <!-- ======= Footer ======= -->
        <footer class="footer">
            <div class="container">
                <nav class="footer-nav" aria-label="Footer links">
                    <a href="https://andretsc.dev" target="_blank" rel="noopener noreferrer">Andre Silva</a>
                </nav>
                <p class="footer-copy">
                    &copy; <?= date('Y') ?>
                    <a href="https://andretsc.dev" target="_blank" rel="noopener noreferrer">Andre Silva</a>
                    &middot; <?= he($t['footer_copy']) ?>
                </p>
                <p class="footer-disclaimer"><?= he($t['footer_disc']) ?></p>
                <p id="cookieNotice" class="cookie-notice hidden" aria-live="polite">
                    <?= he($t['cookie_hint']) ?>
                </p>
            </div>
        </footer>

    </div><!-- /.wrapper -->

    <iframe name="downloadFrame" id="downloadFrame" style="display:none;" tabindex="-1" aria-hidden="true"></iframe>

    <script>
        const DOWNLOAD_TOKEN = '<?= $downloadToken ?>';
        const LANG = '<?= $lang ?>';
        const T = <?= $jsTJson ?>;
    </script>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>