# RiverBird Document Studio

Lightweight internal document editor for **RiverBird Digital Solutions Pvt Ltd**. Create and edit **invoices** and **proposals** from company templates, preview on A4, and download **PDF**, **HTML**, or **TXT**. Drafts are stored in the browser (no database, no login).

Original reference Word files live in [`doc/`](doc/) and are never modified by the app.

## Architecture

| Layer | Technology |
|--------|------------|
| Server | Python 3.11+, FastAPI, Uvicorn |
| Frontend | HTML5, CSS3, vanilla JavaScript (ES modules) |
| Editing | Quill (self-hosted under `frontend/assets/vendor/`) |
| PDF | html2pdf.js (client-side); optional **Print → Save as PDF** |
| Drafts | `localStorage` in the browser |

No PHP, MySQL/Neon, AWS S3, or authentication.

## Folder structure

```
backend/app/main.py          FastAPI app, static assets, /health
frontend/                    Dashboard, templates, editor UI
frontend/assets/js/          Editor logic, previews, drafts, downloads
doc/                         Original .docx reference documents
render.yaml                  Render Free Web Service config
```

## Local setup

```bash
cd backend
python -m venv .venv
.venv\Scripts\activate          # Windows
pip install -r requirements.txt
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

Health check: [http://127.0.0.1:8000/health](http://127.0.0.1:8000/health)

### Frontend vendor libraries (optional rebuild)

```bash
cd frontend
npm install
# Copy dist files into frontend/assets/vendor/ (quill, dompurify, html2pdf.js)
```

Committed copies under `frontend/assets/vendor/` are used at runtime.

## Production behavior

- **GZip** compression for HTML/CSS/JS responses
- **Cache-Control** on static assets (images, CSS, JS) for faster repeat visits
- **Security headers** (`X-Content-Type-Options`, `X-Frame-Options`, etc.)
- **Lazy-loaded PDF library** (html2pdf only when you click Download PDF)
- **Throttled live preview** (`requestAnimationFrame`) so typing stays smooth
- **Deferred** vendor scripts on the editor page

Set `ENV=production` on Render (included in `render.yaml`) to hide OpenAPI `/docs` in production.

## Render deployment (GitHub)

Full step-by-step: **[docs/DEPLOY_RENDER_GITHUB.md](docs/DEPLOY_RENDER_GITHUB.md)**

Quick reference:

1. Push this repo to GitHub (`main` branch).
2. Render → **New Blueprint** (uses `render.yaml`) or **New Web Service** with:
   - **Build:** `pip install -r backend/requirements.txt`
   - **Start:** `cd backend && uvicorn app.main:app --host 0.0.0.0 --port $PORT --workers 1 --proxy-headers --forwarded-allow-ips='*' --no-access-log`
   - **Health check:** `/health`
3. Environment: `PYTHON_VERSION=3.11.9`, `ENV=production`

User drafts are **not** stored on Render’s disk. Export JSON/HTML/PDF locally for backups.

## Using the app

1. **Dashboard** — choose Invoice billings or Proposal, or resume a draft.
2. **Templates** — new document or reference examples:
   - Invoices: `RB2026010` (website), `RB-092602` (GMB SEO)
   - Proposal: Mano Bakery SMM starter content
3. **Editor** — structured fields + rich text; live A4 preview.
4. **Download** — PDF (browser), HTML (standalone with CSS), TXT, or Print.

### Invoice numbers

New invoices suggest format `RB-MMYYNN` using a **local** counter in `localStorage`. This is **not** globally unique across devices or browsers. Always review the number before sending.

### Drafts

- Auto-saved in this browser profile.
- **Export JSON** / **Import JSON** to move work between machines.
- **Delete draft** removes only the local copy.

## Browser support

Tested targets: current Chrome, Edge, Firefox, Safari. PDF layout quality is best in Chromium-based browsers.

## Adding or changing templates

1. Edit seed data in `frontend/assets/js/template-seeds.js`.
2. Adjust layout in `frontend/assets/js/document-preview.js` and `frontend/assets/css/documents.css`.
3. Add a card in `template-manager.js` and `templates.html` if needed.

## Known limitations

- No shared storage between team members without JSON export.
- Invoice sequence is not concurrency-safe without a database.
- Render Free ephemeral filesystem is not used for user documents by design.
- `window.print()` does not download a file automatically; users must choose “Save as PDF” in the print dialog.

## Tests performed

- FastAPI `/health` (run locally after `uvicorn` start)
- Manual checks recommended: editor preview, totals, PDF/HTML download, JSON import/export, mobile Editor/Preview tabs
