/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Main Frontend Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Live search suggestions
    const searchInput = document.querySelector('.fk-search-input');
    const searchContainer = document.querySelector('.fk-search-box');

    if (searchInput && searchContainer) {
        let debounceTimer;
        let suggestionBox = document.createElement('div');
        suggestionBox.className = 'list-group position-absolute w-100 shadow-lg border-0';
        suggestionBox.style.zIndex = '1050';
        suggestionBox.style.top = '100%';
        suggestionBox.style.left = '0';
        suggestionBox.style.display = 'none';
        searchContainer.style.position = 'relative';
        searchContainer.appendChild(suggestionBox);

        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();
            if (query.length < 2) {
                suggestionBox.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                const baseUrl = window.BASE_URL || '';
                fetch(`${baseUrl}/api/products.php?action=search&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        suggestionBox.innerHTML = '';
                        if (data.success && data.products && data.products.length > 0) {
                            data.products.slice(0, 5).forEach(p => {
                                const item = document.createElement('a');
                                item.href = `${baseUrl}/customer/product-details.php?id=${p.product_id}`;
                                item.className = 'list-group-item list-group-item-action d-flex align-items-center py-2';
                                item.innerHTML = `
                                    <div class="me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                        <img src="${baseUrl}/${p.image}" alt="" style="max-height: 100%; max-width: 100%;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small text-dark">${p.product_name}</div>
                                        <div class="text-muted small">${p.ram} | ${p.storage} • <span class="text-primary fw-bold">₹${Number(p.final_price).toLocaleString('en-IN')}</span></div>
                                    </div>
                                `;
                                suggestionBox.appendChild(item);
                            });
                            suggestionBox.style.display = 'block';
                        } else {
                            suggestionBox.innerHTML = '<div class="list-group-item text-muted small p-3">No matching smartphones found</div>';
                            suggestionBox.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        suggestionBox.style.display = 'none';
                    });
            }, 250);
        });

        // Close suggestions on outside click
        document.addEventListener('click', (e) => {
            if (!searchContainer.contains(e.target)) {
                suggestionBox.style.display = 'none';
            }
        });
    }
});

/**
 * Global Toast Notification
 */
function showToast(message, type = 'success') {
    let toastContainer = document.getElementById('globalToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'globalToastContainer';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : 'bg-dark text-white');
    const icon = type === 'success' ? 'fa-circle-check' : (type === 'danger' ? 'fa-circle-xmark' : 'fa-info-circle');

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fa-solid ${icon}"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
    bsToast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}
