/**
 * Global Error Handling and UI Enhancement Script
 * Handles skeleton loaders, toast notifications, and error handling
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize skeleton loader on page load if filters are applied
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilters = urlParams.has('range') || urlParams.has('beat') || 
                       urlParams.has('user') || urlParams.has('start_date') || 
                       urlParams.has('end_date') || urlParams.has('guard_search');
    
    // Skip skeleton loader on load for manual apply forms (like Reports)
    const filterForm = document.querySelector('.global-filter-grid');
    const isManualApply = filterForm && filterForm.dataset.manualApply === 'true';

    if (hasFilters && window.skeletonLoader && !isManualApply) {
        // Show skeleton briefly to indicate loading
        window.skeletonLoader.show();
        
        // Hide after a short delay (data should load quickly)
        setTimeout(() => {
            if (window.skeletonLoader) {
                window.skeletonLoader.hide();
            }
        }, 500);
    }

    // Handle form submissions with loading states
    const forms = document.querySelectorAll('form[method="GET"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Show skeleton loader - Skip for forms with manual apply (like Reports)
            if (window.skeletonLoader && form.dataset.manualApply !== 'true') {
                window.skeletonLoader.show();
            }
            
            // Show filter loader (The spinner requested by user)
            const filterLoader = document.querySelector('.filter-loading');
            if (filterLoader) {
                filterLoader.style.display = 'flex';
            }
        });
    });

    // Handle AJAX errors globally
    if (window.fetch) {
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response;
                })
                .catch(error => {
                    if (window.toast) {
                        window.toast.error('Network error: ' + error.message);
                    }
                    throw error;
                });
        };
    }

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        if (window.toast) {
            window.toast.error('An unexpected error occurred. Please refresh the page.');
        }
    });

    // Handle JavaScript errors
    window.addEventListener('error', function(event) {
        console.error('JavaScript error:', event.error);
        if (window.toast && event.error) {
            // Only show user-friendly errors, not all JS errors
            if (event.error.message && !event.error.message.includes('Script error')) {
                window.toast.error('An error occurred while loading the page.');
            }
        }
    });

    // Auto-hide skeleton loader when all content is loaded
    window.addEventListener('load', function() {
        if (window.skeletonLoader) {
            window.skeletonLoader.hide();
        }
        
        const filterLoader = document.querySelector('.filter-loading');
        if (filterLoader) {
            filterLoader.style.display = 'none';
        }
    });

    // Show success message when filters are applied
    if (hasFilters && window.toast) {
        setTimeout(() => {
            window.toast.info('Filters applied successfully', 2000);
        }, 1000);
    }
});

// Utility function to show loading state
function showLoading() {
    if (window.skeletonLoader) window.skeletonLoader.show();
    const filterLoader = document.querySelector('.filter-loading');
    if (filterLoader) filterLoader.style.display = 'flex';
}

// Utility function to hide loading state
function hideLoading() {
    if (window.skeletonLoader) window.skeletonLoader.hide();
    const filterLoader = document.querySelector('.filter-loading');
    if (filterLoader) filterLoader.style.display = 'none';
}

// Export for use in other scripts
window.showLoading = showLoading;
window.hideLoading = hideLoading;
