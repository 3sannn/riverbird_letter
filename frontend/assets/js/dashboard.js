import { listDrafts, deleteDraft, DRAFT_NOTICE, importDraftJson } from "./local-drafts.js";

function formatWhen(iso) {
  if (!iso) return "";
  try {
    return new Date(iso).toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" });
  } catch {
    return iso;
  }
}

function renderDrafts() {
  const list = document.getElementById("draft-list");
  const empty = document.getElementById("drafts-empty");
  const drafts = listDrafts();
  list.innerHTML = "";
  if (!drafts.length) {
    empty?.classList.remove("hidden");
    return;
  }
  empty?.classList.add("hidden");
  drafts.forEach((meta) => {
    const li = document.createElement("li");
    li.className = "draft-item";
    li.innerHTML = `
      <div>
        <p class="draft-title">${escape(meta.title)}</p>
        <p class="draft-meta">${meta.kind === "invoice" ? "Invoice" : "Proposal"} · ${formatWhen(meta.updatedAt)}</p>
      </div>
      <div class="draft-actions">
        <a class="btn btn-secondary btn-sm" href="/editor?draft=${meta.id}">Resume</a>
        <button type="button" class="btn btn-ghost btn-sm" data-delete="${meta.id}">Delete</button>
      </div>`;
    list.appendChild(li);
  });
  list.querySelectorAll("[data-delete]").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (!confirm("Delete this draft?")) return;
      deleteDraft(btn.dataset.delete);
      renderDrafts();
    });
  });
}

function escape(s) {
  const d = document.createElement("div");
  d.textContent = s;
  return d.innerHTML;
}

export function initDashboard() {
  const notice = document.getElementById("draft-notice");
  if (notice) notice.textContent = DRAFT_NOTICE;
  renderDrafts();

  document.getElementById("import-backup-btn")?.addEventListener("click", () => {
    document.getElementById("dashboard-import-input")?.click();
  });
  document.getElementById("dashboard-import-input")?.addEventListener("change", async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      const draft = await importDraftJson(file);
      location.href = `/editor?draft=${draft.id}`;
    } catch (err) {
      alert(err.message || "Import failed");
    }
    e.target.value = "";
  });
}
