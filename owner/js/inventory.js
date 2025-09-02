window.addEventListener('load', function() {
    const loader = document.getElementById('loader-wrapper');
    if(loader) {
        loader.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // --- Add Product Modal Logic ---
    const addProductModal = document.getElementById('add-product-modal');
    const addProductBtn = document.getElementById('add-product-btn');
    const addProductCloseModalBtn = addProductModal.querySelector('.close-modal-btn');
    const addProductForm = document.getElementById('add-product-form');
    const nonExpiringCheckbox = document.getElementById('non-expiring');
    const expirationDateInput = document.getElementById('expiration_date');

    if (addProductBtn) {
        addProductBtn.addEventListener('click', () => { addProductModal.style.display = 'flex'; });
    }
    if (addProductCloseModalBtn) {
        addProductCloseModalBtn.addEventListener('click', () => { addProductModal.style.display = 'none'; });
    }
    
    if (nonExpiringCheckbox) {
        nonExpiringCheckbox.addEventListener('change', function() {
            expirationDateInput.disabled = this.checked;
            if (this.checked) {
                expirationDateInput.value = '';
            }
        });
    }

    if(addProductForm) {
        addProductForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_product');

            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert('Product added successfully!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        });
    }

    // --- Add Stock Modal Logic ---
    const addStockModal = document.getElementById('add-stock-modal');
    if (addStockModal) {
        const addStockForm = document.getElementById('add-stock-form');
        const addStockProductIdInput = document.getElementById('add-stock-product-id');
        const addStockCloseModalBtn = addStockModal.querySelector('.close-modal-btn');

        document.querySelectorAll('.add-stock-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                addStockProductIdInput.value = productId;
                addStockModal.style.display = 'flex';
            });
        });

        if (addStockCloseModalBtn) {
            addStockCloseModalBtn.addEventListener('click', () => {
                addStockModal.style.display = 'none';
            });
        }
        
        window.addEventListener('click', (event) => {
             if (event.target == addStockModal) { addStockModal.style.display = 'none'; }
        });

        if (addStockForm) {
            addStockForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add_stock');

                const response = await fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('Stock added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }
    }


    // --- Archive Button Logic ---
    document.querySelectorAll('.archive-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const productId = this.dataset.id;
            if (confirm('Are you sure you want to archive this product? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'archive_stock');
                formData.append('product_id', productId);

                const response = await fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('Product archived successfully!');
                    // Remove the row from the table instead of reloading
                    const rowToRemove = document.getElementById('product-row-' + productId);
                    if (rowToRemove) {
                        rowToRemove.remove();
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            }
        });
    });

    // Close modals on window click
     window.addEventListener('click', (event) => {
        if (event.target == addProductModal) { addProductModal.style.display = 'none'; }
    });

    // --- Notification Real-Time WebSocket Logic ---
    if (window.WebSocket) {
        // Change the URL to your WebSocket server endpoint
        var wsProtocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
        var wsHost = window.location.hostname + ':8080'; // Adjust port as needed
        var ws = new WebSocket(wsProtocol + wsHost + '/notifications');
        ws.onmessage = function(event) {
            // Assume the server sends a message when a new notification is available
            fetchNotifications();
        };
        ws.onerror = function() {
            // Fallback to polling if WebSocket fails
            if (!window._notifPollingStarted) {
                setInterval(fetchNotifications, 30000);
                window._notifPollingStarted = true;
            }
        };
    } else {
        setInterval(fetchNotifications, 30000);
    }
}); 