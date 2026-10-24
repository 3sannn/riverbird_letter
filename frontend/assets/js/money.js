/** Integer paise math to avoid floating-point drift */
export function toPaise(amount) {
  const n = Number(amount);
  if (!Number.isFinite(n)) return 0;
  return Math.round(n * 100);
}

export function fromPaise(paise) {
  return paise / 100;
}

export function formatINR(paise, { decimals = false } = {}) {
  const value = fromPaise(paise);
  if (decimals) {
    return `₹${value.toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }
  return `₹${Math.round(value).toLocaleString("en-IN")}`;
}

export function formatINRPlain(paise, { decimals = false } = {}) {
  const value = fromPaise(paise);
  if (decimals) {
    return value.toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  return Math.round(value).toLocaleString("en-IN");
}

export function sumLineItems(items) {
  return items.reduce((acc, row) => {
    const qty = Number(row.quantity) || 1;
    const unit = toPaise(row.unitPrice ?? row.amount ?? 0);
    if (row.amount != null && row.unitPrice == null && row.quantity == null) {
      return acc + toPaise(row.amount);
    }
    return acc + unit * qty;
  }, 0);
}

export function invoiceTotals(data) {
  const subtotal = sumLineItems(data.lineItems || []);
  const discount = toPaise(data.discount || 0);
  const shipping = toPaise(data.shipping || 0);
  const total = Math.max(0, subtotal - discount + shipping);
  const received = toPaise(data.amountReceived || 0);
  const outstanding = Math.max(0, total - received);
  return { subtotal, discount, shipping, total, received, outstanding };
}
