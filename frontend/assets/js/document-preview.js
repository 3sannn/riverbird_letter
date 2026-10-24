import { COMPANY } from "./company.js";
import { formatINR, formatINRPlain, invoiceTotals, sumLineItems, toPaise } from "./money.js";
import { sanitizeHtml } from "./sanitize.js";

function formatDisplayDate(iso) {
  if (!iso) return "";
  const [y, m, d] = iso.split("-");
  if (!y || !m || !d) return iso;
  return `${d}/${m}/${y}`;
}

function companyFooter(c) {
  const co = c || COMPANY;
  return `
    <footer class="doc-footer">
      <p class="doc-footer-name">${co.footerName}</p>
      <p>${co.address}</p>
      <p>Website: ${co.website} &nbsp; Mobile: ${co.mobile}</p>
      <p>CIN: ${co.cin} &nbsp; GST: ${co.gst}</p>
    </footer>`;
}

function headerLogo(co, variant = "invoice") {
  const src = co.logoHeaderPath || co.logoPath;
  const width = variant === "proposal" ? 400 : 340;
  return `<img class="doc-logo doc-logo--${variant}" src="${src}" alt="RiverBird" width="${width}" decoding="async" />`;
}

function addressBlock(person, business, address) {
  return `
    <p class="address-name">${escapeHtml((person || "").toUpperCase())}</p>
    <p class="address-lines">${escapeHtml(business || "")}<br>
    ${escapeHtml(address || "").replace(/\n/g, "<br>")}</p>`;
}

export function renderInvoiceDocument(data) {
  const co = data.company || COMPANY;
  const bill = data.billTo || {};
  const ship = data.copyShipFromBill ? bill : data.shipTo || {};
  const totals = invoiceTotals(data);
  const status = data.paymentStatus || "unpaid";
  let paidLabel = formatINR(totals.received);
  if (status === "partial") {
    paidLabel = `${formatINR(totals.received)} (Partial — ${formatINR(totals.outstanding)} due)`;
  } else if (status === "unpaid" && totals.received === 0) {
    paidLabel = formatINR(0);
  }

  const rows = (data.lineItems || [])
    .filter((r) => (r.description || "").trim())
    .map((r, i) => {
      const qty = Number(r.quantity) || 1;
      const linePaise = r.unitPrice != null ? toPaise(r.unitPrice) * qty : toPaise(r.amount || 0);
      return `<tr>
        <td>${i + 1}</td>
        <td>${escapeHtml(r.description)}</td>
        <td class="num">${formatINRPlain(linePaise)}</td>
      </tr>`;
    })
    .join("");

  const subtotalPaise = sumLineItems(data.lineItems || []);

  return `
    <article class="doc-sheet invoice-doc" data-document="invoice">
      <div class="invoice-accent-bar" aria-hidden="true"></div>
      <header class="invoice-hero">
        <div class="invoice-hero-left">
          ${headerLogo(co, "invoice")}
          <div class="invoice-company-text">
            <h1 class="invoice-company-name">${escapeHtml(co.name)}</h1>
            <p class="invoice-company-tagline">${escapeHtml(co.tagline)}</p>
          </div>
        </div>
        <div class="invoice-hero-right">
          <h2 class="invoice-doc-title">INVOICE</h2>
          <p class="invoice-meta-accent">${formatDisplayDate(data.invoiceDate)}</p>
          <p class="invoice-meta-accent"><strong>INVOICE NO.</strong> ${escapeHtml(data.invoiceNumber || "DRAFT")}</p>
        </div>
      </header>

      <div class="invoice-address-panel">
        <section class="invoice-address-col">
          <h3 class="address-label">Bill To</h3>
          ${addressBlock(bill.name, bill.business, bill.address)}
        </section>
        <section class="invoice-address-col invoice-address-col--divider">
          <h3 class="address-label">Ship To</h3>
          ${addressBlock(ship.name, ship.business, ship.address)}
        </section>
      </div>

      <div class="invoice-body">
        <table class="doc-table doc-table--invoice">
          <thead>
            <tr><th>S.no</th><th>Description</th><th class="num">Amount (₹)</th></tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="3" class="muted">No line items</td></tr>'}</tbody>
        </table>

        <div class="doc-summary-grid">
          <div class="doc-remarks">
            <strong class="remarks-label">Remarks / Payment Instructions:</strong>
            <div class="rich-block">${sanitizeHtml(data.remarksHtml || escapeHtml(data.remarks || ""))}</div>
          </div>
          <table class="doc-totals doc-totals--boxed">
            <tr><td>Subtotal</td><td class="num">${formatINR(subtotalPaise)}</td></tr>
            <tr><td>Discount</td><td class="num">${formatINRPlain(totals.discount, { decimals: true })}</td></tr>
            <tr><td>Amount received</td><td class="num">${formatINRPlain(totals.received)}</td></tr>
            <tr><td>Shipping / handling</td><td class="num">${formatINRPlain(totals.shipping, { decimals: true })}</td></tr>
            <tr class="grand"><td>Total</td><td class="num">${formatINR(totals.total)}</td></tr>
            <tr class="paid-row"><td>Paid</td><td class="num">${paidLabel}</td></tr>
          </table>
        </div>
        ${companyFooter(co)}
      </div>
    </article>`;
}

export function renderProposalDocument(data) {
  const co = data.company || COMPANY;
  const client = data.clientName || "Client";
  const title = data.title || `Project Proposal – ${client}`;
  const serviceRows = (data.serviceRows || [])
    .filter((r) => (r.service || "").trim())
    .map(
      (r, i) => `<tr>
      <td>${i + 1}</td>
      <td>${escapeHtml(r.service)}</td>
      <td class="num">₹${formatINRPlain(toPaise(r.amountPerMonth || 0))}</td>
    </tr>`
    )
    .join("");
  const serviceTotal = (data.serviceRows || []).reduce(
    (a, r) => a + toPaise(r.amountPerMonth || 0),
    0
  );

  const deliv = (data.deliverables || [])
    .map(
      (d, i) =>
        `<tr><td>${i + 1}</td><td>${escapeHtml(d.label)}</td><td>${escapeHtml(d.quantity)}</td></tr>`
    )
    .join("");

  return `
    <article class="doc-sheet proposal-doc" data-document="proposal">
      <header class="proposal-hero">
        <div class="proposal-hero-brand">
          ${headerLogo(co, "proposal")}
          <span class="proposal-brand-rule" aria-hidden="true"></span>
        </div>
        <h1 class="proposal-main-title">${escapeHtml(title)}</h1>
        <p class="proposal-meta-line">
          <span><span class="meta-label">Prepared For:</span> <span class="meta-value">${escapeHtml(client)}</span></span>
          <span class="meta-sep">·</span>
          <span><span class="meta-label">Prepared By:</span> <span class="meta-value">${escapeHtml(data.preparedBy || "RiverBird.in")}</span></span>
          <span class="meta-sep">·</span>
          <span><span class="meta-label">Date:</span> <span class="meta-value">${formatDisplayDate(data.proposalDate)}</span></span>
        </p>
      </header>

      <div class="proposal-body">
        <section class="doc-section">
          <h2 class="section-heading">1. Introduction</h2>
          <div class="rich-block">${sanitizeHtml(data.introductionHtml)}</div>
        </section>

        <section class="doc-section">
          <h2 class="section-heading">2. Scope of Service</h2>
          <div class="rich-block">${sanitizeHtml(data.scopeHtml)}</div>
        </section>

        <section class="doc-section">
          <h2 class="section-heading">3. Service Cost</h2>
          <table class="doc-table doc-table--proposal">
            <thead><tr><th>S.No</th><th>Service</th><th class="num">Amount / Per Month</th></tr></thead>
            <tbody>${serviceRows}</tbody>
            <tfoot><tr><td colspan="2"><strong>Total</strong></td><td class="num"><strong>${formatINR(serviceTotal)}</strong></td></tr></tfoot>
          </table>
        </section>

        <section class="doc-section">
          <h2 class="section-heading">4. Deliverables</h2>
          <table class="doc-table doc-table--proposal">
            <thead><tr><th>S.no</th><th>Reels / Posters</th><th>Quantity</th></tr></thead>
            <tbody>${deliv}</tbody>
          </table>
        </section>

        <section class="doc-section">
          <h2 class="section-heading">5. Terms &amp; Conditions</h2>
          <div class="rich-block">${sanitizeHtml(data.termsHtml)}</div>
        </section>

        <section class="doc-section">
          <h2 class="section-heading">6. Conclusion</h2>
          <div class="rich-block">${sanitizeHtml(data.conclusionHtml)}</div>
          <div class="rich-block approval-block">${sanitizeHtml(data.approvalHtml)}</div>
        </section>

        ${companyFooter(co)}
      </div>
    </article>`;
}

export function renderDocument(data) {
  if (data.kind === "proposal") return renderProposalDocument(data);
  return renderInvoiceDocument(data);
}

function escapeHtml(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}
