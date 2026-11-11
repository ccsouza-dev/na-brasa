
// --- HELPER FUNCTIONS ---

function formatMoney(value) {
    return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// --- DASHBOARD LOGIC ---

let dashboardInterval = null;

// Expose loadOrders globally for add_item.js
window.loadOrders = function() {
    fetchTablesStatus();
};

function initDashboard() {
    const container = document.getElementById('orders-container');
    if (container) {
        fetchTablesStatus();
        // Clear any existing interval to avoid duplicates
        if (dashboardInterval) clearInterval(dashboardInterval);
        dashboardInterval = setInterval(fetchTablesStatus, 5000); // Poll every 5s
    }
}

function fetchTablesStatus() {
    axios.get((window.API_URL || 'api.php') + '?action=get_tables_status')
        .then(res => {
            renderTables(res.data);
            updateTotalOpenValue(res.data);
        })
        .catch(err => {
            console.error('Erro ao buscar mesas:', err);
            const container = document.getElementById('orders-container');
            if (container) {
                // Don't wipe everything on a single transient error, but if it's empty, show error
                if (container.innerHTML.includes('spinner-border')) {
                    container.innerHTML = `
                        <div class="col-12 text-center mt-5">
                            <div class="alert alert-danger d-inline-block">
                                <i class="fas fa-exclamation-triangle me-2"></i> Erro ao carregar pedidos.
                                <br>
                                <button class="btn btn-sm btn-outline-danger mt-2" onclick="fetchTablesStatus()">Tentar Novamente</button>
                            </div>
                        </div>
                    `;
                }
            }
        });
}

function renderTables(tables) {
    const container = document.getElementById('orders-container');
    if (!container) return;

    if (!tables || !Array.isArray(tables) || tables.length === 0) {
        container.innerHTML = '<div class="col-12 text-center mt-5"><p class="text-muted">Nenhuma mesa encontrada.</p></div>';
        return;
    }

    let html = '';
    
    // Sort tables by number
    tables.sort((a, b) => a.number - b.number);

    tables.forEach(table => {
        const isBusy = table.status === 'busy';
        const cardClass = isBusy ? 'border-danger' : 'border-success';
        const bgClass = isBusy ? 'bg-white' : 'bg-light';
        const statusText = isBusy ? 'OCUPADA' : 'LIVRE';
        const statusColor = isBusy ? 'text-danger' : 'text-success';
        
        let ordersHtml = '';
        if (isBusy && table.orders) {
            table.orders.forEach(order => {
                // Filter items for this order
                const orderItems = table.items ? table.items.filter(i => i.order_id === order.id) : [];
                
                let itemsListHtml = '';
                if (orderItems.length > 0) {
                    itemsListHtml = '<div class="mt-2 small border-top pt-1">';
                    orderItems.forEach(item => {
                        itemsListHtml += `
                            <div class="d-flex justify-content-between text-muted">
                                <span>${item.qty}x ${item.name}</span>
                                <span>R$ ${formatMoney(item.subtotal)}</span>
                            </div>
                        `;
                    });
                    itemsListHtml += '</div>';
                }

                // Escape strings for safety
                const safeCustomer = (order.customer_name || '').replace(/'/g, "\\'");
                
                ordersHtml += `
                    <div class="mb-2 border-bottom pb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <span class="fw-bold small">${order.customer_name || 'Cliente'}</span>
                                <br>
                                <small class="text-muted">#${order.id}</small>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold text-dark">R$ ${formatMoney(order.total)}</span>
                            </div>
                        </div>
                        ${itemsListHtml}
                    </div>
                `;
            });
        } else {
            ordersHtml = `<div class="text-center text-muted py-3"><small>Mesa Livre</small></div>`;
        }

        // Card HTML
        html += `
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 shadow-sm ${cardClass} ${bgClass}">
                    <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom-0 pb-0 pt-3">
                        <h5 class="card-title fw-bold mb-0 text-dark">Mesa ${table.number}</h5>
                        <span class="badge ${isBusy ? 'bg-danger' : 'bg-success'}">${statusText}</span>
                    </div>
                    <div class="card-body">
                        <div class="orders-list mb-3">
                            ${ordersHtml}
                        </div>
                        
                        ${isBusy ? `
                            <div class="d-flex justify-content-between align-items-center mt-auto border-top pt-2">
                                <span class="h5 mb-0 fw-bold text-dark">Total:</span>
                                <span class="h5 mb-0 fw-bold text-danger">R$ ${formatMoney(table.total || 0)}</span>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
                        ${isBusy ? `
                            <div class="btn-group btn-group-sm me-2">
                                <button class="btn btn-outline-danger" onclick="deleteOrderConfirm(${table.orders[0]?.id})" title="Excluir Comanda">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-outline-secondary" onclick="openEditOrder(${table.orders[0]?.id}, '${(table.orders[0]?.customer_name || '').replace(/'/g, "\\'")}', ${table.number})" title="Editar/Detalhes">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-primary" onclick="openAddItemModal(${table.orders[0]?.id}, ${table.number})" title="Adicionar Item">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button class="btn btn-success btn-sm flex-grow-1" onclick="closeTable(${table.number}, ${table.orders[0]?.id}, ${table.total || 0})">
                                <i class="fas fa-check-circle me-1"></i> Fechar
                            </button>
                        ` : `
                            <button class="btn btn-primary btn-sm w-100" onclick="openNewOrderModal(${table.number})">
                                <i class="fas fa-plus-circle me-1"></i> Abrir Mesa
                            </button>
                        `}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function updateTotalOpenValue(tables) {
    const totalEl = document.getElementById('total-open-tables-value');
    if (!totalEl) return;

    let total = 0;
    if (tables && Array.isArray(tables)) {
        total = tables.reduce((acc, t) => acc + (parseFloat(t.total) || 0), 0);
    }
    
    totalEl.textContent = `R$ ${formatMoney(total)}`;
}

// --- ORDER ACTIONS ---

function openNewOrderModal(tableNum) {
    Swal.fire({
        title: `Abrir Mesa ${tableNum}`,
        input: 'text',
        inputLabel: 'Nome do Cliente (Opcional)',
        showCancelButton: true,
        confirmButtonText: 'Abrir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post((window.API_URL || 'api.php') + '?action=create_order', {
                table: tableNum,
                customer: result.value
            }).then(res => {
                if (res.data.success) {
                    fetchTablesStatus();
                } else {
                    Swal.fire('Erro', res.data.message, 'error');
                }
            });
        }
    });
}

function closeTable(tableNum, orderId, totalAmount) {
    if (!orderId) return;

    // Ask for payment method
    Swal.fire({
        title: `Fechar Mesa ${tableNum}`,
        width: '600px',
        html: `
            <style>
                .money-bill {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 60px;
                    height: 30px;
                    margin: 2px;
                    border-radius: 3px;
                    color: white;
                    font-weight: bold;
                    font-size: 11px;
                    box-shadow: 1px 1px 2px rgba(0,0,0,0.2);
                }
                .bill-200 { background-color: #8c8c8c; }
                .bill-100 { background-color: #3bb2d0; }
                .bill-50  { background-color: #c67f42; }
                .bill-20  { background-color: #d4af37; }
                .bill-10  { background-color: #cc5c5c; }
                .bill-5   { background-color: #8c5ebb; }
                .bill-2   { background-color: #355e3b; }
                
                .money-coin {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 26px;
                    height: 26px;
                    margin: 2px;
                    border-radius: 50%;
                    border: 2px solid #999;
                    font-weight: bold;
                    font-size: 10px;
                    background-color: #e0e0e0;
                    color: #333;
                    box-shadow: 1px 1px 2px rgba(0,0,0,0.2);
                }
                .coin-1 { border-color: #ffd700; background: radial-gradient(circle, #e0e0e0 60%, #ffd700 61%); }
                .coin-50 { color: #333; }
                .coin-25 { background-color: #ffd700; border-color: #DAA520; }
                .coin-10 { background-color: #ffd700; border-color: #DAA520; }
                .coin-5  { background-color: #cd7f32; border-color: #8b4513; color: white; }
            </style>

            <div class="mb-3">
                <p class="h4 text-success mb-3">Total: R$ ${formatMoney(totalAmount)}</p>
                <label class="form-label">Forma de Pagamento</label>
                <select id="payment-method-select" class="form-select form-select-lg mb-3">
                    <option value="DINHEIRO">DINHEIRO</option>
                    <option value="DÉBITO">DÉBITO</option>
                    <option value="CRÉDITO">CRÉDITO</option>
                    <option value="PIX">PIX</option>
                    <option value="CADERNETA">CADERNETA (Fiado)</option>
                </select>
                
                <!-- Caderneta Fields -->
                <div id="debt-due-date-container" class="d-none text-start bg-light p-3 rounded border">
                    <label class="form-label text-danger fw-bold">Data Prevista de Pagamento</label>
                    <input type="date" id="debt-due-date" class="form-control">
                </div>

                <!-- Cash Payment Fields -->
                <div id="cash-payment-container" class="text-start bg-light p-3 rounded border">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Valor Pago pelo Cliente (R$)</label>
                        <input type="number" id="cash-paid-amount" class="form-control form-control-lg" step="0.01" placeholder="0,00">
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Troco:</span>
                            <span id="change-amount-display" class="h4 mb-0 text-primary">R$ 0,00</span>
                        </div>
                        <div id="change-visuals" class="mt-2 d-flex flex-wrap justify-content-end">
                            <!-- Visual bills/coins will appear here -->
                        </div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirmar Pagamento',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const select = document.getElementById('payment-method-select');
            const dateContainer = document.getElementById('debt-due-date-container');
            const cashContainer = document.getElementById('cash-payment-container');
            const paidInput = document.getElementById('cash-paid-amount');
            const changeDisplay = document.getElementById('change-amount-display');
            const visualsContainer = document.getElementById('change-visuals');

            // Helper to calculate visuals
            const calculateVisuals = (change) => {
                visualsContainer.innerHTML = '';
                if (change <= 0) return;

                const bills = [200, 100, 50, 20, 10, 5, 2];
                const coins = [1.00, 0.50, 0.25, 0.10, 0.05];
                
                let remaining = change;
                let html = '';

                // Bills
                bills.forEach(bill => {
                    const count = Math.floor(remaining / bill);
                    if (count > 0) {
                        for(let i=0; i<count; i++) {
                            html += `<div class="money-bill bill-${bill}">R$ ${bill}</div>`;
                        }
                        remaining = parseFloat((remaining % bill).toFixed(2));
                    }
                });

                // Coins
                coins.forEach(coin => {
                    // Slight tolerance for float precision
                    const count = Math.floor((remaining + 0.001) / coin);
                    if (count > 0) {
                        for(let i=0; i<count; i++) {
                            const coinClass = coin >= 1 ? '1' : (coin * 100).toString();
                            const label = coin >= 1 ? 'R$ 1' : (coin * 100);
                            html += `<div class="money-coin coin-${coinClass}">${label}</div>`;
                        }
                        remaining = parseFloat((remaining % coin).toFixed(2));
                    }
                });
                
                visualsContainer.innerHTML = html;
            };

            const updateVisibility = () => {
                const method = select.value;
                if (method === 'CADERNETA') {
                    dateContainer.classList.remove('d-none');
                    cashContainer.classList.add('d-none');
                    // Default date logic
                    const input = document.getElementById('debt-due-date');
                    if (input && !input.value) {
                        const date = new Date();
                        date.setDate(date.getDate() + 30);
                        input.value = date.toISOString().split('T')[0];
                    }
                } else if (method === 'DINHEIRO') {
                    dateContainer.classList.add('d-none');
                    cashContainer.classList.remove('d-none');
                    setTimeout(() => paidInput.focus(), 100);
                } else {
                    dateContainer.classList.add('d-none');
                    cashContainer.classList.add('d-none');
                }
            };

            if (select) {
                select.addEventListener('change', updateVisibility);
                // Trigger initial state
                updateVisibility();
            }

            if (paidInput) {
                paidInput.addEventListener('input', function() {
                    const paid = parseFloat(this.value) || 0;
                    const change = paid - totalAmount;
                    
                    if (change < 0) {
                        changeDisplay.textContent = 'Falta R$ ' + formatMoney(Math.abs(change));
                        changeDisplay.classList.remove('text-primary');
                        changeDisplay.classList.add('text-danger');
                        visualsContainer.innerHTML = '';
                    } else {
                        changeDisplay.textContent = 'R$ ' + formatMoney(change);
                        changeDisplay.classList.remove('text-danger');
                        changeDisplay.classList.add('text-primary');
                        calculateVisuals(change);
                    }
                });
            }
        },
        preConfirm: () => {
            const method = document.getElementById('payment-method-select').value;
            const dueDate = document.getElementById('debt-due-date').value;
            const paidInput = document.getElementById('cash-paid-amount');
            
            let paidAmount = totalAmount;
            let changeAmount = 0;

            if (method === 'CADERNETA') {
                if (!dueDate) {
                    Swal.showValidationMessage('Informe a data prevista para pagamento da Caderneta');
                    return false;
                }
            } else if (method === 'DINHEIRO') {
                const val = parseFloat(paidInput.value);
                if (!val || val < totalAmount) {
                    Swal.showValidationMessage('Valor pago deve ser maior ou igual ao total!');
                    return false;
                }
                paidAmount = val;
                changeAmount = val - totalAmount;
            }
            
            return { method, dueDate, paidAmount, changeAmount };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { method, dueDate, paidAmount, changeAmount } = result.value;
            
            Swal.fire({
                title: 'Processando...',
                didOpen: () => Swal.showLoading()
            });

            axios.post((window.API_URL || 'api.php') + '?action=close_order', {
                order_id: orderId,
                payment_method: method,
                paid_amount: paidAmount,
                change_amount: changeAmount,
                debt_due_date: method === 'CADERNETA' ? dueDate : null
            }).then(res => {
                if (res.data.success) {
                    let msg = 'Mesa fechada com sucesso.';
                    if (changeAmount > 0) {
                        msg += `<br><b>Troco: R$ ${formatMoney(changeAmount)}</b>`;
                    }
                    Swal.fire('Sucesso!', msg, 'success');
                    fetchTablesStatus();
                } else {
                    Swal.fire('Erro', res.data.message || 'Erro ao fechar mesa.', 'error');
                }
            }).catch(err => {
                Swal.fire('Erro', 'Erro de conexão.', 'error');
            });
        }
    });
}

function deleteOrderConfirm(orderId) {
    Swal.fire({
        title: 'Excluir Comanda?',
        text: "Essa ação não pode ser desfeita! Os itens voltarão ao estoque.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post((window.API_URL || 'api.php') + '?action=delete_order', {
                order_id: orderId
            }).then(res => {
                if (res.data.success) {
                    Swal.fire('Excluído!', 'A comanda foi excluída.', 'success');
                    fetchTablesStatus();
                } else {
                    Swal.fire('Erro', res.data.message || 'Erro ao excluir.', 'error');
                }
            });
        }
    });
}

// --- EDIT ORDER LOGIC (Re-implemented) ---

let currentEditOrderItems = [];

function openEditOrder(orderId, customerName, tableNum) {
    document.getElementById('edit-open-order-id').value = orderId;
    document.getElementById('edit-open-table-num').textContent = tableNum;
    document.getElementById('edit-open-customer').value = customerName;
    
    const list = document.getElementById('edit-open-items-list');
    list.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Carregando itens...</div>';
    
    const modal = new bootstrap.Modal(document.getElementById('editOpenOrderModal'));
    modal.show();
    
    loadEditOrderItems(orderId);
}

function loadEditOrderItems(orderId) {
    axios.get((window.API_URL || 'api.php') + '?action=get_table_items&order_id=' + orderId)
        .then(res => {
            currentEditOrderItems = res.data || [];
            renderEditOrderItems();
        })
        .catch(err => {
            document.getElementById('edit-open-items-list').innerHTML = '<p class="text-danger text-center">Erro ao carregar itens.</p>';
        });
}

function renderEditOrderItems() {
    const list = document.getElementById('edit-open-items-list');
    if (currentEditOrderItems.length === 0) {
        list.innerHTML = '<p class="text-muted text-center py-3">Nenhum item neste pedido.</p>';
        return;
    }
    
    let html = '';
    currentEditOrderItems.forEach(item => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">${item.name}</div>
                    <small class="text-muted">R$ ${formatMoney(item.price)} un.</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-secondary rounded-pill me-3">${item.quantity}x</span>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteOrderItem(${item.id})" title="Remover Item">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    list.innerHTML = html;
}

function deleteOrderItem(itemId) {
    Swal.fire({
        title: 'Remover Item?',
        text: 'O item voltará para o estoque.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sim, remover'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post((window.API_URL || 'api.php') + '?action=delete_order_item', {
                item_id: itemId
            }).then(res => {
                if (res.data.success) {
                    // Reload items
                    const orderId = document.getElementById('edit-open-order-id').value;
                    loadEditOrderItems(orderId);
                    fetchTablesStatus(); // Update dashboard totals
                } else {
                    Swal.fire('Erro', res.data.message, 'error');
                }
            });
        }
    });
}

function updateOpenOrderCustomer() {
    const orderId = document.getElementById('edit-open-order-id').value;
    const name = document.getElementById('edit-open-customer').value;
    
    axios.post((window.API_URL || 'api.php') + '?action=update_order_customer', {
        order_id: orderId,
        customer_name: name
    }).then(res => {
        if (res.data.success) {
            Swal.fire('Sucesso', 'Nome atualizado.', 'success');
            fetchTablesStatus();
        } else {
            Swal.fire('Erro', 'Erro ao atualizar nome.', 'error');
        }
    });
}


// --- Mobile & Scanner Features (PRESERVED) ---

let mobileScanner = null;

function initMobileFeatures() {
    console.log('Iniciando recursos mobile...');
    
    if (typeof bootstrap === 'undefined') console.warn('Bootstrap não carregado.');
    if (typeof axios === 'undefined') console.warn('Axios não carregado.');
}

document.addEventListener('click', function(e) {
    const btnConnect = e.target.closest('#btn-connect-mobile');
    if (btnConnect) {
        e.preventDefault();
        openConnectMobileModal();
    }

    const btnScan = e.target.closest('#btn-mobile-scan');
    if (btnScan) {
        e.preventDefault();
        openMobileScanModal();
    }
});

document.addEventListener('hidden.bs.modal', function (event) {
    if (event.target.id === 'mobileScanModal') {
        if (mobileScanner) {
            mobileScanner.clear().catch(err => console.error(err));
            mobileScanner = null;
        }
    }
});

function openConnectMobileModal() {
    const modalEl = document.getElementById('connectMobileModal');
    if (!modalEl) return;
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    const qrContainer = document.getElementById('qrcode');
    const urlDisplay = document.getElementById('mobile-url-display');
    
    if (qrContainer) qrContainer.innerHTML = ''; 
    if (urlDisplay) urlDisplay.textContent = 'Obtendo IP...';

    const apiUrl = (window.API_URL || 'api.php') + '?action=get_server_ip';

    axios.get(apiUrl).then(res => {
        const ip = res.data.ip || window.location.hostname;
        const port = window.location.port ? ':' + window.location.port : '';
        
        let mobileUrl = '';
        if (window.location.protocol === 'file:') {
            mobileUrl = 'file://mobile.html';
        } else {
            let baseUrl = window.location.protocol + '//' + ip + port;
            let currentPath = window.location.pathname;
            
            if (currentPath.includes('/dashboard') || currentPath.includes('/index.php')) {
                mobileUrl = baseUrl + currentPath.replace(/\/dashboard|\/index\.php/, '/mobile');
            } else if (currentPath.endsWith('/')) {
                mobileUrl = baseUrl + currentPath + 'mobile';
            } else {
                let parts = currentPath.split('/');
                parts.pop();
                mobileUrl = baseUrl + parts.join('/') + '/mobile';
            }
        }
        
        if (qrContainer && typeof QRCode !== 'undefined') {
            new QRCode(qrContainer, { text: mobileUrl, width: 128, height: 128 });
        }
        
        if (urlDisplay) {
            urlDisplay.innerHTML = `<a href="${mobileUrl}" target="_blank">${mobileUrl}</a>`;
        }
    }).catch(err => {
        if (urlDisplay) urlDisplay.textContent = 'Erro ao obter IP.';
    });
}

function openMobileScanModal() {
    const modalEl = document.getElementById('mobileScanModal');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    setTimeout(() => {
        if (!mobileScanner && typeof Html5QrcodeScanner !== 'undefined') {
            try {
                mobileScanner = new Html5QrcodeScanner("mobile-reader", { fps: 10, qrbox: 250 }, false);
                mobileScanner.render(onScanSuccess, onScanFailure);
            } catch (e) {
                document.getElementById('mobile-reader').innerHTML = '<p class="text-white text-center p-3">Erro ao iniciar câmera.</p>';
            }
        }
    }, 500);
}

function onScanSuccess(decodedText, decodedResult) {
    if (mobileScanner) {
        mobileScanner.clear().then(() => {
            mobileScanner = null;
            const modalEl = document.getElementById('mobileScanModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            sendPendingNote(decodedText);
        });
    }
}

function onScanFailure(error) {}

function sendPendingNote(nfceUrl) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ title: 'Processando...', didOpen: () => Swal.showLoading() });
    }

    axios.post((window.API_URL || 'api.php') + '?action=store_pending_note', { nfce_url: nfceUrl })
    .then(res => {
        if (res.data.success) {
            Swal.fire({ icon: 'success', title: 'Nota Enviada!', timer: 2000, showConfirmButton: false });
        } else {
            Swal.fire('Erro', res.data.message || 'Erro ao enviar.', 'error');
        }
    })
    .catch(err => Swal.fire('Erro', 'Erro de conexão.', 'error'));
}

// --- INITIALIZATION ---

document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
    initMobileFeatures();
});
