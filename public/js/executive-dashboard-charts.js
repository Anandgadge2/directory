// Executive Dashboard Charts
window.initializeCharts = function () {
    console.log("Initializing Executive Dashboard Charts...");

    function normalizeSeries(
        labels,
        data,
        fallbackLabel = "No data available",
    ) {
        if (
            !Array.isArray(labels) ||
            !Array.isArray(data) ||
            labels.length === 0 ||
            data.length === 0 ||
            data.every((v) => v === 0)
        ) {
            return {
                labels: [fallbackLabel],
                data: [0],
                isEmpty: true,
            };
        }
        return { labels, data, isEmpty: false };
    }

    // Plugin for "No Data" message overlay
    const noDataPlugin = {
        id: "noDataPlugin",
        afterDraw: (chart) => {
            if (
                chart.data.datasets.length === 0 ||
                (chart.data.datasets[0].data.length <= 1 &&
                    chart.data.datasets[0].data[0] === 0)
            ) {
                const { ctx, width, height } = chart;
                chart.clear();
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.font = "16px Inter, system-ui";
                ctx.fillStyle = "#6c757d";
                ctx.fillText(
                    "No data available for this period",
                    width / 2,
                    height / 2,
                );
                ctx.restore();
            }
        },
    };

    // Destroy existing charts to prevent memory leaks and "canvas already in use" errors
    Chart.helpers.each(Chart.instances, function (instance) {
        instance.destroy();
    });

    // Incident Status Chart
    if (
        typeof window.incidentTrackingData !== "undefined" &&
        document.getElementById("incidentStatusChart")
    ) {
        const norm = normalizeSeries(
            window.incidentTrackingData.statusLabels,
            window.incidentTrackingData.statusData,
        );
        const statusCtx = document
            .getElementById("incidentStatusChart")
            .getContext("2d");

        // Explicit color map based on label substring
        const getStatusColor = (label) => {
            const l = label.toLowerCase();
            if (l.includes("resolved")) return "#28a745"; // Green
            if (l.includes("pending")) return "#ff8c8c"; // Red/Pink
            if (l.includes("escalated")) return "#fd7e14"; // Orange
            if (l.includes("ignored")) return "#6c757d"; // Gray
            if (l.includes("reverted")) return "#17a2b8"; // Cyan
            return "#ffc107"; // Yellow default
        };

        new Chart(statusCtx, {
            type: "doughnut",
            plugins: [noDataPlugin],
            data: {
                labels: norm.labels,
                datasets: [
                    {
                        data: norm.data,
                        backgroundColor: norm.isEmpty
                            ? ["#f8f9fa"]
                            : norm.labels.map((label) => getStatusColor(label)),
                        borderWidth: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "65%",
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const label = norm.labels[index];
                        // Map labels back to status handles if possible, or just pass labels
                        // The controller handles status searching
                        if (typeof window.showIncidentsByType === "function") {
                            window.showIncidentsByType(
                                label,
                                `Status: ${label}`,
                                { fetchByStatus: "true" },
                            );
                        }
                    }
                },
                onHover: (event, elements) => {
                    event.native.target.style.cursor = elements.length
                        ? "pointer"
                        : "default";
                },
                plugins: {
                    legend: {
                        display: !norm.isEmpty,
                        position: "right",
                        align: "center",
                        labels: {
                            boxWidth: 15,
                            padding: 15,
                            usePointStyle: false,
                            font: { size: 12 },
                        },
                    },
                    tooltip: { enabled: !norm.isEmpty },
                },
            },
        });
    }

    // Incident Priority Chart
    if (
        typeof window.incidentTrackingData !== "undefined" &&
        document.getElementById("incidentPriorityChart")
    ) {
        const norm = normalizeSeries(
            window.incidentTrackingData.priorityLabels,
            window.incidentTrackingData.priorityData,
        );
        const priorityCtx = document
            .getElementById("incidentPriorityChart")
            .getContext("2d");
        new Chart(priorityCtx, {
            type: "doughnut",
            plugins: [noDataPlugin],
            data: {
                labels: norm.labels,
                datasets: [
                    {
                        data: norm.data,
                        backgroundColor: norm.isEmpty
                            ? ["#f8f9fa"]
                            : ["#ff9999", "#ffc107", "#28a745"],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: !norm.isEmpty },
                    tooltip: { enabled: !norm.isEmpty },
                },
            },
        });
    }

    // Incident Type Chart
    if (
        typeof window.incidentTrackingData !== "undefined" &&
        document.getElementById("incidentTypeChart")
    ) {
        const norm = normalizeSeries(
            window.incidentTrackingData.typeLabels,
            window.incidentTrackingData.typeData,
        );
        const typeCtx = document
            .getElementById("incidentTypeChart")
            .getContext("2d");

        // Premium Color Palette for bars (harmonious forest-themed)
        const barColors = [
            { start: "#2ecc71", end: "#27ae60" }, // Emerald/Green
            { start: "#3498db", end: "#2980b9" }, // Blue
            { start: "#9b59b6", end: "#8e44ad" }, // Purple
            { start: "#e67e22", end: "#d35400" }, // Carrot/Orange
            { start: "#e74c3c", end: "#c0392b" }, // Alizarin/Red
            { start: "#1abc9c", end: "#16a085" }, // Turquoise
            { start: "#f1c40f", end: "#f39c12" }, // Sun Flower/Yellow
            { start: "#34495e", end: "#2c3e50" }, // Wet Asphalt
            { start: "#7f8c8d", end: "#95a5a6" }, // Asbestos
            { start: "#d35400", end: "#e67e22" }, // Pumpkin
        ];

        // Generate gradients
        const gradientBackgrounds = norm.isEmpty
            ? ["#f8f9fa"]
            : norm.labels.map((label, index) => {
                  const colors = barColors[index % barColors.length];
                  const gradient = typeCtx.createLinearGradient(0, 0, 0, 400);
                  gradient.addColorStop(0, colors.start);
                  gradient.addColorStop(1, colors.end);
                  return gradient;
              });

        // Custom plugin to show values on top of bars
        const chartValueLabels = {
            id: "chartValueLabels",
            afterDatasetsDraw(chart) {
                const { ctx, data } = chart;
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "bottom";
                ctx.font = "bold 13px 'Inter', sans-serif";
                ctx.fillStyle = "#444";

                chart.getDatasetMeta(0).data.forEach((bar, index) => {
                    const value = data.datasets[0].data[index];
                    if (value > 0) {
                        ctx.fillText(value, bar.x, bar.y - 8);
                    }
                });
                ctx.restore();
            },
        };

        new Chart(typeCtx, {
            type: "bar",
            plugins: [noDataPlugin, chartValueLabels],
            data: {
                labels: norm.labels,
                datasets: [
                    {
                        label: "Incidents",
                        data: norm.data,
                        backgroundColor: gradientBackgrounds,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 35,
                        maxBarThickness: 50,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Convert to Horizontal Bar for better label readability
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const label = norm.labels[index];
                        const key =
                            window.incidentTrackingData.typeKeys &&
                            window.incidentTrackingData.typeKeys[index]
                                ? window.incidentTrackingData.typeKeys[index]
                                : label;

                        if (typeof window.showIncidentsByType === "function") {
                            window.showIncidentsByType(key, `${label} Details`);
                        }
                    }
                },
                onHover: (event, elements) => {
                    event.native.target.style.cursor = elements.length
                        ? "pointer"
                        : "default";
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 12, weight: '500' } },
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: "rgba(0, 0, 0, 0.05)",
                        },
                    },
                    y: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            font: { size: 12, weight: 'bold' },
                            color: '#333'
                        }
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: !norm.isEmpty,
                        backgroundColor: "rgba(0, 0, 0, 0.8)",
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                return ` Total Incidents: ${context.raw}`;
                            }
                        }
                    },
                },
                layout: {
                    padding: {
                        left: 10,
                        right: 30, // Space for labels
                        top: 20,
                        bottom: 10,
                    },
                },
            },
        });
    }

    // Patrol Type Chart
    if (
        typeof window.patrolAnalyticsData !== "undefined" &&
        document.getElementById("patrolTypeChart")
    ) {
        const norm = normalizeSeries(
            window.patrolAnalyticsData.typeLabels,
            window.patrolAnalyticsData.typeCounts,
        );
        const patrolTypeCtx = document
            .getElementById("patrolTypeChart")
            .getContext("2d");
        new Chart(patrolTypeCtx, {
            type: "bar",
            plugins: [noDataPlugin],
            data: {
                labels: norm.labels,
                datasets: [
                    {
                        label: "Count",
                        data: norm.data,
                        backgroundColor: norm.isEmpty ? "#f8f9fa" : "#28a745",
                    },
                    {
                        label: "Distance (km)",
                        data: norm.isEmpty
                            ? [0]
                            : window.patrolAnalyticsData.typeDistances,
                        backgroundColor: norm.isEmpty ? "#f8f9fa" : "#17a2b8",
                        yAxisID: "y1",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const label = norm.labels[index];
                        if (typeof window.showPatrolsByType === "function") {
                            window.showPatrolsByType(
                                label,
                                `Patrols: ${label}`,
                            );
                        }
                    }
                },
                onHover: (event, elements) => {
                    event.native.target.style.cursor = elements.length
                        ? "pointer"
                        : "default";
                },
                scales: {
                    y: { beginAtZero: true },
                    y1: { beginAtZero: true, position: "right" },
                },
            },
        });
    }

    // Daily Patrol Trend Chart
    if (
        typeof window.patrolAnalyticsData !== "undefined" &&
        document.getElementById("dailyPatrolTrendChart")
    ) {
        const norm = normalizeSeries(
            window.patrolAnalyticsData.dailyLabels,
            window.patrolAnalyticsData.dailyCounts,
        );
        const dailyTrendCtx = document
            .getElementById("dailyPatrolTrendChart")
            .getContext("2d");
        new Chart(dailyTrendCtx, {
            type: "line",
            plugins: [noDataPlugin],
            data: {
                labels: norm.labels,
                datasets: [
                    {
                        label: "Patrol Count",
                        data: norm.data,
                        borderColor: norm.isEmpty ? "#f8f9fa" : "#007bff",
                        backgroundColor: norm.isEmpty
                            ? "rgba(248, 249, 250, 0.1)"
                            : "rgba(0, 123, 255, 0.1)",
                        tension: 0.4,
                    },
                    {
                        label: "Distance (km)",
                        data: norm.isEmpty
                            ? [0]
                            : window.patrolAnalyticsData.dailyDistances,
                        borderColor: norm.isEmpty ? "#f8f9fa" : "#28a745",
                        backgroundColor: norm.isEmpty
                            ? "rgba(248, 249, 250, 0.1)"
                            : "rgba(40, 167, 69, 0.1)",
                        yAxisID: "y1",
                        tension: 0.4,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true },
                    y1: { beginAtZero: true, position: "right" },
                },
            },
        });
    }

    // Attendance Trend Chart
    if (
        typeof window.attendanceData !== "undefined" &&
        document.getElementById("attendanceTrendChart")
    ) {
        const norm = normalizeSeries(
            window.attendanceData.dailyLabels,
            window.attendanceData.presentData,
        );
        const attendanceTrendCtx = document
            .getElementById("attendanceTrendChart")
            .getContext("2d");
        new Chart(attendanceTrendCtx, {
            type: "line",
            plugins: [noDataPlugin],
            data: {
                labels: norm.labels,
                datasets: [
                    {
                        label: "Present",
                        data: norm.data,
                        borderColor: norm.isEmpty ? "#f8f9fa" : "#28a745",
                        backgroundColor: norm.isEmpty
                            ? "rgba(248, 249, 250, 0.1)"
                            : "rgba(40, 167, 69, 0.1)",
                        tension: 0.4,
                    },
                    {
                        label: "Absent",
                        data: norm.isEmpty
                            ? [0]
                            : window.attendanceData.absentData,
                        borderColor: norm.isEmpty ? "#f8f9fa" : "#dc3545",
                        backgroundColor: norm.isEmpty
                            ? "rgba(248, 249, 250, 0.1)"
                            : "rgba(220, 53, 69, 0.1)",
                        tension: 0.4,
                    },
                    {
                        label: "Late",
                        data: norm.isEmpty
                            ? [0]
                            : window.attendanceData.lateData,
                        borderColor: norm.isEmpty ? "#f8f9fa" : "#ffc107",
                        backgroundColor: norm.isEmpty
                            ? "rgba(248, 249, 250, 0.1)"
                            : "rgba(255, 193, 7, 0.1)",
                        tension: 0.4,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    }

    // Hourly Distribution Chart
    if (
        typeof window.timePatternsData !== "undefined" &&
        document.getElementById("hourlyDistributionChart")
    ) {
        const norm = normalizeSeries(
            window.timePatternsData.hourlyLabels,
            window.timePatternsData.hourlyData,
        );
        const hourlyCtx = document
            .getElementById("hourlyDistributionChart")
            .getContext("2d");
        new Chart(hourlyCtx, {
            type: "bar",
            plugins: [noDataPlugin],
            data: {
                labels: norm.labels,
                datasets: [
                    {
                        label: "Patrol Count",
                        data: norm.data,
                        backgroundColor: norm.isEmpty ? "#f8f9fa" : "#17a2b8",
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    }
};

// Initial run
document.addEventListener("DOMContentLoaded", function () {
    window.initializeCharts();
});
