const STORAGE_KEY = "rbds_invoice_seq";

/**
 * Suggested format RB-MMYYNN (local only — not globally unique).
 */
export function suggestInvoiceNumber(date = new Date()) {
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yy = String(date.getFullYear()).slice(-2);
  const monthKey = `${yy}${mm}`;
  let store = {};
  try {
    store = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");
  } catch {
    store = {};
  }
  const next = (store[monthKey] || 0) + 1;
  store[monthKey] = next;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
  const nn = String(next).padStart(2, "0");
  return `RB-${mm}${yy}${nn}`;
}

export function invoiceNumberWarning() {
  return "Invoice numbers are generated in this browser only. Other devices or profiles may use the same sequence. Review and edit the number before sending.";
}
