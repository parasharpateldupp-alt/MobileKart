/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Cart & Order Processing JavaScript (DFD P1.3)
 */

function addToCart(productId, quantity = 1, buyNow = false) {
    const baseUrl = window.BASE_URL || '';
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    const csrfEl = document.querySelector('meta[name="csrf-token"]');
    if (csrfEl) {
        formData.append('csrf_token', csrfEl.content);
    }

    fetch(`${baseUrl}/api/cart.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update navbar cart badge
            const badges = document.querySelectorAll('.fk-cart-badge');
            badges.forEach(b => {
                b.textContent = data.total_items;
                b.style.display = data.total_items > 0 ? 'inline-block' : 'none';
            });

            if (buyNow) {
                window.location.href = `${baseUrl}/customer/checkout.php`;
            } else {
                showToast(data.message || 'Added to cart successfully!', 'success');
            }
        } else {
            showToast(data.message || 'Unable to add item to cart.', 'danger');
        }
    })
    .catch(err => {
        console.error('Cart Error:', err);
        showToast('A network error occurred. Please try again.', 'danger');
    });
}

function updateCartQuantity(cartItemId, quantity) {
    if (quantity < 1) {
        removeFromCart(cartItemId);
        return;
    }

    const baseUrl = window.BASE_URL || '';
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('cart_item_id', cartItemId);
    formData.append('quantity', quantity);

    const csrfEl = document.querySelector('meta[name="csrf-token"]');
    if (csrfEl) {
        formData.append('csrf_token', csrfEl.content);
    }

    fetch(`${baseUrl}/api/cart.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'Could not update quantity.', 'danger');
        }
    })
    .catch(() => {
        showToast('Failed to update cart quantity.', 'danger');
    });
}

function removeFromCart(cartItemId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    const baseUrl = window.BASE_URL || '';
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('cart_item_id', cartItemId);

    const csrfEl = document.querySelector('meta[name="csrf-token"]');
    if (csrfEl) {
        formData.append('csrf_token', csrfEl.content);
    }

    fetch(`${baseUrl}/api/cart.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'Failed to remove item.', 'danger');
        }
    })
    .catch(() => {
        showToast('Network error while removing item.', 'danger');
    });
}
