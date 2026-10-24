/** Sanitize HTML for preview and export */
export function sanitizeHtml(dirty) {
  if (!dirty) return "";
  if (typeof window !== "undefined" && window.DOMPurify) {
    return window.DOMPurify.sanitize(dirty, {
      USE_PROFILES: { html: true },
      FORBID_TAGS: ["script", "style", "iframe", "object", "embed"],
    });
  }
  const div = document.createElement("div");
  div.textContent = dirty;
  return div.innerHTML;
}
