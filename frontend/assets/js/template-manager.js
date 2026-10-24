import { INVOICE_TEMPLATES, PROPOSAL_TEMPLATES } from "./template-seeds.js";

function card(template, kind) {
  const href = `/editor?kind=${kind}&template=${template.id}`;
  return `
    <article class="template-card">
      <h3>${template.label}</h3>
      <p>${template.description}</p>
      <a class="btn btn-primary" href="${href}">Use template</a>
    </article>`;
}

export function initTemplatesPage() {
  const params = new URLSearchParams(location.search);
  const category = params.get("category") || "all";

  const invoiceEl = document.getElementById("invoice-templates");
  const proposalEl = document.getElementById("proposal-templates");

  if (invoiceEl) {
    invoiceEl.innerHTML = [
      INVOICE_TEMPLATES.blank,
      INVOICE_TEMPLATES.exampleWebsite,
      INVOICE_TEMPLATES.exampleGmb,
    ]
      .map((t) => card(t, "invoice"))
      .join("");
  }
  if (proposalEl) {
    proposalEl.innerHTML = [PROPOSAL_TEMPLATES.blank, PROPOSAL_TEMPLATES.manoBakery]
      .map((t) => card(t, "proposal"))
      .join("");
  }

  document.querySelectorAll("[data-filter]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const f = btn.dataset.filter;
      location.search = f === "all" ? "" : `category=${f}`;
    });
  });

  const showInvoice = category === "all" || category === "invoice";
  const showProposal = category === "all" || category === "proposal";
  document.getElementById("section-invoice")?.classList.toggle("hidden", !showInvoice);
  document.getElementById("section-proposal")?.classList.toggle("hidden", !showProposal);

  document.querySelectorAll("[data-filter]").forEach((btn) => {
    btn.classList.toggle("is-active", btn.dataset.filter === category || (category === "all" && btn.dataset.filter === "all"));
  });
}
