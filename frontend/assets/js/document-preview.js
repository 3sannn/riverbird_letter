import { COMPANY } from "./company.js";
import {
  formatINR,
  formatINRPlain,
  invoicePaidDisplayAmount,
  invoiceTotals,
  sumLineItems,
  toPaise,
} from "./money.js";
import { sanitizeHtml } from "./sanitize.js";

function formatDisplayDate(iso) {
  if (!iso) return "";
  const [y, m, d] = iso.split("-");
  if (!y || !m || !d) return iso;
  return `${d}/${m}/${y}`;
}

function companyFooter(c, variant = "invoice") {
  const co = c || COMPANY;
  return `
    <div class="doc-footer-rule" aria-hidden="true"></div>
    <footer class="doc-footer doc-footer--${variant}">
      <p class="doc-footer-name">${co.footerName}</p>
      <p>${co.address}</p>
      <p>Website: ${co.website} &nbsp; Mobile: ${co.mobile}</p>
      <p>CIN: ${co.cin} &nbsp; GST: ${co.gst}</p>
    </footer>`;
}

function headerLogo(co, variant = "invoice") {
  const src = co.logoHeaderPath || co.logoPath || co.logoWordmarkPath;
  const width = variant === "proposal" ? 300 : 280;
  return `<img class="doc-logo doc-logo--${variant}" src="${src}" alt="RiverBird" width="${width}" decoding="async" />`;
}

function addressBlock(person, business, address) {
  return `
    <p class="party-name">${escapeHtml((person || "").toUpperCase())}</p>
    <p class="party-lines">${escapeHtml(business || "")}<br>
    ${escapeHtml(address || "").replace(/\n/g, "<br>")}</p>`;
}

function proposalSectionTitle(num, label) {
  return `<h2 class="prop-section-title"><span class="prop-section-num">${num}.</span> ${escapeHtml(label)}</h2>`;
}

export function renderInvoiceDocument(data) {
  const co = data.company || COMPANY;
  const bill = data.billTo || {};
  const ship = data.copyShipFromBill ? bill : data.shipTo || {};
  const totals = invoiceTotals(data);
  const status = data.paymentStatus || "unpaid";

  const lineItems = (data.lineItems || []).filter((r) => (r.description || "").trim());
  const rows = lineItems
    .map((r, i) => {
      const qty = Number(r.quantity) || 1;
      const linePaise = r.unitPrice != null ? toPaise(r.unitPrice) * qty : toPaise(r.amount || 0);
      return `<tr>
        <td class="col-sno">${i + 1}</td>
        <td>${escapeHtml(r.description)}</td>
        <td class="num">${formatINRPlain(linePaise)}</td>
      </tr>`;
    })
    .join("");

  const emptyRows =
    lineItems.length < 3
      ? Array.from({ length: 3 - lineItems.length })
          .map(() => `<tr class="inv-empty-row"><td class="col-sno">&nbsp;</td><td>&nbsp;</td><td class="num">&nbsp;</td></tr>`)
          .join("")
      : "";

  const subtotalPaise = sumLineItems(data.lineItems || []);
  const paidPaise = invoicePaidDisplayAmount(totals, status);
  const paidDisplay = formatINR(paidPaise);
  const paidStatusLabel =
    status === "partial" ? "PARTIALLY PAID" : status === "unpaid" ? "UNPAID" : "PAID";
  const discountLabel =
    totals.discountPercent > 0
      ? `DISCOUNT (${totals.discountPercent}%)`
      : "DISCOUNT";

  return `
    <article class="doc-sheet invoice-doc" data-document="invoice">
      <div class="inv-top-rule" aria-hidden="true"></div>

      <div class="inv-main">
      <header class="inv-header">
        <div class="inv-header-left">
          <h1 class="inv-company-name">${escapeHtml(co.name)}</h1>
          <p class="inv-company-tagline">${escapeHtml(co.tagline)}</p>
          ${headerLogo(co, "invoice")}
        </div>
        <div class="inv-header-right">
          <h2 class="inv-title">INVOICE</h2>
          <p class="inv-date">${formatDisplayDate(data.invoiceDate)}</p>
          <p class="inv-number"><strong>INVOICE NO.</strong> <span class="inv-number-value">${escapeHtml(data.invoiceNumber || "DRAFT")}</span></p>
        </div>
      </header>

      <div class="inv-party-grid">
        <section class="inv-party">
          <div class="inv-party-banner">Bill To</div>
          <div class="inv-party-content">${addressBlock(bill.name, bill.business, bill.address)}</div>
        </section>
        <section class="inv-party">
          <div class="inv-party-banner">Ship To</div>
          <div class="inv-party-content">${addressBlock(ship.name, ship.business, ship.address)}</div>
        </section>
      </div>

      <table class="inv-items-table">
        <thead>
          <tr>
            <th class="col-sno">S.no</th>
            <th>Description</th>
            <th class="num">Amount (₹)</th>
          </tr>
        </thead>
        <tbody>
          ${rows || '<tr><td colspan="3" class="muted">No line items</td></tr>'}
          ${emptyRows}
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" class="inv-total-label"><strong>Total</strong></td>
            <td class="num"><strong>${formatINRPlain(subtotalPaise)}</strong></td>
          </tr>
        </tfoot>
      </table>

      <div class="inv-settlement">
        <div class="inv-remarks">
          <span class="inv-remarks-label">Remarks / Payment Instructions:</span>
          <span class="inv-remarks-text rich-block">${sanitizeHtml(data.remarksHtml || escapeHtml(data.remarks || ""))}</span>
        </div>
        <div class="inv-ledger">
          <div class="inv-ledger-row">
            <span class="inv-ledger-key">${discountLabel}</span>
            <span class="inv-ledger-dots" aria-hidden="true"></span>
            <span class="inv-ledger-val">${formatINRPlain(totals.discount, { decimals: true })}</span>
          </div>
          <div class="inv-ledger-row">
            <span class="inv-ledger-key">AMOUNT RECEIVED</span>
            <span class="inv-ledger-dots" aria-hidden="true"></span>
            <span class="inv-ledger-val">${formatINRPlain(totals.received, { decimals: true })}</span>
          </div>
          <div class="inv-ledger-row">
            <span class="inv-ledger-key">SHIPPING/HANDLING</span>
            <span class="inv-ledger-dots" aria-hidden="true"></span>
            <span class="inv-ledger-val">${formatINRPlain(totals.shipping, { decimals: true })}</span>
          </div>
          <div class="inv-ledger-row inv-ledger-grand">
            <span class="inv-ledger-key"><strong>TOTAL</strong></span>
            <span class="inv-ledger-dots" aria-hidden="true"></span>
            <span class="inv-ledger-val"><strong>${formatINRPlain(totals.total, { decimals: true })}</strong></span>
          </div>
          <div class="inv-ledger-row inv-ledger-paid">
            <span class="inv-ledger-key inv-paid-label"><strong>${paidStatusLabel}</strong></span>
            <span class="inv-ledger-dots" aria-hidden="true"></span>
            <span class="inv-paid-box">${paidDisplay}</span>
          </div>
        </div>
      </div>
      </div>

      <div class="inv-footer-block">
        ${companyFooter(co, "invoice")}
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

  const deliv = (data.deliverables || []);
  let delivTotal = 0;
  const delivRows = deliv
    .map((d, i) => {
      const q = parseInt(String(d.quantity).replace(/\D/g, ""), 10);
      if (!Number.isNaN(q)) delivTotal += q;
      return `<tr><td>${i + 1}</td><td>${escapeHtml(d.label)}</td><td>${escapeHtml(d.quantity)}</td></tr>`;
    })
    .join("");

  return `
    <article class="doc-sheet proposal-doc" data-document="proposal">
      <header class="prop-header">
        <div class="prop-logo-row">${headerLogo(co, "proposal")}</div>
        <h1 class="prop-title">${escapeHtml(title)}</h1>
        <p class="prop-meta">
          <span><span class="prop-meta-label">Prepared For:</span> ${escapeHtml(client)}</span>
          <span><span class="prop-meta-label">Prepared By:</span> ${escapeHtml(data.preparedBy || "RiverBird.in")}</span>
          <span><span class="prop-meta-label">Date:</span> ${formatDisplayDate(data.proposalDate)}</span>
        </p>
        <hr class="prop-header-rule" />
      </header>

      <div class="prop-body">
        <section class="prop-section">
          ${proposalSectionTitle(1, "Introduction")}
          <div class="rich-block prop-content">${sanitizeHtml(data.introductionHtml)}</div>
        </section>

        <section class="prop-section">
          ${proposalSectionTitle(2, "Scope of Service")}
          <div class="rich-block prop-content prop-scope">${sanitizeHtml(data.scopeHtml)}</div>
        </section>

        <section class="prop-section">
          ${proposalSectionTitle(3, "Service Cost")}
          <table class="prop-table">
            <thead><tr><th>S.No</th><th>Service</th><th class="num">Amount / Per Month</th></tr></thead>
            <tbody>${serviceRows}</tbody>
            <tfoot><tr><td colspan="2"><strong>Total</strong></td><td class="num"><strong>${formatINR(serviceTotal)}</strong></td></tr></tfoot>
          </table>
        </section>

        <section class="prop-section prop-section--page-break">
          ${proposalSectionTitle(4, "Deliverables")}
          <table class="prop-table">
            <thead><tr><th>S.no</th><th>/video/Posters</th><th>Quantity</th></tr></thead>
            <tbody>${delivRows}</tbody>
            <tfoot><tr><td colspan="2"><strong>Total</strong></td><td><strong>${delivTotal || ""}</strong></td></tr></tfoot>
          </table>
        </section>

        <section class="prop-section">
          ${proposalSectionTitle(5, "Terms & Conditions")}
          <div class="rich-block prop-content">${sanitizeHtml(data.termsHtml)}</div>
        </section>

        <section class="prop-section">
          ${proposalSectionTitle(6, "Conclusion")}
          <div class="rich-block prop-content">${sanitizeHtml(data.conclusionHtml)}</div>
          <div class="rich-block prop-approval">${sanitizeHtml(data.approvalHtml)}</div>
        </section>

        ${companyFooter(co, "proposal")}
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
