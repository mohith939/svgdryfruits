const categoryCards = [...document.querySelectorAll(".catalog-card")];
const filterButtons = [...document.querySelectorAll(".filter-button")];
const catalogSearch = document.querySelector("#catalog-search");
const categoryCount = document.querySelector(".catalog-count");
const emptyResults = document.querySelector(".empty-results");

let activeFilter = "all";

function updateCatalog() {
  const query = catalogSearch.value.trim().toLocaleLowerCase();
  let visibleCount = 0;

  for (const card of categoryCards) {
    const matchesFilter = activeFilter === "all" || card.dataset.category === activeFilter;
    const matchesSearch = !query || card.dataset.search.includes(query) || card.querySelector("h3").textContent.toLocaleLowerCase().includes(query);
    const visible = matchesFilter && matchesSearch;
    card.hidden = !visible;
    if (visible) visibleCount += 1;
  }

  categoryCount.textContent = `${visibleCount} ${visibleCount === 1 ? "category" : "categories"}`;
  emptyResults.hidden = visibleCount > 0;
}

for (const button of filterButtons) {
  button.addEventListener("click", () => {
    activeFilter = button.dataset.filter;
    for (const item of filterButtons) {
      const selected = item === button;
      item.classList.toggle("is-active", selected);
      item.setAttribute("aria-pressed", String(selected));
    }
    updateCatalog();
  });
}

const requestedCategory = new URLSearchParams(window.location.search).get("category");
const requestedFilter = filterButtons.find((button) => button.dataset.filter === requestedCategory);
if (requestedFilter) requestedFilter.click();

catalogSearch.addEventListener("input", updateCatalog);

document.querySelector(".search-panel").addEventListener("submit", (event) => {
  event.preventDefault();
  catalogSearch.value = document.querySelector("#site-search").value.trim();
  updateCatalog();
  document.querySelector("#collection").scrollIntoView({ behavior: "smooth", block: "start" });
});
