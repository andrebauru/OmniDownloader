<?php
$downloadToken = bin2hex(random_bytes(16));

require_once __DIR__ . '/includes/counter.php';
$downloadCount = getDownloadCount();

// Detect protocol and host for canonical/OG URLs
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'omnidownloader.andretsc.dev';
$siteUrl   = $protocol . '://' . $host;
$canonical = $siteUrl . '/';
$ogImage   = $siteUrl . '/assets/img/og-image.png';
?><!DOCTYPE html>
<html lang="pt-BR" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ===== Primary SEO ===== -->
    <title>OmniDownloader — Baixar Vídeos e Músicas do YouTube, TikTok e Instagram</title>
    <meta name="description"
          content="Baixe vídeos em MP4 e músicas em MP3 do YouTube, TikTok, Instagram, Twitter, Facebook e mais de 1000 plataformas. Grátis, rápido e sem precisar instalar nada.">
    <meta name="keywords"
          content="baixar vídeo youtube, baixar mp3 youtube, download youtube, baixar tiktok, baixar instagram, youtube downloader, conversor youtube mp3, baixar música grátis, download vídeo online, yt-dlp online">
    <meta name="author"      content="Andre Silva">
    <meta name="robots"      content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#0D47A1">
    <link rel="canonical"    href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

    <!-- ===== Open Graph (Facebook, WhatsApp, LinkedIn) ===== -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title"       content="OmniDownloader — Baixar Vídeos e Músicas Online">
    <meta property="og:description" content="Baixe vídeos em MP4 e músicas em MP3 do YouTube, TikTok, Instagram e mais de 1000 sites. Grátis, sem cadastro, funciona no celular.">
    <meta property="og:image"       content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"    content="OmniDownloader — Baixador de vídeos e músicas online">
    <meta property="og:locale"       content="pt_BR">
    <meta property="og:site_name"    content="OmniDownloader">

    <!-- ===== Twitter Card ===== -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="OmniDownloader — Baixar Vídeos e Músicas Online">
    <meta name="twitter:description" content="Baixe vídeos em MP4 e músicas em MP3 do YouTube, TikTok, Instagram e mais de 1000 sites. Grátis e sem cadastro.">
    <meta name="twitter:image"       content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:creator"     content="@andretsc">

    <!-- ===== Structured Data: WebApplication ===== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "OmniDownloader",
        "url": "<?= $siteUrl ?>",
        "description": "Baixe vídeos em MP4 e músicas em MP3 do YouTube, TikTok, Instagram, Twitter, Facebook e mais de 1000 plataformas. Grátis, rápido e sem precisar instalar nada.",
        "applicationCategory": "MultimediaApplication",
        "operatingSystem": "All",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "BRL"
        },
        "featureList": [
            "Download de vídeos do YouTube em MP4",
            "Download de músicas do YouTube em MP3",
            "Download de vídeos do TikTok",
            "Download de vídeos do Instagram",
            "Suporte a mais de 1000 plataformas",
            "Sem necessidade de cadastro",
            "Interface responsiva para celular"
        ],
        "author": {
            "@type": "Person",
            "name": "Andre Silva",
            "url": "https://andretsc.dev"
        }
    }
    </script>

    <!-- ===== Structured Data: FAQPage ===== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {
                "@type": "Question",
                "name": "Como baixar vídeos do YouTube?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Cole o link do vídeo do YouTube no campo de busca, selecione o formato MP4, clique em 'Baixar Agora' e escolha onde salvar o arquivo."
                }
            },
            {
                "@type": "Question",
                "name": "Como converter YouTube para MP3?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Cole o link do YouTube, selecione o formato 'Áudio MP3 · 192 kbps', clique em 'Baixar Agora' e o arquivo MP3 será salvo automaticamente."
                }
            },
            {
                "@type": "Question",
                "name": "Quais plataformas são suportadas?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "O OmniDownloader suporta YouTube, TikTok, Instagram, Twitter/X, Facebook, Twitch, SoundCloud e mais de 1000 plataformas via yt-dlp."
                }
            },
            {
                "@type": "Question",
                "name": "O download é gratuito?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Sim, o OmniDownloader é totalmente gratuito e não exige cadastro ou instalação de programas."
                }
            }
        ]
    }
    </script>

    <!-- ===== Performance ===== -->
    <link rel="preconnect" href="https://i.ytimg.com">
    <link rel="dns-prefetch" href="https://i.ytimg.com">
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="preload" href="assets/js/app.js"     as="script">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">

        <!-- ======= Header ======= -->
        <header class="header">
            <div class="container">
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
            </div>
        </header>

        <!-- ======= Main ======= -->
        <main class="main">
            <div class="container">

                <!-- Hero -->
                <div class="hero">
                    <h1 class="hero-title">Baixe vídeos e músicas</h1>
                    <p class="hero-subtitle">YouTube, TikTok, Instagram e mais de 1000 plataformas</p>
                    <div class="stats-widget" aria-live="polite">
                        <span class="stats-dot" aria-hidden="true"></span>
                        <span id="downloadCount" class="stats-number"
                              data-target="<?= $downloadCount ?>">0</span>
                        <span class="stats-label">downloads realizados</span>
                    </div>
                </div>

                <!-- Download Card -->
                <div class="card">

                    <!-- Form (hidden during loading/error) -->
                    <form id="downloadForm" action="download.php" method="POST" target="downloadFrame">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($downloadToken, ENT_QUOTES, 'UTF-8') ?>">

                        <!-- URL Input -->
                        <div class="input-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                </svg>
                                <input
                                    type="url"
                                    name="url"
                                    id="urlInput"
                                    placeholder="Cole aqui o link (YouTube, TikTok, Instagram...)"
                                    autocomplete="off"
                                    spellcheck="false"
                                    required
                                    aria-label="URL do vídeo"
                                >
                                <button type="button" id="clearBtn" class="btn-clear hidden"
                                        title="Limpar" aria-label="Limpar campo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                            <button type="button" id="pasteBtn" class="btn-paste" aria-label="Colar da área de transferência">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="9" y="2" width="6" height="4" rx="1" ry="1"/>
                                    <path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/>
                                    <path d="M16 17l-4-4-4 4"/><path d="M12 13v8"/>
                                </svg>
                                Colar
                            </button>
                        </div>

                        <!-- Video Info Preview (populated via JS/API) -->
                        <div id="videoPreview" class="video-preview hidden" aria-live="polite">
                            <img id="videoThumb" src="" alt="Thumbnail do vídeo">
                            <div class="video-meta">
                                <p id="videoTitle" class="video-title"></p>
                                <p id="videoDuration" class="video-duration"></p>
                            </div>
                        </div>

                        <!-- Format Selection -->
                        <fieldset class="format-group" aria-label="Formato de download">
                            <legend class="sr-only">Escolha o formato</legend>

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
                                        <span class="format-label">Vídeo</span>
                                        <span class="format-desc">MP4 · Melhor qualidade</span>
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
                                        <span class="format-label">Áudio</span>
                                        <span class="format-desc">MP3 · 192 kbps</span>
                                    </span>
                                </span>
                            </label>
                        </fieldset>

                        <!-- Download Button -->
                        <button type="submit" id="downloadBtn" class="btn-download">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Baixar Agora
                        </button>
                    </form>

                    <!-- Loading State -->
                    <div id="loadingState" class="loading-state hidden" aria-live="assertive" role="status">
                        <div class="spinner" aria-hidden="true"></div>
                        <p class="loading-text">Processando o download...</p>
                        <p class="loading-sub">Isso pode levar alguns segundos. Por favor, aguarde.</p>
                    </div>

                    <!-- Error State -->
                    <div id="errorState" class="error-state hidden" aria-live="assertive" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <p id="errorMessage">Ocorreu um erro ao processar o download.</p>
                        <button type="button" id="tryAgainBtn" class="btn-try-again">Tentar novamente</button>
                    </div>

                </div><!-- /.card -->

                <!-- Supported Platforms -->
                <div class="platforms">
                    <p class="platforms-label">Plataformas suportadas</p>
                    <div class="platform-list" role="list">
                        <span class="platform-badge" role="listitem">YouTube</span>
                        <span class="platform-badge" role="listitem">TikTok</span>
                        <span class="platform-badge" role="listitem">Instagram</span>
                        <span class="platform-badge" role="listitem">Twitter / X</span>
                        <span class="platform-badge" role="listitem">Facebook</span>
                        <span class="platform-badge" role="listitem">Twitch</span>
                        <span class="platform-badge" role="listitem">SoundCloud</span>
                        <span class="platform-badge" role="listitem">+1000 sites</span>
                    </div>
                </div>

                <!-- ======= FAQ (SEO rich snippet) ======= -->
                <section class="faq" aria-label="Perguntas frequentes">
                    <h2 class="faq-title">Perguntas Frequentes</h2>

                    <details class="faq-item">
                        <summary class="faq-question">Como baixar vídeos do YouTube?</summary>
                        <p class="faq-answer">Cole o link do vídeo do YouTube no campo acima, selecione o formato <strong>Vídeo MP4</strong>, clique em <strong>Baixar Agora</strong> e escolha onde salvar o arquivo.</p>
                    </details>

                    <details class="faq-item">
                        <summary class="faq-question">Como converter YouTube para MP3?</summary>
                        <p class="faq-answer">Cole o link do YouTube, selecione o formato <strong>Áudio MP3</strong> e clique em <strong>Baixar Agora</strong>. O áudio é extraído em qualidade 192 kbps automaticamente.</p>
                    </details>

                    <details class="faq-item">
                        <summary class="faq-question">Funciona para TikTok e Instagram?</summary>
                        <p class="faq-answer">Sim! Cole o link de qualquer vídeo do TikTok ou Instagram e baixe normalmente. O OmniDownloader suporta mais de 1000 plataformas via yt-dlp.</p>
                    </details>

                    <details class="faq-item">
                        <summary class="faq-question">O download é gratuito e sem cadastro?</summary>
                        <p class="faq-answer">Sim, o OmniDownloader é 100% gratuito, não exige cadastro, instalação de aplicativos ou extensões de navegador.</p>
                    </details>

                    <details class="faq-item">
                        <summary class="faq-question">Posso pesquisar vídeos sem ter o link?</summary>
                        <p class="faq-answer">Sim! Basta digitar o nome do vídeo ou artista no campo de busca. O OmniDownloader buscará automaticamente no YouTube e mostrará os resultados com thumbnail e duração.</p>
                    </details>
                </section>

            </div>
        </main>

        <!-- ======= Footer ======= -->
        <footer class="footer">
            <div class="container">
                <nav class="footer-nav" aria-label="Links do rodapé">
                    <a href="https://andretsc.dev" target="_blank" rel="noopener noreferrer">Andre Silva</a>
                </nav>
                <p class="footer-copy">© <?= date('Y') ?> <a href="https://andretsc.dev" target="_blank" rel="noopener noreferrer">Andre Silva</a> · Todos os direitos reservados</p>
                <p class="footer-disclaimer">Este site não armazena vídeos. Todo o conteúdo é de responsabilidade de seus respectivos donos.</p>
            </div>
        </footer>

    </div><!-- /.wrapper -->

    <!-- Hidden iframe used as form target so the page doesn't navigate on download -->
    <iframe name="downloadFrame" id="downloadFrame" style="display:none;" tabindex="-1" aria-hidden="true"></iframe>

    <!-- Pass PHP token to JS -->
    <script>const DOWNLOAD_TOKEN = '<?= $downloadToken ?>';</script>
    <script src="assets/js/app.js"></script>
</body>
</html>
