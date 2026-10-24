import { renderDocument } from "./document-preview.js";
import { sanitizeFilename } from "./local-drafts.js";

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

/** A4 at 96 CSS px/in (slightly under height avoids html2pdf spill to page 2) */
const A4_WIDTH_PX = 794;
const A4_HEIGHT_PX = 1122;

function prepareInvoicePdfNode(node) {
  const prev = {
    width: node.style.width,
    height: node.style.height,
    minHeight: node.style.minHeight,
    maxHeight: node.style.maxHeight,
    boxShadow: node.style.boxShadow,
    className: node.className,
  };
  node.classList.add("invoice-pdf-export");
  node.style.width = `${A4_WIDTH_PX}px`;
  node.style.height = `${A4_HEIGHT_PX}px`;
  node.style.minHeight = `${A4_HEIGHT_PX}px`;
  node.style.maxHeight = `${A4_HEIGHT_PX}px`;
  node.style.boxShadow = "none";
  return prev;
}

function restoreInvoicePdfNode(node, prev) {
  node.className = prev.className;
  node.style.width = prev.width;
  node.style.height = prev.height;
  node.style.minHeight = prev.minHeight;
  node.style.maxHeight = prev.maxHeight;
  node.style.boxShadow = prev.boxShadow;
}

async function saveInvoicePdf(node, filename) {
  const opt = {
    margin: 0,
    filename,
    image: { type: "jpeg", quality: 0.96 },
    html2canvas: {
      scale: 2,
      useCORS: true,
      logging: false,
      allowTaint: true,
      scrollX: 0,
      scrollY: -window.scrollY,
      width: A4_WIDTH_PX,
      height: A4_HEIGHT_PX,
      windowWidth: A4_WIDTH_PX,
      windowHeight: A4_HEIGHT_PX,
    },
    jsPDF: { unit: "mm", format: "a4", orientation: "portrait", compress: true },
    pagebreak: { mode: [] },
  };

  const pdf = await window.html2pdf().set(opt).from(node).toPdf().get("pdf");
  let pages = pdf.internal.getNumberOfPages();
  while (pages > 1) {
    pdf.deletePage(pages);
    pages = pdf.internal.getNumberOfPages();
  }
  pdf.save(filename);
}

export async function downloadPdf(previewRoot) {
  const node = previewRoot.querySelector(".doc-sheet");
  if (!node) throw new Error("Nothing to export");
  await loadHtml2Pdf();
  if (!window.html2pdf) throw new Error("PDF library not loaded");

  const filename = `${baseFilenameFromNode(node)}.pdf`;
  const isInvoice = node.classList.contains("invoice-doc");
  const invoicePrev = isInvoice ? prepareInvoicePdfNode(node) : null;

  await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));

  try {
    if (isInvoice) {
      await saveInvoicePdf(node, filename);
      return;
    }

    const opt = {
      margin: [6, 6, 6, 6],
      filename,
      image: { type: "jpeg", quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true, logging: false, allowTaint: true },
      jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
      pagebreak: { mode: ["css", "legacy"] },
    };
    await window.html2pdf().set(opt).from(node).save();
  } finally {
    if (isInvoice && invoicePrev) restoreInvoicePdfNode(node, invoicePrev);
  }
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
    const num = node.querySelector(".inv-number-value")?.textContent?.trim();
    return sanitizeFilename(num || "Invoice");
  }
  const t = node.querySelector(".prop-title")?.textContent || "Proposal";
  return sanitizeFilename(t);
}

function triggerDownload(blob, filename) {
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  URL.revokeObjectURL(a.href);
}
