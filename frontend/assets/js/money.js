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
  if (decimals || paise % 100 !== 0) {
    return `₹${value.toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }
  return `₹${Math.round(value).toLocaleString("en-IN")}`;
}

export function formatINRPlain(paise, { decimals = false } = {}) {
  const value = fromPaise(paise);
  if (decimals || paise % 100 !== 0) {
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

export function resolveDiscountPercent(data) {
  if (data.discountPercent != null && data.discountPercent !== "") {
    const p = Number(data.discountPercent);
    if (Number.isFinite(p)) return Math.min(100, Math.max(0, p));
  }
  return 0;
}

export function invoiceTotals(data) {
  const subtotal = sumLineItems(data.lineItems || []);
  const discountPercent = resolveDiscountPercent(data);
  const discount = Math.round((subtotal * discountPercent) / 100);
  const shipping = toPaise(data.shipping || 0);
  const total = Math.max(0, subtotal - discount + shipping);
  const received = toPaise(data.amountReceived || 0);
  const outstanding = Math.max(0, total - received);
  return { subtotal, discount, discountPercent, shipping, total, received, outstanding };
}

/** Amount shown in PAID / balance row on the invoice */
export function invoicePaidDisplayAmount(totals, paymentStatus) {
  const status = paymentStatus || "unpaid";
  if (status === "paid") {
    return totals.received > 0 ? totals.received : totals.total;
  }
  if (status === "partial") {
    return totals.received > 0 ? totals.received : totals.total;
  }
  return totals.total;
}
