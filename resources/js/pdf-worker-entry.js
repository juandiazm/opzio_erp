// PDF.js worker entry point.
// Importing this file as a separate Vite chunk causes it to be output
// as a .js file (not .mjs), so the server serves it with the correct
// MIME type regardless of whether mod_mime / .htaccess is active.
import 'pdfjs-dist/build/pdf.worker.min.mjs';
