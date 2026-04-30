// Enhanced Table Sorting with Visual Indicators
window.initTableSort = function() {
    document.querySelectorAll(".sortable-table").forEach((table) => {
        // Prevent double initialization
        if (table.dataset.sortInitialized) return;
        table.dataset.sortInitialized = "true";

        const headers = table.querySelectorAll("th[data-sortable]");
        const tbody = table.querySelector("tbody");

        if (!tbody || headers.length === 0) return;

        headers.forEach((header, colIndex) => {
            // Add sort indicator
            if (!header.querySelector(".sort-indicator")) {
                const indicator = document.createElement("span");
                indicator.className = "sort-indicator";
                indicator.innerHTML = " ↕";
                header.appendChild(indicator);
            }

            let direction = null; // null → asc → desc

            header.style.cursor = "pointer";
            header.style.userSelect = "none";

            header.addEventListener("click", () => {
                // Remove sort classes from all headers
                headers.forEach((h) => {
                    h.classList.remove("sort-asc", "sort-desc");
                    const ind = h.querySelector(".sort-indicator");
                    if (ind) ind.textContent = " ↕";
                });

                const rows = Array.from(tbody.querySelectorAll("tr"));

                // Determine if column contains numbers
                const isNumber =
                    header.dataset.type === "number" ||
                    rows.every((row) => {
                        const cell = row.children[colIndex];
                        if (!cell) return false;
                        const text = cell.textContent.trim();
                        // Remove commas and check if it's a number
                        const numText = text.replace(/,/g, "");
                        return (
                            numText !== "" &&
                            !isNaN(numText) &&
                            !isNaN(parseFloat(numText))
                        );
                    });

                // Toggle direction
                if (direction === null) direction = "asc";
                else if (direction === "asc") direction = "desc";
                else direction = "asc";

                header.classList.add(`sort-${direction}`);
                const indicator = header.querySelector(".sort-indicator");
                if (indicator) {
                    indicator.textContent = direction === "asc" ? " ↑" : " ↓";
                }

                // Sort rows
                rows.sort((a, b) => {
                    const cellA = a.children[colIndex];
                    const cellB = b.children[colIndex];

                    if (!cellA || !cellB) return 0;

                    let A = cellA.textContent.trim();
                    let B = cellB.textContent.trim();

                    // Remove commas for number comparison
                    if (isNumber) {
                        A = parseFloat(A.replace(/,/g, "")) || 0;
                        B = parseFloat(B.replace(/,/g, "")) || 0;
                        return direction === "asc" ? A - B : B - A;
                    }

                    // String comparison
                    return direction === "asc"
                        ? A.localeCompare(B, undefined, {
                              numeric: true,
                              sensitivity: "base",
                          })
                        : B.localeCompare(A, undefined, {
                              numeric: true,
                              sensitivity: "base",
                          });
                });

                // Re-append sorted rows
                rows.forEach((row) => tbody.appendChild(row));
            });
        });
    });
};

document.addEventListener("DOMContentLoaded", () => {
    window.initTableSort();
});
