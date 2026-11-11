
let stockProducts = [];
let currentCategory = 'bebida'; // Default

document.addEventListener('DOMContentLoaded', () => {
    // Init tabs
    const tabs = document.querySelectorAll('.stock-category-btn');
    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            // Remove active
            tabs.forEach(t => t.classList.remove('active'));
            // Add active
            e.currentTarget.classList.add('active');
            
            currentCategory = e.currentTarget.dataset.category;
            loadStockProducts();
        });
    });

    const toUpper = (el) => {
        if (!el) return;
        el.addEventListener('input', () => {
            el.value = el.value.toUpperCase();
        });
    };

    toUpper(document.getElementById('new-name'));
    toUpper(document.getElementById('edit-name'));

    // New Product
    const btnNew = document.getElementById('btn-open-new-product');
    if (btnNew) {
        btnNew.addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('newProductModal'));
            modal.show();
        });
    }

    // Save New
    const btnSaveNew = document.getElementById('btn-save-new-product');
    if (btnSaveNew) {
        btnSaveNew.addEventListener('click', saveNewProduct);
    }

    // Save Edit
    const btnSaveEdit = document.getElementById('btn-save-product');
    if (btnSaveEdit) {
        btnSaveEdit.addEventListener('click', saveProduct);
    }
    
    // EAN Lookup (New)
    const btnLookup = document.getElementById('btn-lookup-ean');
    if(btnLookup) {
        btnLookup.addEventListener('click', () => lookupEan('new'));
    }

    // Initial load
    loadStockProducts();
});

function loadStockProducts() {
    const tbody = document.getElementById('stock-table-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary"></div></td></tr>';

    axios.get('api.php?action=get_products&category=' + currentCategory)
        .then(res => {
            stockProducts = res.data || [];
            renderStockTable();
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erro ao carregar produtos.</td></tr>';
        });
}

function renderStockTable() {
    const tbody = document.getElementById('stock-table-body');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (stockProducts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum produto encontrado nesta categoria.</td></tr>';
        return;
    }

    stockProducts.forEach(p => {
        const tr = document.createElement('tr');
        
        // Stock Status Color
        let stockClass = 'text-success';
        if (p.stock_quantity <= p.min_stock) stockClass = 'text-danger fw-bold';
        else if (p.stock_quantity <= p.min_stock * 1.5) stockClass = 'text-warning fw-bold';

        tr.innerHTML = `
            <td>
                <div class="fw-bold">${p.name}</div>
                <small class="text-muted">${p.category}</small>
            </td>
            <td>${p.ean || p.barcode || '-'}</td>
            <td>R$ ${parseFloat(p.cost_price || 0).toFixed(2)}</td>
            <td>R$ ${parseFloat(p.price || 0).toFixed(2)}</td>
            <td class="${stockClass}">${p.stock_quantity}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="openEditProduct(${p.id})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function saveNewProduct() {
    const name = document.getElementById('new-name').value;
    const category = document.getElementById('new-category').value;
    const price = document.getElementById('new-price').value;
    const cost = document.getElementById('new-cost').value;
    const stock = document.getElementById('new-stock').value;
    const min = document.getElementById('new-min').value;
    const ean = document.getElementById('new-ean').value;

    if (!name || !price) {
        Swal.fire('Erro', 'Nome e Preço de Venda são obrigatórios.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-save-new-product');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm"></div> Salvar';
    btn.disabled = true;

    axios.post('api.php?action=create_product', {
        name, category, price, cost_price: cost, stock_quantity: stock, min_stock: min, ean
    }).then(res => {
        if (res.data.success) {
            Swal.fire('Sucesso', 'Produto cadastrado!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('newProductModal')).hide();
            // Clear inputs
            document.getElementById('new-name').value = '';
            document.getElementById('new-ean').value = '';
            document.getElementById('new-price').value = '';
            document.getElementById('new-cost').value = '0.00';
            document.getElementById('new-stock').value = '0';
            
            // Reload
            if (currentCategory === category) {
                loadStockProducts();
            } else {
                // Optionally switch category
            }
        } else {
            Swal.fire('Erro', res.data.message || 'Erro ao cadastrar.', 'error');
        }
    }).catch(err => {
        Swal.fire('Erro', 'Erro de comunicação.', 'error');
    }).finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function openEditProduct(id) {
    const product = stockProducts.find(p => p.id === id);
    if (!product) return;

    document.getElementById('edit-id').value = product.id;
    document.getElementById('edit-name').value = product.name;
    document.getElementById('edit-category').value = product.category;
    document.getElementById('edit-ean').value = product.ean || product.barcode || '';
    document.getElementById('edit-cost').value = product.cost_price;
    document.getElementById('edit-price').value = product.price;
    document.getElementById('edit-min').value = product.min_stock;
    document.getElementById('edit-current-stock').innerText = product.stock_quantity;
    document.getElementById('edit-current-stock-input').value = product.stock_quantity;
    document.getElementById('edit-add-stock').value = 0;

    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    modal.show();
}

function saveProduct() {
    const id = document.getElementById('edit-id').value;
    const name = document.getElementById('edit-name').value;
    const category = document.getElementById('edit-category').value;
    const price = document.getElementById('edit-price').value;
    const cost = document.getElementById('edit-cost').value;
    const min = document.getElementById('edit-min').value;
    const ean = document.getElementById('edit-ean').value;
    const addStock = document.getElementById('edit-add-stock').value;
    const setStock = document.getElementById('edit-current-stock-input').value;

    const btn = document.getElementById('btn-save-product');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm"></div> Salvar';
    btn.disabled = true;

    axios.post('api.php?action=update_product', {
        id, name, category, price, cost_price: cost, min_stock: min, ean,
        add_stock: addStock,
        stock_quantity: setStock
    }).then(res => {
        if (res.data.success) {
            Swal.fire('Sucesso', 'Produto atualizado!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
            loadStockProducts();
        } else {
            Swal.fire('Erro', res.data.message || 'Erro ao atualizar.', 'error');
        }
    }).catch(err => {
        Swal.fire('Erro', 'Erro de comunicação.', 'error');
    }).finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function lookupEan(mode) {
    const inputId = mode === 'new' ? 'new-ean' : 'edit-ean';
    const ean = document.getElementById(inputId).value;
    
    if (!ean) {
        Swal.fire('Atenção', 'Digite um EAN primeiro.', 'warning');
        return;
    }

    axios.get('api.php?action=lookup_ean&ean=' + ean)
        .then(res => {
            if (res.data.success && res.data.product) {
                const p = res.data.product;
                if (mode === 'new') {
                    document.getElementById('new-name').value = p.product_name || '';
                }
            } else {
                Swal.fire('Info', 'Produto não encontrado na base de dados global.', 'info');
            }
        })
        .catch(err => console.error(err));
}
