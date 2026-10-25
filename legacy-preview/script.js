const menuButton = document.querySelector(".menu-toggle");
const mobileNavigation = document.querySelector(".mobile-nav");
const searchButton = document.querySelector(".search-toggle");
const searchPanel = document.querySelector(".search-panel");
const searchForm = document.querySelector(".search-panel");
const searchInput = document.querySelector("#site-search");
const statusMessage = document.querySelector("#status-message");

document.querySelectorAll("#year").forEach((year) => {
  year.textContent = new Date().getFullYear();
});

menuButton.addEventListener("click", () => {
  const isExpanded = menuButton.getAttribute("aria-expanded") === "true";
  searchPanel.hidden = true;
  searchButton.setAttribute("aria-label", "Show search");
  menuButton.setAttribute("aria-expanded", String(!isExpanded));
  menuButton.setAttribute("aria-label", isExpanded ? "Open navigation menu" : "Close navigation menu");
  mobileNavigation.hidden = isExpanded;
});

mobileNavigation.addEventListener("click", (event) => {
  if (event.target.closest("a")) {
    mobileNavigation.hidden = true;
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "Open navigation menu");
  }
});

searchButton.addEventListener("click", () => {
  const opening = searchPanel.hidden;
  mobileNavigation.hidden = true;
  menuButton.setAttribute("aria-expanded", "false");
  menuButton.setAttribute("aria-label", "Open navigation menu");
  searchPanel.hidden = !opening;
  searchButton.setAttribute("aria-label", opening ? "Hide search" : "Show search");
  if (opening) searchInput.focus();
});

if (!document.querySelector("#catalog-search")) {
  searchForm.addEventListener("submit", (event) => {
    event.preventDefault();
    statusMessage.textContent = searchInput.value.trim()
      ? "Product search will be available when the catalog is connected."
      : "Enter a product or category to search.";
  });
}

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    if (!searchPanel.hidden) {
      searchPanel.hidden = true;
      searchButton.setAttribute("aria-label", "Show search");
      searchButton.focus();
    }
    if (!mobileNavigation.hidden) {
      mobileNavigation.hidden = true;
      menuButton.setAttribute("aria-expanded", "false");
      menuButton.setAttribute("aria-label", "Open navigation menu");
      menuButton.focus();
    }
  }
});

if ("IntersectionObserver" in window && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
  const motionTargets = document.querySelectorAll(
    ".pantry-section, .gifting-section, .story-section, .catalog-card, .shop-note"
  );
  const observer = new IntersectionObserver((entries, activeObserver) => {
    for (const entry of entries) {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        activeObserver.unobserve(entry.target);
      }
    }
  }, { threshold: 0.12 });

  for (const target of motionTargets) {
    target.classList.add("motion-reveal");
    observer.observe(target);
  }
  document.documentElement.classList.add("motion-ready");
}
