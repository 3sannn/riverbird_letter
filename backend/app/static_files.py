"""Static files with production cache headers."""
from __future__ import annotations

from fastapi.staticfiles import StaticFiles
from starlette.responses import Response
from starlette.types import Scope


class CachedStaticFiles(StaticFiles):
    """Long-lived cache for versioned static assets; safe for self-hosted vendor bundles."""

    async def get_response(self, path: str, scope: Scope) -> Response:
        response = await super().get_response(path, scope)
        if response.status_code != 200:
            return response
        lower = path.lower()
        if lower.endswith((".js", ".css", ".woff", ".woff2")):
            response.headers["Cache-Control"] = "public, max-age=604800, stale-while-revalidate=86400"
        elif lower.endswith((".png", ".jpg", ".jpeg", ".webp", ".svg", ".ico")):
            response.headers["Cache-Control"] = "public, max-age=2592000, stale-while-revalidate=86400"
        return response
