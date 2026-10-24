"""RiverBird Document Studio — static frontend + health check."""
from __future__ import annotations

import os
from pathlib import Path

from fastapi import FastAPI, HTTPException
from fastapi.middleware.gzip import GZipMiddleware
from fastapi.responses import FileResponse
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import Response

from app.static_files import CachedStaticFiles

ROOT = Path(__file__).resolve().parents[2]
FRONTEND = ROOT / "frontend"
ASSETS = FRONTEND / "assets"
IS_PRODUCTION = os.getenv("ENV", "production").lower() in ("production", "prod", "1", "true")

app = FastAPI(
    title="RiverBird Document Studio",
    version="1.0.0",
    docs_url=None if IS_PRODUCTION else "/docs",
    redoc_url=None if IS_PRODUCTION else "/redoc",
    openapi_url=None if IS_PRODUCTION else "/openapi.json",
)

app.add_middleware(GZipMiddleware, minimum_size=500)


class SecurityHeadersMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next) -> Response:
        response = await call_next(request)
        response.headers.setdefault("X-Content-Type-Options", "nosniff")
        response.headers.setdefault("X-Frame-Options", "SAMEORIGIN")
        response.headers.setdefault("Referrer-Policy", "strict-origin-when-cross-origin")
        response.headers.setdefault("Permissions-Policy", "geolocation=(), microphone=(), camera=()")
        return response


app.add_middleware(SecurityHeadersMiddleware)

if ASSETS.is_dir():
    app.mount("/assets", CachedStaticFiles(directory=str(ASSETS)), name="assets")


def _html_page(name: str) -> FileResponse:
    path = FRONTEND / name
    if not path.is_file():
        raise HTTPException(status_code=404, detail=f"Page not found: {name}")
    response = FileResponse(path, media_type="text/html; charset=utf-8")
    response.headers["Cache-Control"] = "no-cache"
    return response


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok", "service": "riverbird-document-studio"}


@app.get("/")
def dashboard() -> FileResponse:
    return _html_page("index.html")


@app.get("/templates")
def templates_page() -> FileResponse:
    return _html_page("templates.html")


@app.get("/editor")
def editor_page() -> FileResponse:
    return _html_page("editor.html")


@app.get("/favicon.ico", include_in_schema=False)
def favicon() -> FileResponse:
    logo = ASSETS / "images" / "riverbird-logo.png"
    if logo.is_file():
        response = FileResponse(logo, media_type="image/png")
        response.headers["Cache-Control"] = "public, max-age=2592000"
        return response
    raise HTTPException(status_code=404)
