# Deploy RiverBird Document Studio to Render (via GitHub)

Step-by-step guide for hosting the app on [Render](https://render.com) Free tier with automatic deploys from GitHub.

## Prerequisites

- A [GitHub](https://github.com) account
- A [Render](https://render.com) account (sign in with GitHub)
- [Git](https://git-scm.com/) installed locally
- This project folder on your PC (`riverbird_letter`)

## Part 1 — Push the code to GitHub

### 1. Create a new repository on GitHub

1. Open [github.com/new](https://github.com/new).
2. **Repository name:** e.g. `riverbird-document-studio` (your choice).
3. Set **Public** or **Private** (either works with Render).
4. Do **not** add a README, `.gitignore`, or license (you already have them locally).
5. Click **Create repository**.

### 2. Initialize git and push (first time)

Open PowerShell in your project folder:

```powershell
cd E:\riverbird_letter

git status
```

If this is already a git repo with a remote, skip to **Part 2** or run `git remote -v` to confirm `origin` points to your GitHub repo.

**First-time push example:**

```powershell
git add .
git commit -m "RiverBird Document Studio — production-ready app"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git push -u origin main
```

Replace `YOUR_USERNAME` and `YOUR_REPO_NAME` with your GitHub details.

GitHub may ask you to sign in (browser or personal access token).

### 3. What must be in the repo

Render needs these paths (already in the project):

- `backend/requirements.txt`
- `backend/app/main.py`
- `frontend/` (including `frontend/assets/vendor/` — Quill, DOMPurify, html2pdf)
- `render.yaml` (optional but recommended for Blueprint deploy)
- `doc/` (reference Word files)

**Do not commit:** `backend/.venv/`, `frontend/node_modules/`, `.env` (listed in `.gitignore`).

---

## Part 2 — Create the Render Web Service

### Option A — Blueprint from `render.yaml` (recommended)

1. In Render: **Dashboard** → **Blueprints** → **New Blueprint Instance**.
2. Connect your GitHub account if prompted.
3. Select the repository you just pushed.
4. Render reads `render.yaml` and proposes a web service named `riverbird-document-studio`.
5. Review settings (Free plan, health check `/health`) → **Apply**.

### Option B — Manual Web Service

1. **Dashboard** → **New** → **Web Service**.
2. Connect the GitHub repository.
3. Configure:

| Field | Value |
|--------|--------|
| **Name** | `riverbird-document-studio` |
| **Region** | Closest to you (e.g. Singapore) |
| **Branch** | `main` |
| **Runtime** | Python 3 |
| **Build Command** | `pip install -r backend/requirements.txt` |
| **Start Command** | `cd backend && uvicorn app.main:app --host 0.0.0.0 --port $PORT --workers 1 --proxy-headers --forwarded-allow-ips='*' --no-access-log` |
| **Plan** | Free |

4. **Advanced** → **Health Check Path:** `/health`
5. **Environment** → add:
   - `PYTHON_VERSION` = `3.11.9`
   - `ENV` = `production`
6. Click **Create Web Service**.

---

## Part 3 — Wait for deploy and verify

1. Render runs **Build** then **Deploy** (usually 2–5 minutes first time).
2. When status is **Live**, open the URL: `https://riverbird-document-studio.onrender.com` (name may vary).
3. Check:
   - Dashboard loads
   - `https://YOUR-SERVICE.onrender.com/health` returns `{"status":"ok",...}`
   - Open **Editor** → preview updates; **Download PDF** works (first click loads PDF library)

### Free tier notes

- Service **sleeps after ~15 minutes** with no traffic; first visit after sleep can take **30–60 seconds** (cold start).
- No database or disk storage for drafts — drafts stay in the user’s browser.
- Redeploy happens automatically on every push to `main` if auto-deploy is enabled.

---

## Part 4 — Ongoing updates

```powershell
cd E:\riverbird_letter
git add .
git commit -m "Describe your change"
git push
```

Render rebuilds and redeploys from `main`.

---

## Troubleshooting

| Problem | What to do |
|---------|------------|
| Build fails on `pip install` | Confirm `backend/requirements.txt` exists and Python version is 3.11+ in Render env. |
| 502 / not loading | Check **Logs** in Render; ensure start command uses `$PORT` and `0.0.0.0`. |
| Static files 404 | Ensure `frontend/assets/` was committed (especially `vendor/`). |
| Health check fails | Path must be `/health` exactly. |
| Slow first load | Normal on Free tier after sleep; keep using `/health` for uptime monitors sparingly. |

---

## Custom domain (optional)

1. Render service → **Settings** → **Custom Domains**.
2. Add your domain and follow DNS instructions (CNAME to Render).
3. Enable **HTTPS** (automatic on Render).

---

## Security

This app has **no login**. Anyone with the URL can use it. For internal use only:

- Use a **private** GitHub repo.
- Do not share the Render URL publicly, or protect with Render/authentication add-ons if you add them later.
