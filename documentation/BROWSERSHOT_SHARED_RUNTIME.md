# Browsershot shared runtime

ERP PDF generation uses `App\Services\Pdf\BrowsershotFactory` for all Browsershot instances. In production, its defaults point to `/usr/bin/node`, `/opt/opzio/browsershot/node_modules`, and `/opt/opzio/browsershot/cache`, which should contain Node 22 and Puppeteer 24.40.0. Set `BROWSERSHOT_NODE_MODULES`, `BROWSERSHOT_PUPPETEER_CACHE`, `PDF_NODE_BINARY`, or `PDF_CHROME_PATH` in `.env` to override those paths. The older `PDF_NODE_MODULE_PATH` setting remains a local-development fallback. Without `PDF_CHROME_PATH`, Windows uses the configured local Chrome path while Linux uses Puppeteer's installed Chrome.

PDF HTML is rendered from project-local temporary files with local assets inlined, so navigation waits for the document `load` event rather than strict `networkidle0`. This avoids waiting indefinitely on background or persistent network requests that are not needed to print the already-rendered report.

The factory checks that Puppeteer is readable, that the configured browser cache exists, and that each application's own `storage/app/runtime/browsershot` directories are writable. It keeps temporary files, `HOME`, XDG directories, and the Node temporary directory inside the current project; it never creates or uses another application's runtime directories. The shared Puppeteer modules and Chrome cache must be readable and executable by the PHP-FPM and queue-worker accounts. Project runtime directories are created by the account generating the PDF with mode `0770`; existing inaccessible directories produce an explicit error.

`BROWSERSHOT_NO_SANDBOX` defaults to enabled in production to preserve the existing server behavior and disabled in local development. Set it to `false` only after confirming Chromium can use its sandbox in that environment.

The ERP production and QA deployment workflows run `php artisan browsershot:smoke-test` as the detected PHP-FPM pool user after deployment. The command generates a PDF and fails the deployment unless the output begins with a PDF signature. They also restart Laravel queue workers after deployment so long-lived workers load the deployed code.

PDF headers embed the 472×221 `public/images/opzio-logo-pdf.png` asset instead of the 6057×2838 source logo. This preserves the existing print dimensions while avoiding repeatedly decoding a 17-megapixel image for each report.

CRM does not use Browsershot: its PDF generation uses Dompdf, which does not require Node.js, Puppeteer, Chrome, or a project `node_modules` directory. Its existing quotation PDF test exercises that path.
