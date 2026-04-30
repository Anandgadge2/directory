{{-- Skeleton Loader Component --}}
<div class="skeleton-loader" id="skeleton-loader" style="display: none;">
    <div class="skeleton-overlay"></div>
    <div class="skeleton-content container-fluid">
        {{-- Header Skeleton --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="skeleton-header-card">
                <div class="skeleton-line skeleton-title-large"></div>
                <div class="skeleton-line skeleton-subtitle"></div>
            </div>
            <div class="skeleton-badge">
                <div class="skeleton-line skeleton-badge-text"></div>
            </div>
        </div>

        {{-- Info Alert Skeleton --}}
        <div class="skeleton-alert mb-4">
            <div class="skeleton-line skeleton-title"></div>
            <div class="skeleton-line skeleton-text"></div>
            <div class="skeleton-line skeleton-text-short"></div>
        </div>

        {{-- KPI Cards Skeleton (8 cards for executive dashboard) --}}
        <div class="row g-3 mb-4">
            @for($i = 0; $i < 8; $i++)
            <div class="col-md-3 col-sm-6">
                <div class="skeleton-card shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="skeleton-line skeleton-kpi-title"></div>
                            <div class="skeleton-line skeleton-kpi-value"></div>
                            <div class="skeleton-line skeleton-kpi-subtitle"></div>
                        </div>
                        <div class="skeleton-icon"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>

        {{-- Guard Performance Section Skeleton --}}
        <div class="mb-2">
            <div class="skeleton-line skeleton-section-title"></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="skeleton-card">
                    <div class="skeleton-line skeleton-title mb-3"></div>
                    <div class="skeleton-table">
                        @for($i = 0; $i < 5; $i++)
                        <div class="skeleton-table-row">
                            <div class="skeleton-line skeleton-avatar"></div>
                            <div class="skeleton-line skeleton-text"></div>
                            <div class="skeleton-line skeleton-number"></div>
                            <div class="skeleton-line skeleton-number"></div>
                            <div class="skeleton-line skeleton-number"></div>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="skeleton-card">
                    <div class="skeleton-line skeleton-title mb-3"></div>
                    <div class="skeleton-chart-small"></div>
                </div>
            </div>
        </div>

        {{-- Patrol Analytics Section Skeleton --}}
        <div class="mb-2">
            <div class="skeleton-line skeleton-section-title"></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="skeleton-card">
                    <div class="skeleton-line skeleton-title mb-3"></div>
                    <div class="skeleton-chart"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="skeleton-card">
                    <div class="skeleton-line skeleton-title mb-3"></div>
                    <div class="skeleton-chart"></div>
                </div>
            </div>
        </div>

        {{-- Incident Tracking Section Skeleton --}}
        <div class="mb-2">
            <div class="skeleton-line skeleton-section-title"></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="skeleton-card">
                    <div class="skeleton-line skeleton-title mb-3"></div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="skeleton-kpi-mini"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="skeleton-chart-mini"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="skeleton-chart-mini"></div>
                        </div>
                    </div>
                    <div class="skeleton-table">
                        @for($i = 0; $i < 4; $i++)
                        <div class="skeleton-table-row">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line"></div>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="skeleton-card mb-3">
                    <div class="skeleton-line skeleton-title mb-3"></div>
                    <div class="skeleton-chart-small"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.skeleton-loader {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 2000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(3px);
    overflow-y: auto;
}

.skeleton-content {
    position: relative;
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    padding-top: 100px; /* Account for navbar */
}

.skeleton-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.skeleton-line {
    height: 16px;
    background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    border-radius: 4px;
    margin-bottom: 10px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-title {
    width: 60%;
    height: 20px;
}

.skeleton-value {
    width: 40%;
    height: 32px;
    margin-top: 15px;
}

.skeleton-chart {
    height: 300px;
    background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    border-radius: 8px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-table {
    margin-top: 15px;
}

.skeleton-table-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.skeleton-table-row .skeleton-line {
    flex: 1;
    height: 14px;
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Executive Dashboard Specific Skeletons */
.skeleton-header-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    width: 60%;
}

.skeleton-title-large {
    width: 70%;
    height: 28px;
    margin-bottom: 8px;
}

.skeleton-subtitle {
    width: 90%;
    height: 16px;
}

.skeleton-badge {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 10px 15px;
    width: 200px;
}

.skeleton-badge-text {
    width: 100%;
    height: 20px;
}

.skeleton-alert {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

.skeleton-kpi-title {
    width: 60%;
    height: 14px;
    margin-bottom: 8px;
}

.skeleton-kpi-value {
    width: 40%;
    height: 32px;
    margin-bottom: 6px;
}

.skeleton-kpi-subtitle {
    width: 80%;
    height: 12px;
}

.skeleton-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-section-title {
    width: 200px;
    height: 14px;
    margin-bottom: 10px;
}

.skeleton-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-number {
    width: 50px;
    height: 16px;
}

.skeleton-chart-small {
    height: 200px;
    background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    border-radius: 8px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-chart-mini {
    height: 120px;
    background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    border-radius: 8px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-kpi-mini {
    height: 100px;
    background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    border-radius: 8px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-text {
    width: 85%;
    height: 14px;
}

.skeleton-text-short {
    width: 60%;
    height: 14px;
}
</style>

<script>
// Skeleton Loader Manager
class SkeletonLoader {
    constructor() {
        this.loader = document.getElementById('skeleton-loader');
    }

    show() {
        if (this.loader) {
            this.loader.style.display = 'block';
            // Prevent body scroll when skeleton is shown
            document.body.style.overflow = 'hidden';
        }
    }

    hide() {
        if (this.loader) {
            this.loader.style.display = 'none';
            // Restore body scroll
            document.body.style.overflow = '';
        }
    }
}

// Initialize skeleton loader when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.skeletonLoader = new SkeletonLoader();
    });
} else {
    window.skeletonLoader = new SkeletonLoader();
}
</script>
