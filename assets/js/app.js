'use strict';

/* ================================================
   OmniDownloader — Frontend JavaScript
   © Andre Silva | andretsc.dev
   ================================================ */

// ---- i18n helper ------------------------------------------------------- //

function t(key) {
    return (typeof T !== 'undefined' && T[key] !== undefined) ? T[key] : key;
}

// ---- DOM ---------------------------------------------------------------- //

const urlInput        = document.getElementById('urlInput');
const pasteBtn        = document.getElementById('pasteBtn');
const clearBtn        = document.getElementById('clearBtn');
const downloadForm    = document.getElementById('downloadForm');
const loadingState    = document.getElementById('loadingState');
const errorState      = document.getElementById('errorState');
const errorMessage    = document.getElementById('errorMessage');
const tryAgainBtn     = document.getElementById('tryAgainBtn');
const videoPreview    = document.getElementById('videoPreview');
const videoTitle      = document.getElementById('videoTitle');
const videoThumb      = document.getElementById('videoThumb');
const videoDuration   = document.getElementById('videoDuration');
const videoPlatform   = document.getElementById('videoPlatform');
const downloadFrame   = document.getElementById('downloadFrame');
const downloadSection = document.getElementById('downloadSection');
const searchSection   = document.getElementById('searchSection');
const searchStatus    = document.getElementById('searchStatus');
const resultsGrid     = document.getElementById('resultsGrid');
const pagination      = document.getElementById('pagination');
const prevPageBtn     = document.getElementById('prevPageBtn');
const nextPageBtn     = document.getElementById('nextPageBtn');
const pageInfoEl      = document.getElementById('pageInfo');
const counterEl       = document.getElementById('downloadCount');
const progressFill    = document.getElementById('progressFill');
const progressPct     = document.getElementById('progressPct');
const loadingStage    = document.getElementById('loadingStage');
const cookieNotice    = document.getElementById('cookieNotice');

// ---- State ------------------------------------------------------------- //

let pollInterval       = null;
let timeoutHandle      = null;
let infoTimer          = null;
let searchTimer        = null;
let progressStageTimer = null;
let progressAnimFrame  = null;

let searchQuery    = '';
let searchPage     = 1;
let searchPages    = 1;
let searchPlatform = 'youtube';
let mode           = 'idle'; // 'idle' | 'url' | 'search'
let progressCurrent = 0;
let activePreviewId = null; // currently open audio preview

function canRenderAudioPreview(platform) {
    return platform === 'YouTube' || platform === 'SoundCloud';
}

// ---- Platform Detection ------------------------------------------------- //

const PLATFORM_MAP = {
    'youtube': 'YouTube', 'youtube:tab': 'YouTube',
    'tiktok': 'TikTok',
    'instagram': 'Instagram',
    'twitter': 'Twitter / X', 'x': 'Twitter / X',
    'facebook': 'Facebook', 'facebook:video': 'Facebook',
    'twitch:vod': 'Twitch', 'twitch:stream': 'Twitch',
    'soundcloud': 'SoundCloud',
    'vimeo': 'Vimeo',
    'dailymotion': 'Dailymotion',
    'reddit': 'Reddit',
};

function platformFromExtractor(extractor) {
    if (!extractor) return null;
    return PLATFORM_MAP[extractor.toLowerCase()] || extractor;
}

function platformFromUrl(url) {
    if (!url) return null;
    const u = url.toLowerCase();
    if (u.includes('youtu.be') || u.includes('youtube.com')) return 'YouTube';
    if (u.includes('tiktok.com'))                             return 'TikTok';
    if (u.includes('instagram.com'))                          return 'Instagram';
    if (u.includes('twitter.com') || u.includes('x.com'))    return 'Twitter / X';
    if (u.includes('facebook.com') || u.includes('fb.watch'))return 'Facebook';
    if (u.includes('twitch.tv'))                              return 'Twitch';
    if (u.includes('soundcloud.com'))                         return 'SoundCloud';
    if (u.includes('vimeo.com'))                              return 'Vimeo';
    if (u.includes('dailymotion.com'))                        return 'Dailymotion';
    if (u.includes('reddit.com'))                             return 'Reddit';
    return null;
}

function setPlatformBadge(el, platform) {
    if (!el) return;
    if (platform) {
        el.textContent = platform;
        el.dataset.platform = platform;
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}

// ---- Helpers ------------------------------------------------------------ //

function isUrl(text) {
    return /^https?:\/\//i.test(text) || /^www\./i.test(text);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatDuration(seconds) {
    if (!seconds || seconds < 1) return '';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    return `${m}:${String(s).padStart(2,'0')}`;
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function deleteCookie(name) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
}

function stopPolling() {
    clearInterval(pollInterval);
    clearTimeout(timeoutHandle);
    pollInterval  = null;
    timeoutHandle = null;
}

function showCookieNotice() {
    if (!cookieNotice) return;
    cookieNotice.classList.remove('hidden');
}

function hideCookieNotice() {
    if (!cookieNotice) return;
    cookieNotice.classList.add('hidden');
}

function isYoutubeUrlInput(text) {
    const u = String(text || '').toLowerCase();
    return u.includes('youtube.com') || u.includes('youtu.be');
}

function isCookieRelatedBlock(msg) {
    const s = String(msg || '').toLowerCase();
    return s.includes('anti-bot')
        || s.includes("you're not a bot")
        || s.includes('cookies-from-browser')
        || s.includes('cookies.txt')
        || s.includes('--cookies');
}

// ---- Audio Preview ------------------------------------------------------ //

function closeAudioPreview() {
    document.querySelectorAll('.audio-preview-box iframe').forEach(frame => {
        try { frame.src = 'about:blank'; } catch { /* ignore */ }
    });
    document.querySelectorAll('.audio-preview-box').forEach(el => el.remove());
    document.querySelectorAll('.btn-audio-preview.active').forEach(el => el.classList.remove('active'));
    activePreviewId = null;
}

function toggleAudioPreview(id, platform, el) {
    if (activePreviewId === id) {
        closeAudioPreview();
        return;
    }
    closeAudioPreview();
    activePreviewId = id;
    el.classList.add('active');

    const box = document.createElement('div');
    box.className = 'audio-preview-box';

    if (!canRenderAudioPreview(platform)) {
        activePreviewId = null;
        el.classList.remove('active');
        return;
    }

    if (platform === 'SoundCloud') {
        const scUrl = encodeURIComponent(`https://soundcloud.com/${id}`);
        box.innerHTML = `
            <button type="button" class="btn-close-preview" aria-label="Fechar preview">×</button>
            <iframe scrolling="no" frameborder="no" allow="autoplay"
                src="https://w.soundcloud.com/player/?url=${scUrl}&color=%231565C0&auto_play=false&show_artwork=false&show_user=false&buying=false&liking=false&sharing=false&download=false">
            </iframe>`;
    } else {
        // YouTube
        box.innerHTML = `
            <button type="button" class="btn-close-preview" aria-label="Fechar preview">×</button>
            <iframe src="https://www.youtube.com/embed/${encodeURIComponent(id)}?autoplay=0&controls=1&modestbranding=1&rel=0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen frameborder="0" loading="lazy">
            </iframe>`;
    }

    const resultItem = el.closest('.result-item');
    resultItem.after(box);
    box.querySelector('.btn-close-preview')?.addEventListener('click', closeAudioPreview);
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ---- Progress Bar ------------------------------------------------------ //

function animateProgressTo(target, durationMs) {
    const from  = progressCurrent;
    const start = performance.now();
    if (progressAnimFrame) cancelAnimationFrame(progressAnimFrame);

    function step(now) {
        const elapsed   = Math.min((now - start) / durationMs, 1);
        progressCurrent = from + (target - from) * elapsed;
        const pct = Math.round(progressCurrent);
        if (progressFill) progressFill.style.width = pct + '%';
        if (progressPct)  progressPct.textContent  = pct + '%';
        if (elapsed < 1) {
            progressAnimFrame = requestAnimationFrame(step);
        } else {
            progressAnimFrame = null;
        }
    }
    progressAnimFrame = requestAnimationFrame(step);
}

function startProgressBar() {
    progressCurrent = 0;
    if (progressFill) { progressFill.style.transition = 'none'; progressFill.style.width = '0%'; }
    if (progressPct)  progressPct.textContent = '0%';

    const stages = [
        { pct: 15, wait: 500,  msg: t('stage1') },
        { pct: 42, wait: 4000, msg: t('stage2') },
        { pct: 72, wait: 7500, msg: t('stage3') },
        { pct: 88, wait: 5500, msg: t('stage4') },
    ];

    let i = 0;
    function runStage() {
        if (i >= stages.length) return;
        const s = stages[i++];
        if (loadingStage) loadingStage.textContent = s.msg;
        animateProgressTo(s.pct, 1400);
        progressStageTimer = setTimeout(runStage, s.wait);
    }
    progressStageTimer = setTimeout(runStage, 200);
}

function completeProgressBar(callback) {
    clearTimeout(progressStageTimer);
    if (loadingStage) loadingStage.textContent = t('stage4');
    animateProgressTo(100, 600);
    setTimeout(callback, 700);
}

function stopProgressBar() {
    clearTimeout(progressStageTimer);
    if (progressAnimFrame) { cancelAnimationFrame(progressAnimFrame); progressAnimFrame = null; }
}

// ---- Mode Switching ----------------------------------------------------- //

function setMode(newMode) {
    mode = newMode;
    const isSearch = (newMode === 'search');
    if (!isSearch) closeAudioPreview();
    searchSection.classList.toggle('hidden', !isSearch);
    downloadSection.classList.toggle('hidden', isSearch);
    if (newMode !== 'url') videoPreview.classList.add('hidden');
}

function resetToIdle() {
    mode = 'idle';
    searchSection.classList.add('hidden');
    downloadSection.classList.remove('hidden');
    videoPreview.classList.add('hidden');
    closeAudioPreview();
}

// ---- Download UI -------------------------------------------------------- //

function showLoading() {
    downloadForm.style.display = 'none';
    loadingState.classList.remove('hidden');
    errorState.classList.add('hidden');
    startProgressBar();
}

function showForm() {
    stopProgressBar();
    downloadForm.style.display = '';
    loadingState.classList.add('hidden');
    errorState.classList.add('hidden');
}

function showError(msg) {
    stopProgressBar();
    downloadForm.style.display = '';
    loadingState.classList.add('hidden');
    errorState.classList.remove('hidden');
    errorMessage.textContent = msg || t('err_def');
}

// ---- Input Interactions ------------------------------------------------- //

urlInput.addEventListener('input', () => {
    const val = urlInput.value.trim();
    clearBtn.classList.toggle('hidden', !val);
    clearTimeout(infoTimer);
    clearTimeout(searchTimer);

    if (!val) { resetToIdle(); return; }

    if (isYoutubeUrlInput(val)) showCookieNotice();
    else hideCookieNotice();

    if (isUrl(val)) {
        setMode('url');
        infoTimer = setTimeout(() => fetchVideoInfo(val), 700);
    } else {
        setMode('search');
        searchTimer = setTimeout(() => doSearch(val, 1), 600);
    }
});

clearBtn.addEventListener('click', () => {
    urlInput.value = '';
    clearBtn.classList.add('hidden');
    clearTimeout(infoTimer);
    clearTimeout(searchTimer);
    resetToIdle();
    closeAudioPreview();
    hideCookieNotice();
    urlInput.focus();
});

// ---- Paste Button ------------------------------------------------------- //

pasteBtn.addEventListener('click', async () => {
    try {
        const text = await navigator.clipboard.readText();
        if (text && text.trim()) {
            urlInput.value = text.trim();
            clearBtn.classList.remove('hidden');
            urlInput.dispatchEvent(new Event('input'));
        }
    } catch {
        urlInput.focus();
        urlInput.select();
    }
});

// ---- Video Info Preview ------------------------------------------------- //

async function fetchVideoInfo(url) {
    if (!isUrl(url)) return;
    try {
        const res  = await fetch(`api.php?url=${encodeURIComponent(url)}`);
        if (!res.ok) return;
        const data = await res.json();
        if (data.error || !data.title) return;

        videoTitle.textContent    = data.title;
        videoDuration.textContent = data.duration ? '⏱ ' + formatDuration(data.duration) : '';
        const platform = platformFromExtractor(data.extractor) || platformFromUrl(url);
        setPlatformBadge(videoPlatform, platform);

        if (data.thumbnail) {
            videoThumb.src           = data.thumbnail;
            videoThumb.style.display = '';
        } else {
            videoThumb.style.display = 'none';
        }
        if (mode === 'url') videoPreview.classList.remove('hidden');
    } catch { /* optional */ }
}

// ---- Search Platform Switcher ------------------------------------------- //

document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-platform');
    if (!btn) return;
    const plat = btn.dataset.platform;
    if (plat === searchPlatform) return;
    searchPlatform = plat;
    document.querySelectorAll('.btn-platform').forEach(b => b.classList.toggle('active', b.dataset.platform === plat));
    if (searchQuery) doSearch(searchQuery, 1);
});

// ---- Search ------------------------------------------------------------- //

async function doSearch(query, page) {
    searchQuery = query;
    searchPage  = page;
    closeAudioPreview();

    const loadMsg = t('srch_load').replace('%s', `<strong>${escapeHtml(query)}</strong>`);
    searchStatus.innerHTML = `
        <div class="search-loading">
            <div class="search-spinner"></div>
            <span>${loadMsg}</span>
        </div>`;
    resultsGrid.innerHTML = '';
    pagination.classList.add('hidden');

    try {
        const res  = await fetch(`search.php?q=${encodeURIComponent(query)}&page=${page}&platform=${encodeURIComponent(searchPlatform)}`);
        const data = await res.json();

        if (data.error) {
            searchStatus.innerHTML = `<p class="search-empty">⚠️ ${escapeHtml(data.error)}</p>`;
            return;
        }
        if (!data.results || data.results.length === 0) {
            const msg = t('srch_empty').replace('%s', escapeHtml(query));
            searchStatus.innerHTML = `<p class="search-empty">${msg}</p>`;
            return;
        }

        searchPages = data.totalPages || 1;
        const resLabel = t('srch_res')
            .replace('%s', `<strong>${escapeHtml(query)}</strong>`)
            .replace('%d', data.total);
        searchStatus.innerHTML = `<p class="search-label">${resLabel}</p>`;
        renderResults(data.results);
        updatePagination(data.page, data.totalPages);
    } catch (e) {
        searchStatus.innerHTML = `<p class="search-empty">⚠️ ${escapeHtml(t('srch_err'))}</p>`;
        console.error('[Search]', e);
    }
}

function renderResults(results) {
    const dlLabel = escapeHtml(t('dl_result'));
    resultsGrid.innerHTML = results.map(item => `
        <div class="result-item"
             data-url="${escapeHtml(item.url)}"
             data-title="${escapeHtml(item.title)}"
             data-platform="${escapeHtml(item.platform || '')}"
             data-id="${escapeHtml(item.id || '')}"
             role="button" tabindex="0">
            <div class="result-thumb-wrapper">
                <img class="result-thumb" src="${escapeHtml(item.thumbnail)}"
                     alt="${escapeHtml(item.title)}" loading="lazy"
                     onerror="this.style.display='none'">
                ${item.duration ? `<span class="result-duration">${formatDuration(item.duration)}</span>` : ''}
            </div>
            <div class="result-info">
                <p class="result-title">${escapeHtml(item.title)}</p>
                <p class="result-meta">${escapeHtml(item.uploader || '')}</p>
                ${item.platform ? `<span class="result-platform" data-platform="${escapeHtml(item.platform)}">${escapeHtml(item.platform)}</span>` : ''}
            </div>
            <div class="result-actions">
                ${canRenderAudioPreview(item.platform || '') ? `
                <button type="button" class="btn-audio-preview" title="Preview"
                        data-id="${escapeHtml(item.id || '')}"
                        data-platform="${escapeHtml(item.platform || '')}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="currentColor" aria-hidden="true">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                </button>` : ''}
                <button type="button" class="btn-select" data-action="select">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    ${dlLabel}
                </button>
            </div>
        </div>
    `).join('');

    resultsGrid.querySelectorAll('.result-item').forEach(el => {
        // Download button
        el.querySelector('[data-action="select"]').addEventListener('click', ev => {
            ev.stopPropagation();
            selectResult(el);
        });
        // Audio preview button
        const previewBtn = el.querySelector('.btn-audio-preview');
        if (previewBtn) {
            previewBtn.addEventListener('click', ev => {
                ev.stopPropagation();
                const id       = ev.currentTarget.dataset.id;
                const platform = ev.currentTarget.dataset.platform;
                toggleAudioPreview(id, platform, ev.currentTarget);
            });
        }
        // Click on row (excluding buttons) = select
        el.addEventListener('click', () => selectResult(el));
        el.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') selectResult(el); });
    });
}

function selectResult(el) {
    const url      = el.dataset.url;
    const title    = el.dataset.title;
    const platform = el.dataset.platform || platformFromUrl(url);

    urlInput.value = url;
    clearBtn.classList.remove('hidden');
    setMode('url');

    // Show preview immediately with what we know
    videoTitle.textContent    = title;
    videoDuration.textContent = '';
    videoThumb.style.display  = 'none';
    setPlatformBadge(videoPlatform, platform);
    videoPreview.classList.remove('hidden');

    // Then enrich with API data (thumbnail, duration, etc.)
    fetchVideoInfo(url);
    urlInput.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function updatePagination(page, totalPages) {
    if (totalPages <= 1) { pagination.classList.add('hidden'); return; }
    pagination.classList.remove('hidden');
    const tpl   = t('page_info');
    const parts = [page, totalPages];
    let idx = 0;
    pageInfoEl.textContent = tpl.replace(/%d/g, () => parts[idx++]);
    prevPageBtn.disabled   = page <= 1;
    nextPageBtn.disabled   = page >= totalPages;
}

prevPageBtn.addEventListener('click', () => {
    if (searchPage > 1) doSearch(searchQuery, searchPage - 1);
});
nextPageBtn.addEventListener('click', () => {
    if (searchPage < searchPages) doSearch(searchQuery, searchPage + 1);
});

// ---- Download Form ------------------------------------------------------ //

downloadForm.addEventListener('submit', e => {
    const url = urlInput.value.trim();
    if (!url || !isUrl(url)) {
        e.preventDefault();
        urlInput.focus();
        return;
    }
    deleteCookie('fileDownloadToken');
    closeAudioPreview();
    showLoading();

    pollInterval = setInterval(() => {
        if (getCookie('fileDownloadToken') === DOWNLOAD_TOKEN) {
            deleteCookie('fileDownloadToken');
            stopPolling();
            completeProgressBar(() => { showForm(); refreshCounter(); });
        }
    }, 500);

    timeoutHandle = setTimeout(() => {
        stopPolling();
        showError(t('timeout_e'));
    }, 180_000);
});

// ---- Iframe Error Detection --------------------------------------------- //

downloadFrame.addEventListener('load', () => {
    if (loadingState.classList.contains('hidden')) return;
    try {
        const doc      = downloadFrame.contentDocument || downloadFrame.contentWindow.document;
        const bodyText = doc.body ? doc.body.innerText.trim() : '';
        
        // Debug: log any response
        console.log('[Download Iframe Load]', {
            hidden: loadingState.classList.contains('hidden'),
            bodyLength: bodyText.length,
            bodyPreview: bodyText.substring(0, 200),
            hasError: bodyText.length > 0 && bodyText.length < 2000
        });
        
        if (bodyText && bodyText.length < 2000) {
            stopPolling();
            if (isCookieRelatedBlock(bodyText)) {
                showForm();
                showCookieNotice();
                return;
            }
            
            // Check for rate-limit and show more helpful message
            const lowerBodyText = bodyText.toLowerCase();
            if (lowerBodyText.includes('rate') || 
                lowerBodyText.includes('bloqueou') ||
                lowerBodyText.includes('aguarde')) {
                showError(bodyText + '\n\n💡 Dica: Se necessário, espere alguns minutos e tente novamente.');
            } else {
                showError(bodyText);
            }
        } else if (bodyText && bodyText.length >= 2000) {
            // Large response - might be HTML page with error, try to extract text content
            console.warn('[Download] Large response received:', bodyText.substring(0, 500));
            stopPolling();
            showError('Erro ao processar download. Por favor, tente novamente.');
        }
    } catch (e) {
        console.error('[Download Iframe Error]', e);
        // Cross-origin or other iframe issues
    }
});

tryAgainBtn.addEventListener('click', () => { showForm(); urlInput.focus(); });

document.addEventListener('visibilitychange', () => {
    if (document.hidden) closeAudioPreview();
});

window.addEventListener('beforeunload', closeAudioPreview);

// ---- Download Counter --------------------------------------------------- //

function animateCount(el, from, to, duration) {
    if (from >= to) { el.textContent = to.toLocaleString(); return; }
    const startTime = performance.now();
    function step(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(from + (to - from) * eased).toLocaleString();
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

async function refreshCounter() {
    try {
        const res  = await fetch('stats.php');
        const data = await res.json();
        if (typeof data.count === 'number' && counterEl) {
            const current = parseInt(counterEl.textContent.replace(/\D/g, '')) || 0;
            animateCount(counterEl, current, data.count, 900);
        }
    } catch { /* ignore */ }
}

if (counterEl) {
    const target = parseInt(counterEl.dataset.target) || 0;
    setTimeout(() => animateCount(counterEl, 0, target, 1200), 400);
}