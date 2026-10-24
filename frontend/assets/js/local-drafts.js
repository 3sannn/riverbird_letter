const INDEX_KEY = "rbds_drafts_index";
const DRAFT_PREFIX = "rbds_draft_";
const SCHEMA_VERSION = 1;

function readIndex() {
  try {
    const raw = localStorage.getItem(INDEX_KEY);
    const list = raw ? JSON.parse(raw) : [];
    return Array.isArray(list) ? list : [];
  } catch {
    return [];
  }
}

function writeIndex(list) {
  localStorage.setItem(INDEX_KEY, JSON.stringify(list));
}

export function listDrafts() {
  return readIndex().sort((a, b) => (b.updatedAt || "").localeCompare(a.updatedAt || ""));
}

export function getDraft(id) {
  try {
    const raw = localStorage.getItem(DRAFT_PREFIX + id);
    if (!raw) return null;
    const data = JSON.parse(raw);
    if (!validateDraft(data)) return null;
    return data;
  } catch {
    return null;
  }
}

export function saveDraft(draft) {
  if (!draft.id) draft.id = crypto.randomUUID();
  draft.updatedAt = new Date().toISOString();
  draft.version = SCHEMA_VERSION;
  if (!validateDraft(draft)) {
    throw new Error("Invalid draft data");
  }
  localStorage.setItem(DRAFT_PREFIX + draft.id, JSON.stringify(draft));
  const index = readIndex().filter((d) => d.id !== draft.id);
  index.push({
    id: draft.id,
    title: draft.title || "Untitled",
    kind: draft.kind,
    templateId: draft.templateId,
    updatedAt: draft.updatedAt,
  });
  writeIndex(index);
  return draft;
}

export function deleteDraft(id) {
  localStorage.removeItem(DRAFT_PREFIX + id);
  writeIndex(readIndex().filter((d) => d.id !== id));
}

export function renameDraft(id, title) {
  const draft = getDraft(id);
  if (!draft) return null;
  draft.title = title;
  return saveDraft(draft);
}

export function validateDraft(data) {
  if (!data || typeof data !== "object") return false;
  if (data.version !== SCHEMA_VERSION) return false;
  if (data.kind !== "invoice" && data.kind !== "proposal") return false;
  if (typeof data.id !== "string") return false;
  return true;
}

export function exportDraftJson(draft) {
  const blob = new Blob([JSON.stringify(draft, null, 2)], { type: "application/json" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = `${sanitizeFilename(draft.title || "draft")}.json`;
  a.click();
  URL.revokeObjectURL(a.href);
}

export function importDraftJson(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => {
      try {
        const data = JSON.parse(String(reader.result));
        if (!data || typeof data !== "object") {
          reject(new Error("Invalid draft file"));
          return;
        }
        if (!data.version) data.version = SCHEMA_VERSION;
        data.id = crypto.randomUUID();
        if (!validateDraft(data)) {
          reject(new Error("Invalid draft file"));
          return;
        }
        resolve(saveDraft(data));
      } catch (e) {
        reject(e);
      }
    };
    reader.onerror = () => reject(reader.error);
    reader.readAsText(file);
  });
}

export function sanitizeFilename(name) {
  return name.replace(/[^\w\-]+/g, "-").replace(/-+/g, "-").slice(0, 80) || "document";
}

export const DRAFT_NOTICE =
  "Drafts are stored only in this browser profile on this device. Export JSON backups for safekeeping. Render's server does not store your documents.";
