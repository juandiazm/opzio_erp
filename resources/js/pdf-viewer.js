import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

// Let Vite bundle the worker as a hashed asset so the URL is always correct.
pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorkerUrl;

let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1.0;
let rendering = false;
let currentUrl = null;

export async function loadPdfViewer(url) {
    currentUrl = url;
    currentPage = 1;
    scale = 1.0;

    $('#pdf-loading').show().html('<i class="fa-solid fa-spinner fa-spin"></i>');
    $('#pdf-canvas').hide().css('width', '');

    try {
        if (pdfDoc && typeof pdfDoc.destroy === 'function') {
            try {
                pdfDoc.destroy();
            } catch (e) {
                console.error('Error destroying previous PDF:', e);
            }
        }
        pdfDoc = null;

        // Use browser-native fetch (handles session cookies, no range requests)
        // so PDF.js never makes partial/range fetches that WAMP may mishandle
        const response = await fetch(url, { credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const data = await response.arrayBuffer();

        const loadingTask = pdfjsLib.getDocument({ data });
        pdfDoc = await loadingTask.promise;
        totalPages = pdfDoc.numPages;
        $('#pdf-page-count').text(totalPages);
        await renderCurrentPage();
    } catch (error) {
        console.error('Error loading PDF:', error);
        $('#pdf-loading').html('<i class="fa-solid fa-triangle-exclamation"></i> Error al cargar el PDF');
    }
}

async function renderCurrentPage() {
    if (!pdfDoc || rendering) return;
    rendering = true;

    $('#pdf-page-num').text(currentPage);

    try {
        const page = await pdfDoc.getPage(currentPage);
        const container = document.getElementById('pdf-canvas-container');
        const containerWidth = container.clientWidth - 48;

        const baseViewport = page.getViewport({ scale: 1 });
        const fitScale = containerWidth / baseViewport.width;
        const viewport = page.getViewport({ scale: fitScale * scale });

        const canvas = document.getElementById('pdf-canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = viewport.width;
        canvas.height = viewport.height;

        await page.render({ canvasContext: ctx, viewport }).promise;

        $('#pdf-loading').hide();
        $('#pdf-canvas').show();
    } catch (error) {
        console.error('Error rendering page:', error);
    }

    rendering = false;
}

export function pdfPrevPage() {
    if (currentPage <= 1) return;
    currentPage--;
    renderCurrentPage();
}

export function pdfNextPage() {
    if (currentPage >= totalPages) return;
    currentPage++;
    renderCurrentPage();
}

export function pdfZoomIn() {
    scale = Math.min(scale + 0.25, 3.0);
    renderCurrentPage();
}

export function pdfZoomOut() {
    scale = Math.max(scale - 0.25, 0.5);
    renderCurrentPage();
}

export function printPdf() {
    if (currentUrl) {
        const w = window.open(currentUrl, '_blank');
        if (w) {
            w.addEventListener('load', () => w.print());
        }
    }
}

export function downloadPdf() {
    if (!currentUrl) return;
    const base = currentUrl.split('?')[0];
    const a = document.createElement('a');
    a.href = base;
    a.download = base.split('/').pop();
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

export async function sharePdf() {
    if (!currentUrl) return;
    const base = currentUrl.split('?')[0];
    const fullUrl = window.location.origin + base;
    if (navigator.share) {
        try {
            await navigator.share({ url: fullUrl });
        } catch (_) {}
    } else {
        try {
            await navigator.clipboard.writeText(fullUrl);
            const btn = document.getElementById('pdf-share');
            if (btn) {
                const icon = btn.querySelector('i');
                icon.className = 'fa-solid fa-check';
                setTimeout(() => { icon.className = 'fa-solid fa-share-nodes'; }, 2000);
            }
        } catch (_) {}
    }
}

export function fullscreenPdf() {
    const el = document.getElementById('order-viewer');
    if (!document.fullscreenElement) {
        el.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
}

// Call once after DOM is ready to keep the fullscreen icon in sync
export function initPdfViewer() {
    document.addEventListener('fullscreenchange', () => {
        const icon = document.querySelector('#pdf-fullscreen i');
        if (!icon) return;
        if (document.fullscreenElement) {
            icon.className = 'fa-solid fa-compress';
        } else {
            icon.className = 'fa-solid fa-expand';
        }
    });
}

export function destroyPdfViewer() {
    if (pdfDoc && typeof pdfDoc.destroy === 'function') {
        try {
            pdfDoc.destroy();
        } catch (e) {
            console.error('Error destroying PDF:', e);
        }
    }
    pdfDoc = null;
    rendering = false;
    currentUrl = null;
    currentPage = 1;
    totalPages = 0;
    $('#pdf-loading').show();
    $('#pdf-canvas').hide();
    $('#pdf-page-num').text('1');
    $('#pdf-page-count').text('-');
}
