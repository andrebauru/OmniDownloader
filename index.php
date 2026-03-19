<?php
// Generate a one-time token used to detect when the download has started (via cookie)
$downloadToken = bin2hex(random_bytes(16));
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="OmniDownloader — Baixe vídeos e músicas do YouTube, TikTok, Instagram e mais de 1000 plataformas.">
    <title>OmniDownloader — Baixe Vídeos e Músicas</title>
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

            </div>
        </main>

        <!-- ======= Footer ======= -->
        <footer class="footer">
            <div class="container">
                <p>© <?= date('Y') ?> <a href="https://andretsc.dev" target="_blank" rel="noopener noreferrer">Andre Silva</a></p>
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
