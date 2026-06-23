/**
 * admin/admin.js — Admin dashboard JavaScript
 */

// Utility functions
function showNotification(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.insertBefore(alert, document.body.firstChild);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// CSRF token retrieval
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Fetch with error handling
async function apiFetch(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            },
        });
        
        if (!response.ok) {
            throw new Error(`API error: ${response.statusText}`);
        }
        
        return await response.json();
    } catch (error) {
        showNotification(`Error: ${error.message}`, 'danger');
        throw error;
    }
}

// Confirm action with modal
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Format bytes to human readable
function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Date formatting
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Initialize tooltips (Bootstrap)
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Export to CSV helper
function exportToCSV(filename, data) {
    const csv = convertArrayToCSV(data);
    downloadCSV(csv, filename);
}

function convertArrayToCSV(data) {
    const array = [Object.keys(data[0])].concat(data);
    return array.map(it => {
        return Object.values(it).map(x => {
            x = x === null || x === undefined ? '' : x.toString().replace(/"/g, '""');
            return `"${x}"`;
        }).toString();
    }).join('\n');
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], {type: "text/csv"});
    const downloadLink = document.createElement("a");
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Status badge colors
const statusColors = {
    'new': '#ff8c8c',
    'reviewed': '#ffd700',
    'downloaded': '#86efac',
    'draft': '#ffd700',
    'published': '#86efac',
    'archived': '#a0aec0',
};

function getStatusColor(status) {
    return statusColors[status] || '#a0aec0';
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Cmd/Ctrl + K to focus search
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        const search = document.querySelector('input[name="search"]');
        if (search) search.focus();
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        document.querySelectorAll('[id*="form"]').forEach(form => {
            form.style.display = 'none';
        });
    }
});

console.log('Admin dashboard initialized');
