import { renderDocument } from "./document-preview.js";
import { sanitizeFilename } from "./local-drafts.js";

const DOCUMENT_CSS = `
  @import url('/assets/css/documents.css');
  @import url('/assets/css/print.css');
`;

export function buildStandaloneHtml(data) {
  const body = renderDocument(data);
  const css = window.__RBDS_EXPORT_CSS || "";
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>${escapeTitle(data)}</title>
  <style>${css}</style>
</head>
<body class="export-body">
  <div class="export-wrap">${body}</div>
</body>
</html>`;
}

function escapeTitle(data) {
  if (data.kind === "invoice") return data.invoiceNumber || "Invoice";
  return data.title || "Proposal";
}

export async function preloadExportCss() {
  const files = ["/assets/css/documents.css", "/assets/css/print.css"];
  const parts = [];
  for (const f of files) {
    const res = await fetch(f);
    if (res.ok) parts.push(await res.text());
  }
  window.__RBDS_EXPORT_CSS = parts.join("\n");
}

export function downloadHtml(data) {
  const html = buildStandaloneHtml(data);
  const blob = new Blob([html], { type: "text/html;charset=utf-8" });
  triggerDownload(blob, `${baseFilename(data)}.html`);
}

export function downloadText(data) {
  const el = document.createElement("div");
  el.innerHTML = renderDocument(data);
  const text = el.innerText;
  const blob = new Blob([text], { type: "text/plain;charset=utf-8" });
  triggerDownload(blob, `${baseFilename(data)}.txt`);
}

let html2pdfLoadPromise = null;

export function loadHtml2Pdf() {
  if (window.html2pdf) return Promise.resolve();
  if (html2pdfLoadPromise) return html2pdfLoadPromise;
  html2pdfLoadPromise = new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = "/assets/vendor/html2pdf/html2pdf.bundle.min.js";
    s.async = true;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error("Could not load PDF library"));
    document.head.appendChild(s);
  });
  return html2pdfLoadPromise;
}

export async function downloadPdf(previewRoot) {
  const node = previewRoot.querySelector(".doc-sheet");
  if (!node) throw new Error("Nothing to export");
  await loadHtml2Pdf();
  if (!window.html2pdf) throw new Error("PDF library not loaded");

  const opt = {
    margin: [8, 8, 12, 8],
    filename: `${baseFilenameFromNode(node)}.pdf`,
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2.25, useCORS: true, logging: false, allowTaint: true },
    jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
    pagebreak: { mode: ["css", "legacy"], before: ".page-break-before" },
  };

  await window.html2pdf().set(opt).from(node).save();
}

export function printDocument() {
  window.print();
}

function baseFilename(data) {
  if (data.kind === "invoice") {
    return sanitizeFilename(data.invoiceNumber || "Invoice");
  }
  const client = data.clientName || "Proposal";
  return sanitizeFilename(`${client}-Proposal`);
}

function baseFilenameFromNode(node) {
  const kind = node.dataset.document;
  if (kind === "invoice") {
    const num = node.querySelector(".doc-meta-line strong")?.parentElement?.textContent?.replace(/INVOICE NO\./i, "").trim();
    return sanitizeFilename(num || "Invoice");
  }
  const t = node.querySelector(".proposal-main-title")?.textContent || "Proposal";
  return sanitizeFilename(t);
}

function triggerDownload(blob, filename) {
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  URL.revokeObjectURL(a.href);
}
