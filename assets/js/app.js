'use strict';

/* ================================================
   OmniDownloader — Frontend JavaScript
   © Andre Silva | andretsc.dev

   Flow:
   1. User pastes a URL (or clicks the Paste button)
   2. JS calls api.php to show a video preview card
   3. User selects format (Video / MP3) and clicks Download
   4. Form targets a hidden iframe → browser shows native Save As dialog
   5. PHP sets a cookie (fileDownloadToken) when the file stream begins
   6. JS polls for that cookie to detect download start → reset loading UI
   ================================================ */

// ---- DOM References ---------------------------------------------------- //

const urlInput      = document.getElementById('urlInput');
const pasteBtn      = document.getElementById('pasteBtn');
const clearBtn      = document.getElementById('clearBtn');
const downloadForm  = document.getElementById('downloadForm');
const loadingState  = document.getElementById('loadingState');
const errorState    = document.getElementById('errorState');
const errorMessage  = document.getElementById('errorMessage');
const tryAgainBtn   = document.getElementById('tryAgainBtn');
const videoPreview  = document.getElementById('videoPreview');
const videoTitle    = document.getElementById('videoTitle');
const videoThumb    = document.getElementById('videoThumb');
const videoDuration = document.getElementById('videoDuration');
const downloadFrame = document.getElementById('downloadFrame');

// ---- State ------------------------------------------------------------- //

let pollInterval  = null;
let timeoutHandle = null;
let infoTimer     = null;

// ---- Cookie Helpers ---------------------------------------------------- //

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function deleteCookie(name) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
}

// ---- Format Duration --------------------------------------------------- //

function formatDuration(seconds) {
    if (!seconds || seconds < 1) return '';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) {
        return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
    return `${m}:${String(s).padStart(2, '0')}`;
}

// ---- UI State Helpers -------------------------------------------------- //

function showLoading() {
    downloadForm.style.display = 'none';
    loadingState.classList.remove('hidden');
    errorState.classList.add('hidden');
}

function showForm() {
    downloadForm.style.display = '';
    loadingState.classList.add('hidden');
    errorState.classList.add('hidden');
}

function showError(msg) {
    downloadForm.style.display = '';
    loadingState.classList.add('hidden');
    errorState.classList.remove('hidden');
    errorMessage.textContent = msg || 'Ocorreu um erro inesperado. Tente novamente.';
}

function stopPolling() {
    clearInterval(pollInterval);
    clearTimeout(timeoutHandle);
    pollInterval  = null;
    timeoutHandle = null;
}

// ---- URL Input Interactions -------------------------------------------- //

urlInput.addEventListener('input', () => {
    const val = urlInput.value.trim();
    clearBtn.classList.toggle('hidden', !val);

    if (val) {
        scheduleInfoFetch(val);
    } else {
        videoPreview.classList.add('hidden');
        clearTimeout(infoTimer);
    }
});

clearBtn.addEventListener('click', () => {
    urlInput.value = '';
    clearBtn.classList.add('hidden');
    videoPreview.classList.add('hidden');
    clearTimeout(infoTimer);
    urlInput.focus();
});

// ---- Paste Button ------------------------------------------------------ //

pasteBtn.addEventListener('click', async () => {
    try {
        const text = await navigator.clipboard.readText();
        if (text && text.trim()) {
            urlInput.value = text.trim();
            clearBtn.classList.remove('hidden');
            urlInput.dispatchEvent(new Event('input'));
        }
    } catch {
        // Clipboard API unavailable or denied — focus the field for manual paste
        urlInput.focus();
        urlInput.select();
    }
});

// ---- Video Info Preview ------------------------------------------------ //

function scheduleInfoFetch(url) {
    clearTimeout(infoTimer);
    // Debounce: wait 700 ms after the user stops typing
    infoTimer = setTimeout(() => fetchVideoInfo(url), 700);
}

async function fetchVideoInfo(url) {
    if (!url || !/^https?:\/\//i.test(url)) return;

    try {
        const res = await fetch(`api.php?url=${encodeURIComponent(url)}`);
        if (!res.ok) return;

        const data = await res.json();
        if (data.error || !data.title) return;

        videoTitle.textContent    = data.title;
        videoDuration.textContent = data.duration ? '⏱ ' + formatDuration(data.duration) : '';

        if (data.thumbnail) {
            videoThumb.src            = data.thumbnail;
            videoThumb.style.display  = '';
        } else {
            videoThumb.style.display  = 'none';
        }

        videoPreview.classList.remove('hidden');
    } catch {
        // Info preview is optional — silently ignore errors
    }
}

// ---- Download Form Submission ------------------------------------------ //

downloadForm.addEventListener('submit', () => {
    // Clean any stale token cookie from a previous download
    deleteCookie('fileDownloadToken');

    showLoading();

    // Poll every 500 ms for the cookie that PHP sets when the file is ready
    pollInterval = setInterval(() => {
        if (getCookie('fileDownloadToken') === DOWNLOAD_TOKEN) {
            deleteCookie('fileDownloadToken');
            stopPolling();
            // Brief delay so the browser save dialog can appear before we reset UI
            setTimeout(() => {
                showForm();
                refreshCounter();
            }, 1500);
        }
    }, 500);

    // Safety timeout: 3 minutes
    timeoutHandle = setTimeout(() => {
        stopPolling();
        showError(
            'O download está demorando mais que o esperado.\n' +
            'Verifique se a URL é válida e tente novamente.'
        );
    }, 180_000);
});

// ---- Iframe Load (Error Detection) ------------------------------------- //
// When PHP returns a text/plain error instead of a file, the iframe will
// load with content. We read it and display as an error message.

downloadFrame.addEventListener('load', () => {
    // Only handle if we are currently in loading state
    if (loadingState.classList.contains('hidden')) return;

    try {
        const doc      = downloadFrame.contentDocument || downloadFrame.contentWindow.document;
        const bodyText = doc.body ? doc.body.innerText.trim() : '';

        // A file download response usually produces no readable body in the iframe.
        // An error response produces a short text message.
        if (bodyText && bodyText.length < 1000) {
            stopPolling();
            showError(bodyText);
        }
    } catch {
        // Cross-origin errors or empty body — ignore
    }
});

// ---- Try Again --------------------------------------------------------- //

tryAgainBtn.addEventListener('click', () => {
    showForm();
    urlInput.focus();
});

// ---- Download Counter -------------------------------------------------- //

const counterEl = document.getElementById('downloadCount');

function animateCount(el, from, to, duration) {
    if (from >= to) {
        el.textContent = to.toLocaleString('pt-BR');
        return;
    }
    const startTime = performance.now();
    function step(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        el.textContent = Math.round(from + (to - from) * eased).toLocaleString('pt-BR');
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
    } catch { /* silently ignore */ }
}

// Animate counter from 0 to real value on page load
if (counterEl) {
    const target = parseInt(counterEl.dataset.target) || 0;
    // Small delay so the page has rendered before animating
    setTimeout(() => animateCount(counterEl, 0, target, 1200), 400);
}
