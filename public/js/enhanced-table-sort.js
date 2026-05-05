// Enhanced Table Sorting with Visual Indicators
window.initTableSort = function() {
    document.querySelectorAll(".sortable-table").forEach((table) => {
        // Prevent double initialization
        if (table.dataset.sortInitialized) return;
        table.dataset.sortInitialized = "true";

        const headers = table.querySelectorAll("th[data-sortable]");
        const tbody = table.querySelector("tbody");

        if (!tbody || headers.length === 0) return;

        headers.forEach((header) => {
            // Add sort indicator
            if (!header.querySelector(".sort-indicator")) {
                const indicator = document.createElement("span");
                indicator.className = "sort-indicator ms-1 opacity-50";
                indicator.innerHTML = '<i class="bi bi-arrow-down-up" style="font-size: 0.8rem;"></i>';
                header.appendChild(indicator);
            }

            header.style.cursor = "pointer";
            header.style.userSelect = "none";
            header.classList.add("sort-header");

            header.addEventListener("click", () => {
                // Determine actual column index
                const realColIndex = header.cellIndex;
                
                // Remove sort classes from all headers in THIS table
                const allTableHeaders = table.querySelectorAll("th");
                allTableHeaders.forEach((h) => {
                    if (h !== header) {
                        h.classList.remove("sort-asc", "sort-desc");
                        h.removeAttribute("data-direction");
                        const ind = h.querySelector(".sort-indicator");
                        if (ind) {
                            ind.innerHTML = '<i class="bi bi-arrow-down-up" style="font-size: 0.8rem;"></i>';
                            ind.classList.add("opacity-50");
                        }
                    }
                });

                const rows = Array.from(tbody.querySelectorAll("tr"));
                if (rows.length <= 1) return;

                // Toggle direction
                let direction = header.getAttribute("data-direction");
                if (direction === "asc") direction = "desc";
                else direction = "asc";

                // Determine if column contains numbers
                const isNumber = header.dataset.type === "number";

                header.classList.remove("sort-asc", "sort-desc");
                header.classList.add(`sort-${direction}`);
                header.setAttribute("data-direction", direction);
                
                const indicator = header.querySelector(".sort-indicator");
                if (indicator) {
                    indicator.classList.remove("opacity-50");
                    indicator.innerHTML = direction === "asc" 
                        ? '<i class="bi bi-arrow-up-short" style="font-size: 1rem;"></i>' 
                        : '<i class="bi bi-arrow-down-short" style="font-size: 1rem;"></i>';
                }

                // Sort rows
                rows.sort((a, b) => {
                    const cellA = a.children[realColIndex];
                    const cellB = b.children[realColIndex];

                    if (!cellA || !cellB) return 0;

                    let valA = cellA.getAttribute('data-value') || cellA.textContent.trim();
                    let valB = cellB.getAttribute('data-value') || cellB.textContent.trim();

                    // Cleanup common empty placeholders
                    const isEmpty = (v) => v === "" || v === "-" || v === "—" || v === "NA" || v === "N/A" || v === null || v === undefined;

                    if (isEmpty(valA) && isEmpty(valB)) return 0;
                    if (isEmpty(valA)) return 1; // Always at bottom
                    if (isEmpty(valB)) return -1; // Always at bottom

                    if (isNumber) {
                        const getNum = (v) => {
                            const n = v.toString().replace(/,/g, "").match(/[-+]?[0-9]*\.?[0-9]+/);
                            return n ? parseFloat(n[0]) : 0;
                        };
                        const numA = getNum(valA);
                        const numB = getNum(valB);
                        return direction === "asc" ? numA - numB : numB - numA;
                    }

                    // String/Date comparison
                    return direction === "asc"
                        ? valA.localeCompare(valB, undefined, { numeric: true, sensitivity: "base" })
                        : valB.localeCompare(valA, undefined, { numeric: true, sensitivity: "base" });
                });

                // Re-append sorted rows
                const fragment = document.createDocumentFragment();
                rows.forEach((row) => fragment.appendChild(row));
                tbody.appendChild(fragment);
            });
        });
    });
};

document.addEventListener("DOMContentLoaded", () => {
    window.initTableSort();
});
