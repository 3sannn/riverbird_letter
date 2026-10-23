# PHP → FastAPI + Neon migration plan

## Phase 1 audit (completed)

| Area | PHP implementation | Python target |
|------|-------------------|---------------|
| Auth | `app/Auth`, `/setup` (open when empty) | FastAPI sessions; **admin via CLI only** (`create_admin`) |
| DB | MySQL `database/schema.sql` | Neon PostgreSQL + Alembic |
| Clients | `ClientRepository` | `Client` model + `/api/clients` |
| Documents | `DocumentService`, `DocumentRepository` | `DocumentService` + `/api/documents` |
| Invoice numbers | `InvoiceNumberService` + `FOR UPDATE` | Same logic with SQLAlchemy + `SELECT … FOR UPDATE` |
| Payments | `PaymentRepository` | `Payment` + service |
| PDF | Dompdf | Jinja2 HTML + `xhtml2pdf` |
| Files | `storage/` + `FileController` | `STORAGE_PATH` + `/api/files/{id}` |
| UI | PHP views in `views/` | `frontend/` HTML/CSS/JS |
| Templates | `templates/invoice` etc. | `backend/app/templates/documents/` (Jinja2) |
| Tests | PHPUnit | pytest |

Original reference files: `doc/` (never modified).

Historical data: seed script `backend/scripts/import_reference_data.py` (optional).

## Git preservation

- Branch `legacy/php-portal`: snapshot of the PHP stack before Python becomes primary.
- Primary development: `main` with `backend/` + `frontend/`.

## Hosting (verified constraints)

**Hostinger Shared Web Hosting** typically provides:

- PHP + static files + MariaDB
- **Not** a persistent ASGI/FastAPI process

**Recommended architecture**

| Component | Host |
|-----------|------|
| Static frontend (`frontend/`) | Hostinger `public_html` or subdomain |
| FastAPI API (`backend/`) | Render / Railway / Fly.io free or low-cost tier |
| PostgreSQL | Neon Free tier (`sslmode=require`) |
| PDF/uploads | Persistent disk on API host **or** Hostinger private folder mounted via API only |

Neon stores **rows only** — files remain on API host storage (or S3-compatible later).

## Manual steps for you

1. Create Neon project → copy `DATABASE_URL` (with `sslmode=require`).
2. Deploy API to Render (or similar) with env vars from `.env.example`.
3. Run `alembic upgrade head` on deploy.
4. Run `python -m scripts.create_admin` once.
5. Upload `frontend/` to Hostinger; set `frontend/assets/js/config.js` → `API_BASE_URL`.
6. Enable HTTPS on both hosts; set `FRONTEND_URL` on API for CORS.
