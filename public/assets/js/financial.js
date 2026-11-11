
// Financial Dashboard Logic
// Author: Assistant (Trae IDE)

// Chart Instances (to destroy/update)
let charts = {
    daily: null,
    revenue: null,
    payment: null,
    payer: null,
    category: null
};

// State
let currentPeriod = 'daily';
let currentFinancialData = null; // Store data for edit access

document.addEventListener('DOMContentLoaded', function() {
    // Initial Load
    applyFinancialFilter('daily');

    // Add Listeners for Revenue Button (Support for both IDs found in code)
    const btnAddRev = document.getElementById('btn-add-revenue');
    if (btnAddRev) {
        btnAddRev.addEventListener('click', saveRevenue);
    }

    // Add Listeners for Expense Button
    const btnAddExp = document.getElementById('btn-add-expense');
    if (btnAddExp) {
        btnAddExp.addEventListener('click', saveExpense);
    }

    setupAutocomplete();
});

function applyFinancialFilter(period) {
    currentPeriod = period;
    
    // Update active button state
    document.querySelectorAll('.btn-filter-period').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.period === period) btn.classList.add('active');
    });

    const dates = calculateDates(period);
    loadFinancialData(dates.start, dates.end);
}

function calculateDates(period) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    // Set end to end of today
    end.setHours(23, 59, 59, 999);

    switch(period) {
        case 'daily':
            start.setHours(0, 0, 0, 0);
            break;
        case 'weekly':
            // Start of current week (Sunday) or last 7 days? Let's use last 7 days for better utility
            start.setDate(today.getDate() - 7);
            start.setHours(0, 0, 0, 0);
            break;
        case 'biweekly':
            start.setDate(today.getDate() - 15);
            start.setHours(0, 0, 0, 0);
            break;
        case 'monthly':
            start.setDate(1); // Start of month
            start.setHours(0, 0, 0, 0);
            break;
        case 'quarterly':
            start.setMonth(today.getMonth() - 3);
            start.setHours(0, 0, 0, 0);
            break;
        case 'semiannual':
            start.setMonth(today.getMonth() - 6);
            start.setHours(0, 0, 0, 0);
            break;
        case 'annual':
            start.setFullYear(today.getFullYear() - 1);
            start.setHours(0, 0, 0, 0);
            break;
    }

    return {
        start: formatDateForApi(start),
        end: formatDateForApi(end)
    };
}

function formatDateForApi(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function loadFinancialData(startDate, endDate) {
    // Show loading state if needed
    
    axios.get((window.API_URL || 'api.php') + `?action=get_financial_summary&start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (response.data) {
                currentFinancialData = response.data; // Save for edits
                renderDashboard(response.data);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados financeiros:', error);
            Swal.fire('Erro', 'Falha ao carregar dados financeiros.', 'error');
        });
}

function renderDashboard(data) {
    // 1. Update KPIs
    safeText('dash-revenue', formatMoney(data.revenue || 0));
    safeText('dash-receivables', formatMoney(data.receivables_total || 0));
    safeText('dash-expenses', formatMoney(data.total_expenses || 0));
    
    // Profit Calculation
    const revenue = parseFloat(data.revenue) || 0;
    const expenses = parseFloat(data.total_expenses) || 0;
    const profit = revenue - expenses;
    const margin = revenue > 0 ? ((profit / revenue) * 100).toFixed(1) : 0;

    safeText('dash-profit', formatMoney(profit));
    safeText('dash-margin', margin);

    // Receivables Date
    if (data.receivables_upcoming && data.receivables_upcoming.length > 0) {
        const next = data.receivables_upcoming[0];
        const dateObj = new Date(next.date);
        safeText('dash-receivables-date', `Próx: ${dateObj.toLocaleDateString('pt-BR')} (${formatMoney(next.total)})`);
    } else {
        safeText('dash-receivables-date', 'Sem previsões');
    }

    // 2. Render Charts
    renderCharts(data);

    // 3. Render Lists
    renderTransactionList(data);
    renderRevenueList(data.revenue_list);
    renderExpenseList(data.expense_list);
}

function renderCharts(data) {
    // --- Daily Summary Chart ---
    const dailyLabels = [];
    const dailyIncome = [];
    const dailyExpense = [];

    // Merge daily data (orders + manual)
    const dailyMap = {};
    
    (data.daily_orders || []).forEach(d => {
        if (!dailyMap[d.date]) dailyMap[d.date] = { income: 0, expense: 0 };
        dailyMap[d.date].income += parseFloat(d.total);
    });
    
    (data.daily_manual_revenues || []).forEach(d => {
        if (!dailyMap[d.date]) dailyMap[d.date] = { income: 0, expense: 0 };
        dailyMap[d.date].income += parseFloat(d.total);
    });

    (data.daily_expenses || []).forEach(d => {
        if (!dailyMap[d.date]) dailyMap[d.date] = { income: 0, expense: 0 };
        dailyMap[d.date].expense += parseFloat(d.total);
    });

    // Sort dates
    Object.keys(dailyMap).sort().forEach(date => {
        // Fix: Parse YYYY-MM-DD explicitly to avoid timezone shift (UTC to Local)
        // new Date("2024-02-02") is UTC, which becomes previous day in UTC-3
        const parts = date.split('-');
        // Create date as Local Time: year, monthIndex (0-11), day
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        
        dailyLabels.push(d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        dailyIncome.push(dailyMap[date].income);
        dailyExpense.push(dailyMap[date].expense);
    });

    createChart('dailySummaryChart', 'daily', 'bar', {
        labels: dailyLabels,
        datasets: [
            {
                label: 'Receitas',
                data: dailyIncome,
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: '#198754',
                borderWidth: 1
            },
            {
                label: 'Despesas',
                data: dailyExpense,
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: '#dc3545',
                borderWidth: 1
            }
        ]
    });

    // --- Revenue vs Expense Pie ---
    const totalRev = parseFloat(data.revenue) || 0;
    const totalExp = parseFloat(data.total_expenses) || 0;
    
    createChart('revenueChart', 'revenue', 'doughnut', {
        labels: ['Receitas', 'Despesas'],
        datasets: [{
            data: [totalRev, totalExp],
            backgroundColor: ['#198754', '#dc3545']
        }]
    });

    // --- Payment Methods ---
    const methodLabels = [];
    const methodValues = [];
    const methodColors = [];
    
    // Define standard colors for known methods
    const colorMap = {
        'DINHEIRO': '#20c997',
        'PIX': '#0dcaf0',
        'CRÉDITO': '#0d6efd',
        'DÉBITO': '#6610f2',
        'CADERNETA': '#ffc107'
    };

    if (data.revenue_by_method) {
        Object.keys(data.revenue_by_method).forEach(method => {
            methodLabels.push(method);
            methodValues.push(data.revenue_by_method[method]);
            methodColors.push(colorMap[method] || '#6c757d');
        });
    }

    createChart('paymentMethodChart', 'payment', 'pie', {
        labels: methodLabels,
        datasets: [{
            data: methodValues,
            backgroundColor: methodColors
        }]
    });

    // --- Expense by Payer ---
    const payerLabels = [];
    const payerValues = [];
    if (data.expenses) {
        Object.keys(data.expenses).forEach(payer => {
            payerLabels.push(payer.toUpperCase());
            payerValues.push(data.expenses[payer]);
        });
    }

    createChart('expensePayerChart', 'payer', 'pie', {
        labels: payerLabels,
        datasets: [{
            data: payerValues,
            backgroundColor: ['#6c757d', '#fd7e14', '#d63384'] // Grey, Orange, Pink
        }]
    });

    // --- Sales by Category ---
    const catLabels = [];
    const catValues = [];
    
    if (data.sales_by_category) {
        data.sales_by_category.forEach(item => {
            if (item.category === 'PORCAO') return;
            catLabels.push(item.category);
            catValues.push(item.total);
        });
    }

    createChart('salesByCategoryChart', 'category', 'doughnut', {
        labels: catLabels,
        datasets: [{
            data: catValues,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
            ]
        }]
    });
}

function createChart(canvasId, chartKey, type, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    if (charts[chartKey]) {
        charts[chartKey].destroy();
    }

    charts[chartKey] = new Chart(ctx, {
        type: type,
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12 }
                }
            }
        }
    });
}

function renderTransactionList(data) {
    const tbody = document.getElementById('dash-transactions-body');
    if (!tbody) return;
    
    // Combine recent items from lists
    let all = [];
    if (data.revenue_list) all = all.concat(data.revenue_list.map(i => ({...i, type: 'revenue'})));
    if (data.expense_list) all = all.concat(data.expense_list.map(i => ({...i, type: 'expense'})));
    if (data.order_list) all = all.concat(data.order_list.map(i => ({...i, type: 'revenue', description: `Pedido #${i.id}`})));

    // Sort by date desc
    all.sort((a, b) => {
        const da = a.revenue_date || a.expense_date || a.created_at;
        const db = b.revenue_date || b.expense_date || b.created_at;
        return new Date(db) - new Date(da);
    });

    // Take top 10
    const recent = all.slice(0, 10);
    
    let html = '';
    recent.forEach(item => {
        const dateVal = item.revenue_date || item.expense_date || item.created_at;
        const date = new Date(dateVal).toLocaleDateString('pt-BR');
        const isRev = item.type === 'revenue';
        const color = isRev ? 'text-success' : 'text-danger';
        const icon = isRev ? 'fa-arrow-up' : 'fa-arrow-down';
        const entity = item.source || item.supplier || item.customer_name || 'Diversos';
        
        html += `
            <tr>
                <td class="ps-4 text-muted small">${date}</td>
                <td><span class="fw-bold text-dark">${entity}</span></td>
                <td class="text-muted small">${item.description || '-'}</td>
                <td class="text-end pe-4 fw-bold ${color}">
                    <i class="fas ${icon} me-1 small"></i> ${formatMoney(item.amount || item.total)}
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function renderRevenueList(list) {
    const tbody = document.getElementById('revenue-list-body');
    if (!tbody) return;
    
    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Nenhuma receita no período</td></tr>';
        return;
    }

    let html = '';
    list.forEach(item => {
        html += `
            <tr>
                <td class="ps-3">${new Date(item.revenue_date).toLocaleDateString('pt-BR')}</td>
                <td class="fw-bold">${item.source || '-'}</td>
                <td class="small text-muted">${item.description || '-'}</td>
                <td class="text-end fw-bold text-success">${formatMoney(item.amount)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editRevenue(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction('revenue', ${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function renderExpenseList(list) {
    const tbody = document.getElementById('expense-list-body');
    if (!tbody) return;

    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Nenhuma despesa no período</td></tr>';
        return;
    }

    let html = '';
    list.forEach(item => {
        html += `
            <tr>
                <td class="ps-3">${new Date(item.expense_date).toLocaleDateString('pt-BR')}</td>
                <td class="fw-bold">${item.supplier || '-'}</td>
                <td class="small text-muted">${item.description || '-'}</td>
                <td class="text-end fw-bold text-danger">${formatMoney(item.amount)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editExpense(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction('expense', ${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// --- Actions ---

function saveRevenue() {
    // Check which ID set is being used
    const dateEl = document.getElementById('rev-date') || document.getElementById('revenue-date');
    const methodEl = document.getElementById('rev-payment-method') || document.getElementById('revenue-payment-method');
    const sourceEl = document.getElementById('rev-source') || document.getElementById('revenue-source');
    const descEl = document.getElementById('rev-description') || document.getElementById('revenue-description');
    const recvEl = document.getElementById('rev-received-by') || document.getElementById('revenue-received-by');
    const amountEl = document.getElementById('rev-amount') || document.getElementById('revenue-amount');

    if (!amountEl || !amountEl.value) {
        Swal.fire('Atenção', 'Informe o valor da receita', 'warning');
        return;
    }

    const data = {
        date: dateEl ? dateEl.value : null,
        payment_method: methodEl ? methodEl.value : 'DINHEIRO',
        source: sourceEl ? sourceEl.value : '',
        description: descEl ? descEl.value : '',
        received_by: recvEl ? recvEl.value : 'CAIXA',
        amount: amountEl.value
    };

    axios.post((window.API_URL || 'api.php') + '?action=add_revenue', data)
        .then(response => {
            if (response.data.success) {
                Swal.fire('Sucesso', 'Receita salva!', 'success');
                // Clear fields
                if (amountEl) amountEl.value = '';
                if (sourceEl) sourceEl.value = '';
                if (descEl) descEl.value = '';
                // Reload
                applyFinancialFilter(currentPeriod);
            } else {
                Swal.fire('Erro', response.data.message || 'Erro ao salvar', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Erro', 'Erro de conexão', 'error');
        });
}

function saveExpense() {
    const dateEl = document.getElementById('note-date');
    const methodEl = document.getElementById('note-payment-method');
    const supplierEl = document.getElementById('note-supplier');
    const descEl = document.getElementById('note-description');
    const paidByEl = document.getElementById('note-paid-by');
    const amountEl = document.getElementById('note-amount');

    if (!amountEl || !amountEl.value) {
        Swal.fire('Atenção', 'Informe o valor da despesa', 'warning');
        return;
    }

    const data = {
        expense_date: dateEl ? dateEl.value : null,
        payment_method: methodEl ? methodEl.value : 'DINHEIRO',
        supplier: supplierEl ? supplierEl.value : '',
        description: descEl ? descEl.value : '',
        paid_by: paidByEl ? paidByEl.value : 'caixa',
        amount: amountEl.value
    };

    axios.post((window.API_URL || 'api.php') + '?action=add_expense', data)
        .then(response => {
            if (response.data.success) {
                Swal.fire('Sucesso', 'Despesa salva!', 'success');
                if (amountEl) amountEl.value = '';
                if (supplierEl) supplierEl.value = '';
                if (descEl) descEl.value = '';
                applyFinancialFilter(currentPeriod);
            } else {
                Swal.fire('Erro', response.data.message || 'Erro ao salvar', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Erro', 'Erro de conexão', 'error');
        });
}

// Edit Functions
function editRevenue(id) {
    if (!currentFinancialData || !currentFinancialData.revenue_list) return;
    
    const item = currentFinancialData.revenue_list.find(i => i.id === id);
    if (!item) return;

    document.getElementById('edit-rev-id').value = item.id;
    document.getElementById('edit-rev-date').value = item.revenue_date;
    document.getElementById('edit-rev-method').value = item.payment_method;
    document.getElementById('edit-rev-source').value = item.source || '';
    document.getElementById('edit-rev-description').value = item.description || '';
    document.getElementById('edit-rev-amount').value = item.amount;

    const modal = new bootstrap.Modal(document.getElementById('editRevenueModal'));
    modal.show();
}

function updateRevenue() {
    const id = document.getElementById('edit-rev-id').value;
    const date = document.getElementById('edit-rev-date').value;
    const method = document.getElementById('edit-rev-method').value;
    const source = document.getElementById('edit-rev-source').value;
    const desc = document.getElementById('edit-rev-description').value;
    const amount = document.getElementById('edit-rev-amount').value;

    const data = {
        id: id,
        date: date,
        payment_method: method,
        source: source,
        description: desc,
        amount: amount
    };

    axios.post((window.API_URL || 'api.php') + '?action=update_revenue', data)
        .then(response => {
            if (response.data.success) {
                Swal.fire('Sucesso', 'Receita atualizada!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editRevenueModal')).hide();
                applyFinancialFilter(currentPeriod);
            } else {
                Swal.fire('Erro', response.data.message || 'Erro ao atualizar', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Erro', 'Erro de conexão', 'error');
        });
}

function editExpense(id) {
    if (!currentFinancialData || !currentFinancialData.expense_list) return;
    
    const item = currentFinancialData.expense_list.find(i => i.id === id);
    if (!item) return;

    document.getElementById('edit-exp-id').value = item.id;
    document.getElementById('edit-exp-date').value = item.expense_date;
    document.getElementById('edit-exp-method').value = item.payment_method;
    document.getElementById('edit-exp-supplier').value = item.supplier || '';
    document.getElementById('edit-exp-description').value = item.description || '';
    document.getElementById('edit-exp-paid-by').value = (item.responsible || 'caixa').toLowerCase();
    document.getElementById('edit-exp-amount').value = item.amount;

    const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
    modal.show();
}

function updateExpense() {
    const id = document.getElementById('edit-exp-id').value;
    const date = document.getElementById('edit-exp-date').value;
    const method = document.getElementById('edit-exp-method').value;
    const supplier = document.getElementById('edit-exp-supplier').value;
    const desc = document.getElementById('edit-exp-description').value;
    const paidBy = document.getElementById('edit-exp-paid-by').value;
    const amount = document.getElementById('edit-exp-amount').value;

    const data = {
        id: id,
        date: date,
        payment_method: method,
        supplier: supplier,
        description: desc,
        paid_by: paidBy,
        amount: amount
    };

    axios.post((window.API_URL || 'api.php') + '?action=update_expense', data)
        .then(response => {
            if (response.data.success) {
                Swal.fire('Sucesso', 'Despesa atualizada!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editExpenseModal')).hide();
                applyFinancialFilter(currentPeriod);
            } else {
                Swal.fire('Erro', response.data.message || 'Erro ao atualizar', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Erro', 'Erro de conexão', 'error');
        });
}

function deleteTransaction(type, id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "Essa ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!'
    }).then((result) => {
        if (result.isConfirmed) {
            const action = type === 'revenue' ? 'delete_revenue' : 'delete_expense';
            axios.post((window.API_URL || 'api.php') + `?action=${action}`, { id: id })
                .then(response => {
                    if (response.data.success) {
                        Swal.fire('Excluído!', 'Registro removido.', 'success');
                        applyFinancialFilter(currentPeriod);
                    } else {
                        Swal.fire('Erro', 'Não foi possível excluir.', 'error');
                    }
                });
        }
    });
}

// Utility
function safeText(id, text) {
    const el = document.getElementById(id);
    if (el) el.innerText = text;
}

function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function setupAutocomplete() {
    // Helper to debounce API calls
    const debounce = (func, wait) => {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    };

    // Generic Autocomplete Function
    const initAutocomplete = (inputId, suggestionsId, action) => {
        const input = document.getElementById(inputId);
        const suggestions = document.getElementById(suggestionsId);
        
        if (!input || !suggestions) return;

        // Hide suggestions on click outside
        document.addEventListener('click', function(e) {
            if (e.target !== input && e.target !== suggestions) {
                suggestions.style.display = 'none';
            }
        });

        // Handle input typing
        input.addEventListener('input', debounce(function() {
            const query = this.value;
            if (query.length < 1) {
                suggestions.style.display = 'none';
                return;
            }

            axios.get((window.API_URL || 'api.php') + `?action=${action}&query=${encodeURIComponent(query)}`)
                .then(response => {
                    const items = response.data;
                    suggestions.innerHTML = '';
                    
                    if (!items || items.length === 0) {
                        suggestions.style.display = 'none';
                        return;
                    }

                    items.forEach(item => {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action small py-2';
                        a.textContent = item.name || item; // Handle object or string
                        a.style.cursor = 'pointer';
                        
                        a.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            input.value = item.name || item;
                            suggestions.style.display = 'none';
                        };
                        
                        suggestions.appendChild(a);
                    });
                    
                    suggestions.style.display = 'block';
                })
                .catch(err => console.error('Autocomplete error:', err));
        }, 300));
        
        // Handle focus (optional: show recent or similar? No, keep simple)
    };

    // Setup for Supplier (Expense Modal)
    initAutocomplete('note-supplier', 'supplier-suggestions', 'search_suppliers');
    
    // Setup for Description (Expense Modal)
    initAutocomplete('note-description', 'description-suggestions', 'search_descriptions');

    // Also Setup for Revenue Modal (Source and Description)
    initAutocomplete('revenue-source', 'source-suggestions', 'search_suppliers'); // Reusing supplier search for source
    initAutocomplete('revenue-description', 'revenue-description-suggestions', 'search_descriptions');
}
