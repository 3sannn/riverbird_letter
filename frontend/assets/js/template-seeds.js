import { COMPANY } from "./company.js";

const dermiizClient = {
  name: "DR. SHENBAGARAJ",
  business: "DermiiZ Skin & Hair Clinic",
  address:
    "C83, 10th Cross St, West Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018.",
};

export const INVOICE_TEMPLATES = {
  blank: {
    id: "invoice-blank",
    kind: "invoice",
    label: "New invoice",
    description: "Start from the standard RiverBird invoice layout.",
    seed: () => ({
      version: 1,
      kind: "invoice",
      templateId: "invoice-blank",
      title: "New Invoice",
      invoiceDate: new Date().toISOString().slice(0, 10),
      invoiceNumber: "",
      billTo: { name: "", business: "", address: "" },
      shipTo: { name: "", business: "", address: "" },
      copyShipFromBill: false,
      lineItems: [{ description: "", quantity: 1, unitPrice: 0 }],
      remarks: "",
      discountPercent: 0,
      shipping: 0,
      amountReceived: 0,
      paymentStatus: "unpaid",
      company: COMPANY,
    }),
  },
  exampleWebsite: {
    id: "invoice-example-website",
    kind: "invoice",
    label: "Reference — Website Development (RB2026010)",
    description: "Historical example from Dermiiz advance invoice.",
    seed: () => ({
      version: 1,
      kind: "invoice",
      templateId: "invoice-example-website",
      title: "Website Development — Advance",
      invoiceDate: "2026-08-27",
      invoiceNumber: "RB2026010",
      billTo: { ...dermiizClient },
      shipTo: { ...dermiizClient },
      copyShipFromBill: true,
      lineItems: [{ description: "Website Development", quantity: 1, unitPrice: 18000 }],
      remarks: "Paid Advance - Full Project",
      discountPercent: 0,
      shipping: 0,
      amountReceived: 18000,
      paymentStatus: "paid",
      company: COMPANY,
    }),
  },
  exampleGmb: {
    id: "invoice-example-gmb",
    kind: "invoice",
    label: "Reference — Google My Business SEO (RB-092602)",
    description: "Historical example from September 2026 invoice.",
    seed: () => ({
      version: 1,
      kind: "invoice",
      templateId: "invoice-example-gmb",
      title: "Google My Business SEO — Final",
      invoiceDate: "2026-09-30",
      invoiceNumber: "RB-092602",
      billTo: { ...dermiizClient },
      shipTo: { ...dermiizClient },
      copyShipFromBill: true,
      lineItems: [{ description: "Google My Business SEO", quantity: 1, unitPrice: 7500 }],
      remarks: "Final Payment - Full Project",
      discountPercent: 0,
      shipping: 0,
      amountReceived: 7500,
      paymentStatus: "paid",
      company: COMPANY,
    }),
  },
};

const scopeListHtml = `
<p><strong>A. Social Media Marketing</strong></p>
<ul>
<li><strong>Content Calendar:</strong> Content Planning, Content Calendar and posting timeline.</li>
<li><strong>Competitor Analyse:</strong> Observing competitors' content and performance.</li>
<li><strong>Content Ideation:</strong> Creating platform-specific content ideas based on trends and audience insights.</li>
<li><strong>Account Management:</strong> Managing and maintaining the social media account.</li>
<li><strong>Graphic Design:</strong> includes with Creative &amp; Promotional Posters.</li>
<li><strong>Keyword Optimization:</strong> Optimizing content with relevant keywords to improve reach and visibility.</li>
<li><strong>Brand Consistency:</strong> Maintaining a uniform brand voice and visual style.</li>
<li><strong>Optimization:</strong> Improving content based on performance data and trends.</li>
<li><strong>Posting:</strong> Posting on time &amp; Scheduling</li>
<li><strong>YouTube Shorts:</strong> Reels published on Instagram will also be posted as YouTube Shorts to increase content reach across platforms.</li>
<li><strong>Complete Management:</strong> End-to-end social media handling including planning, execution, engagement, monitoring, and reporting.</li>
</ul>`;

const defaultTermsHtml = `
<ul>
<li>The client must provide necessary access to social media accounts, brand assets, and any other required resources before the start of the service.</li>
<li>Delays in access provision may impact the delivery timeline, and RiverBird.in will not be responsible for such delays.</li>
<li>RiverBird.in assures that any credentials, materials, or information shared by the client will remain strictly confidential and will not be disclosed or misused under any circumstances.</li>
<li>The client must inform RiverBird.in at least three (3) days in advance before scheduling any video shoot.</li>
<li>The client must pay 50% of the project value before starting with production.</li>
<li>The remaining 50% is payable on end of each month.</li>
<li>Any additional work beyond the agreed scope will be charged separately.</li>
<li>Either party may terminate the contract by providing one (1) month written notice.</li>
<li>This contract is valid for 2 months.</li>
</ul>`;

export const PROPOSAL_TEMPLATES = {
  blank: {
    id: "proposal-blank",
    kind: "proposal",
    label: "New proposal",
    description: "Structured proposal with editable sections.",
    seed: () => ({
      version: 1,
      kind: "proposal",
      templateId: "proposal-blank",
      title: "Project Proposal",
      clientName: "",
      preparedBy: "RiverBird.in",
      proposalDate: new Date().toISOString().slice(0, 10),
      introductionHtml:
        "<p>RiverBird.in is pleased to offer services to enhance your online presence. Our goal is to create high-quality, engaging content that showcases your expertise.</p>",
      scopeHtml: "<p>Describe scope of service here.</p>",
      serviceRows: [{ service: "", amountPerMonth: 0 }],
      deliverables: [
        { label: "Reels", quantity: "8" },
        { label: "Posters", quantity: "8" },
        { label: "YouTube Shorts", quantity: "Same reels posted as Shorts" },
      ],
      termsHtml: defaultTermsHtml,
      conclusionHtml:
        "<p>We look forward to collaborating with you for a results-driven approach to your business.</p>",
      approvalHtml:
        "<p><strong>Approval &amp; Acceptance</strong></p><p>I, ________________, agree to authorize RiverBird.in to proceed with the project as outlined above.</p>",
      company: COMPANY,
    }),
  },
  manoBakery: {
    id: "proposal-mano-bakery",
    kind: "proposal",
    label: "Reference — Mano Bakery SMM",
    description: "Original social media marketing proposal content.",
    seed: () => ({
      version: 1,
      kind: "proposal",
      templateId: "proposal-mano-bakery",
      title: "Project Proposal – Mano Bakery",
      clientName: "Mano Bakery",
      preparedBy: "RiverBird.in",
      proposalDate: "2026-10-07",
      introductionHtml:
        "<p>RiverBird.in is pleased to offer Social Media Marketing services to enhance Mano Bakery online presence. Our goal is to create high-quality, engaging content that showcases your expertise in the Bakery &amp; Food Industry.</p>",
      scopeHtml: scopeListHtml,
      serviceRows: [{ service: "Social Media Marketing", amountPerMonth: 20000 }],
      deliverables: [
        { label: "Reels", quantity: "8" },
        { label: "Posters", quantity: "8" },
        { label: "YouTube Shorts", quantity: "Same 8 Reels will be posted as Shorts" },
      ],
      termsHtml: defaultTermsHtml,
      conclusionHtml:
        "<p>Our Social Media Marketing will help Mano Bakery Online presence improve visibility, and generate relevant enquiries. We look forward to collaborating with you for a results-driven approach to your business.</p>",
      approvalHtml:
        "<p><strong>Approval &amp; Acceptance</strong></p><p>I, ________________, agree to authorize RiverBird.in to proceed with the project as outlined above.</p>",
      company: COMPANY,
    }),
  },
};

export function getTemplateSeed(kind, templateId) {
  if (kind === "invoice") {
    const map = INVOICE_TEMPLATES;
    if (templateId === "invoice-example-website") return map.exampleWebsite.seed();
    if (templateId === "invoice-example-gmb") return map.exampleGmb.seed();
    return map.blank.seed();
  }
  if (kind === "proposal") {
    if (templateId === "proposal-mano-bakery") return PROPOSAL_TEMPLATES.manoBakery.seed();
    return PROPOSAL_TEMPLATES.blank.seed();
  }
  return null;
}
