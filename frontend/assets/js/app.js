/** Shared UI helpers */
document.querySelectorAll("[data-nav-toggle]").forEach((btn) => {
  btn.addEventListener("click", () => {
    document.body.classList.toggle("nav-open");
  });
});
