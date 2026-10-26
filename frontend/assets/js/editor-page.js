import { renderDocument } from "./document-preview.js";
import { getTemplateSeed } from "./template-seeds.js";
import {
  deleteDraft,
  exportDraftJson,
  getDraft,
  importDraftJson,
  saveDraft,
} from "./local-drafts.js";
import { suggestInvoiceNumber, invoiceNumberWarning } from "./invoice-number.js";
import { downloadHtml, downloadPdf, downloadText, preloadExportCss, printDocument } from "./download.js";
import { invoiceTotals, formatINR, fromPaise } from "./money.js";

const params = new URLSearchParams(location.search);
const kind = params.get("kind") || "invoice";
const templateId = params.get("template") || (kind === "invoice" ? "invoice-blank" : "proposal-blank");
const draftId = params.get("draft");

let state = null;
let saveTimer = null;
let previewRaf = null;
let previewDirty = false;
let quillInstances = {};

const els = {
  form: document.getElementById("editor-form"),
  preview: document.getElementById("preview-root"),
  saveStatus: document.getElementById("save-status"),
  viewTabs: document.querySelectorAll("[data-view-tab]"),
  workspace: document.getElementById("editor-workspace"),
  invoicePanel: document.getElementById("invoice-fields"),
  proposalPanel: document.getElementById("proposal-fields"),
  lineItemsBody: document.getElementById("line-items-body"),
  serviceRowsBody: document.getElementById("service-rows-body"),
  deliverablesBody: document.getElementById("deliverables-body"),
};

function normalizeInvoiceDraft(draft) {
  if (draft?.kind !== "invoice") return draft;
  if (draft.discountPercent == null && draft.discount != null) {
    const d = Number(draft.discount);
    draft.discountPercent = Number.isFinite(d) && d <= 100 ? d : 0;
  }
  if (draft.discountPercent == null) draft.discountPercent = 0;
  delete draft.discount;
  return draft;
}

function initState() {
  if (draftId) {
    const existing = getDraft(draftId);
    if (existing) {
      state = normalizeInvoiceDraft(existing);
      return;
    }
  }
  state = normalizeInvoiceDraft(getTemplateSeed(kind, templateId));
  state.id = crypto.randomUUID();
  if (kind === "invoice" && !state.invoiceNumber && templateId === "invoice-blank") {
    state.invoiceNumber = suggestInvoiceNumber();
  }
  state.title =
    state.title ||
    (kind === "invoice" ? `Invoice ${state.invoiceNumber || ""}`.trim() : state.title);
}

function mountQuill(selector, initialHtml, onChange) {
  const node = document.querySelector(selector);
  if (!node || !window.Quill) return null;
  const q = new window.Quill(node, {
    theme: "snow",
    modules: {
      toolbar: [
        ["bold", "italic", "underline"],
        [{ header: [2, 3, false] }],
        [{ size: ["small", false, "large"] }],
        [{ align: [] }],
        [{ list: "ordered" }, { list: "bullet" }],
        ["link"],
        ["clean"],
      ],
    },
  });
  q.root.innerHTML = initialHtml || "";
  q.on("text-change", () => onChange(q.root.innerHTML));
  return q;
}

function setupQuills() {
  if (kind === "invoice") {
    quillInstances.remarks = mountQuill("#remarks-editor", state.remarksHtml || `<p>${state.remarks || ""}</p>`, (html) => {
      state.remarksHtml = html;
      scheduleSave();
      queuePreviewUpdate();
    });
  } else {
    quillInstances.intro = mountQuill("#intro-editor", state.introductionHtml, (html) => {
      state.introductionHtml = html;
      scheduleSave();
      queuePreviewUpdate();
    });
    quillInstances.scope = mountQuill("#scope-editor", state.scopeHtml, (html) => {
      state.scopeHtml = html;
      scheduleSave();
      queuePreviewUpdate();
    });
    quillInstances.terms = mountQuill("#terms-editor", state.termsHtml, (html) => {
      state.termsHtml = html;
      scheduleSave();
      queuePreviewUpdate();
    });
    quillInstances.conclusion = mountQuill("#conclusion-editor", state.conclusionHtml, (html) => {
      state.conclusionHtml = html;
      scheduleSave();
      queuePreviewUpdate();
    });
    quillInstances.approval = mountQuill("#approval-editor", state.approvalHtml, (html) => {
      state.approvalHtml = html;
      scheduleSave();
      queuePreviewUpdate();
    });
  }
}

function queuePreviewUpdate() {
  previewDirty = true;
  if (previewRaf != null) return;
  previewRaf = requestAnimationFrame(() => {
    previewRaf = null;
    if (!previewDirty) return;
    previewDirty = false;
    readForm();
    refreshPreview();
    if (kind === "invoice") updateTotalsSummary();
  });
}

function onFormChange() {
  readForm();
  scheduleSave();
  queuePreviewUpdate();
}

function bindCommon() {
  document.getElementById("btn-back")?.addEventListener("click", () => {
    location.href = "/templates?category=" + kind;
  });
  document.getElementById("btn-dashboard")?.addEventListener("click", () => {
    location.href = "/";
  });

  els.viewTabs.forEach((tab) => {
    tab.addEventListener("click", () => setView(tab.dataset.viewTab));
  });

  document.getElementById("btn-export-json")?.addEventListener("click", () => exportDraftJson(state));
  document.getElementById("btn-import-json")?.addEventListener("click", () => {
    document.getElementById("import-json-input")?.click();
  });
  document.getElementById("import-json-input")?.addEventListener("change", async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!confirm("Import will replace the current draft in the editor. Continue?")) return;
    try {
      state = await importDraftJson(file);
      hydrateForm();
      refreshPreview();
      flashSave("Imported backup");
    } catch (err) {
      alert(err.message || "Import failed");
    }
    e.target.value = "";
  });

  document.getElementById("btn-download-html")?.addEventListener("click", () => downloadHtml(state));
  document.getElementById("btn-download-txt")?.addEventListener("click", () => downloadText(state));
  document.getElementById("btn-download-pdf")?.addEventListener("click", async () => {
    try {
      await downloadPdf(els.preview);
    } catch (e) {
      alert("PDF export failed. Try Print → Save as PDF instead.\n\n" + (e.message || e));
    }
  });
  document.getElementById("btn-print")?.addEventListener("click", () => printDocument());

  document.getElementById("btn-delete-draft")?.addEventListener("click", () => {
    if (!confirm("Delete this draft from this browser?")) return;
    deleteDraft(state.id);
    location.href = "/";
  });

  els.form?.addEventListener("input", onFormChange);
  els.form?.addEventListener("change", onFormChange);
}

function setView(view) {
  if (els.workspace) els.workspace.dataset.view = view;
  els.viewTabs.forEach((t) => t.classList.toggle("is-active", t.dataset.viewTab === view));
}

function scheduleSave() {
  if (saveTimer) clearTimeout(saveTimer);
  els.saveStatus.textContent = "Saving…";
  saveTimer = setTimeout(() => {
    try {
      saveDraft(state);
      flashSave("Saved locally");
    } catch {
      flashSave("Save failed");
    }
  }, 650);
}

function flashSave(msg) {
  els.saveStatus.textContent = msg;
}

function readForm() {
  if (kind === "invoice") readInvoiceForm();
  else readProposalForm();
}

function readInvoiceForm() {
  const f = els.form;
  state.title = f.title.value;
  state.invoiceDate = f.invoiceDate.value;
  state.invoiceNumber = f.invoiceNumber.value;
  state.billTo = {
    name: f.billName.value,
    business: f.billBusiness.value,
    address: f.billAddress.value,
  };
  if (f.copyShipFromBill.checked) {
    state.copyShipFromBill = true;
    state.shipTo = { ...state.billTo };
  } else {
    state.copyShipFromBill = false;
    state.shipTo = {
      name: f.shipName.value,
      business: f.shipBusiness.value,
      address: f.shipAddress.value,
    };
  }
  state.discountPercent = parseFloat(f.discountPercent.value) || 0;
  state.shipping = parseFloat(f.shipping.value) || 0;
  state.amountReceived = parseFloat(f.amountReceived.value) || 0;
  state.paymentStatus = f.paymentStatus.value;
  state.lineItems = [...els.lineItemsBody.querySelectorAll("tr")].map((row) => ({
    description: row.querySelector("[data-field=description]")?.value || "",
    quantity: parseFloat(row.querySelector("[data-field=quantity]")?.value) || 1,
    unitPrice: parseFloat(row.querySelector("[data-field=unitPrice]")?.value) || 0,
  }));
  const totals = invoiceTotals(state);
  if (state.paymentStatus === "paid" && state.amountReceived === 0 && totals.total > 0) {
    state.amountReceived = fromPaise(totals.total);
    f.amountReceived.value = String(state.amountReceived);
  }
  updateTotalsSummary();
}

function readProposalForm() {
  const f = els.form;
  state.title = f.title.value;
  state.clientName = f.clientName.value;
  state.preparedBy = f.preparedBy.value;
  state.proposalDate = f.proposalDate.value;
  state.serviceRows = [...els.serviceRowsBody.querySelectorAll("tr")].map((row) => ({
    service: row.querySelector("[data-field=service]")?.value || "",
    amountPerMonth: parseFloat(row.querySelector("[data-field=amount]")?.value) || 0,
  }));
  state.deliverables = [...els.deliverablesBody.querySelectorAll("tr")].map((row) => ({
    label: row.querySelector("[data-field=label]")?.value || "",
    quantity: row.querySelector("[data-field=quantity]")?.value || "",
  }));
}

function hydrateForm() {
  if (kind === "invoice") hydrateInvoice();
  else hydrateProposal();
  refreshPreview();
}

function hydrateInvoice() {
  els.invoicePanel?.classList.remove("hidden");
  els.proposalPanel?.classList.add("hidden");
  const f = els.form;
  f.title.value = state.title || "";
  f.invoiceDate.value = state.invoiceDate || "";
  f.invoiceNumber.value = state.invoiceNumber || "";
  f.billName.value = state.billTo?.name || "";
  f.billBusiness.value = state.billTo?.business || "";
  f.billAddress.value = state.billTo?.address || "";
  f.copyShipFromBill.checked = !!state.copyShipFromBill;
  toggleShipFields();
  f.shipName.value = state.shipTo?.name || "";
  f.shipBusiness.value = state.shipTo?.business || "";
  f.shipAddress.value = state.shipTo?.address || "";
  f.discountPercent.value = state.discountPercent ?? 0;
  f.shipping.value = state.shipping ?? 0;
  f.amountReceived.value = state.amountReceived ?? 0;
  f.paymentStatus.value = state.paymentStatus || "unpaid";
  renderLineItems();
  updateTotalsSummary();
  document.getElementById("invoice-number-warning").textContent = invoiceNumberWarning();
}

function hydrateProposal() {
  els.proposalPanel?.classList.remove("hidden");
  els.invoicePanel?.classList.add("hidden");
  const f = els.form;
  f.title.value = state.title || "";
  f.clientName.value = state.clientName || "";
  f.preparedBy.value = state.preparedBy || "RiverBird.in";
  f.proposalDate.value = state.proposalDate || "";
  renderServiceRows();
  renderDeliverables();
}

function toggleShipFields() {
  const disabled = els.form.copyShipFromBill.checked;
  ["shipName", "shipBusiness", "shipAddress"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.disabled = disabled;
  });
}

function renderLineItems() {
  els.lineItemsBody.innerHTML = "";
  (state.lineItems || []).forEach((row, idx) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${idx + 1}</td>
      <td><input data-field="description" type="text" value="${escapeAttr(row.description)}" /></td>
      <td><input data-field="quantity" type="text" inputmode="numeric" value="${row.quantity ?? 1}" class="input-narrow" autocomplete="off" /></td>
      <td><input data-field="unitPrice" type="text" inputmode="decimal" value="${row.unitPrice ?? 0}" class="input-narrow" autocomplete="off" /></td>
      <td><button type="button" class="btn-icon" data-remove-row aria-label="Remove row">×</button></td>`;
    els.lineItemsBody.appendChild(tr);
  });
  bindRowButtons();
}

function renderServiceRows() {
  els.serviceRowsBody.innerHTML = "";
  (state.serviceRows || []).forEach((row) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td><input data-field="service" type="text" value="${escapeAttr(row.service)}" /></td>
      <td><input data-field="amount" type="number" min="0" step="1" value="${row.amountPerMonth ?? 0}" class="input-narrow" /></td>
      <td><button type="button" class="btn-icon" data-remove-row>×</button></td>`;
    els.serviceRowsBody.appendChild(tr);
  });
  bindRowButtons();
}

function renderDeliverables() {
  els.deliverablesBody.innerHTML = "";
  (state.deliverables || []).forEach((row) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td><input data-field="label" type="text" value="${escapeAttr(row.label)}" /></td>
      <td><input data-field="quantity" type="text" value="${escapeAttr(row.quantity)}" /></td>
      <td><button type="button" class="btn-icon" data-remove-row>×</button></td>`;
    els.deliverablesBody.appendChild(tr);
  });
  bindRowButtons();
}

function bindRowButtons() {
  document.querySelectorAll("[data-remove-row]").forEach((btn) => {
    btn.onclick = () => {
      btn.closest("tr")?.remove();
      onFormChange();
    };
  });
}

function escapeAttr(s) {
  return String(s ?? "").replace(/"/g, "&quot;");
}

function updateTotalsSummary() {
  const el = document.getElementById("totals-summary");
  if (!el) return;
  const t = invoiceTotals(state);
  el.innerHTML = `
    <p>Subtotal: <strong>${formatINR(t.subtotal)}</strong></p>
    <p>Discount (${t.discountPercent}%): <strong>-${formatINR(t.discount)}</strong></p>
    <p>Shipping: <strong>${formatINR(t.shipping)}</strong></p>
    <p>Total: <strong>${formatINR(t.total)}</strong></p>
    <p>Amount received: <strong>${formatINR(t.received)}</strong></p>
    <p>Outstanding: <strong>${formatINR(t.outstanding)}</strong></p>`;
}

function refreshPreview() {
  els.preview.innerHTML = renderDocument(state);
}

function wireInvoiceExtras() {
  document.getElementById("btn-add-line")?.addEventListener("click", () => {
    state.lineItems.push({ description: "", quantity: 1, unitPrice: 0 });
    renderLineItems();
  });
  els.form.copyShipFromBill?.addEventListener("change", () => {
    toggleShipFields();
    readForm();
    refreshPreview();
  });
  els.form.paymentStatus?.addEventListener("change", () => {
    const f = els.form;
    readForm();
    const t = invoiceTotals(state);
    if (f.paymentStatus.value === "paid") {
      state.amountReceived = fromPaise(t.total);
      f.amountReceived.value = String(state.amountReceived);
    }
    onFormChange();
  });
}

function wireProposalExtras() {
  document.getElementById("btn-add-service")?.addEventListener("click", () => {
    state.serviceRows.push({ service: "", amountPerMonth: 0 });
    renderServiceRows();
  });
  document.getElementById("btn-add-deliverable")?.addEventListener("click", () => {
    state.deliverables.push({ label: "", quantity: "" });
    renderDeliverables();
  });
}

async function boot() {
  if (window.matchMedia("(max-width: 960px)").matches) {
    setView("editor");
  } else {
    els.workspace?.removeAttribute("data-view");
  }
  initState();
  if (kind === "invoice") wireInvoiceExtras();
  else wireProposalExtras();
  bindCommon();
  hydrateForm();
  setupQuills();
  await preloadExportCss();
  saveDraft(state);
  flashSave("Saved locally");
  document.getElementById("editor-kind-label").textContent =
    kind === "invoice" ? "Invoice editor" : "Proposal editor";
}

boot();
