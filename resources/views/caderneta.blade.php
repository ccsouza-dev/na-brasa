@extends('layouts.app')

@section('title', 'Caderneta (Fiado) - Espetinhos da Jaque')
@section('page-title', 'Gestão de Caderneta')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-book-open me-2 text-warning"></i> Contas em Aberto (Fiado)</h5>
            <button class="btn btn-sm btn-primary" onclick="loadDebts()">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Previsão</th>
                            <th>Cliente</th>
                            <th>Valor Total</th>
                            <th>Itens</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="debts-list">
                        <tr><td colspan="6" class="text-center py-4">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group" id="details-list"></ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', loadDebts);

    function formatMoney(value) {
        return parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
    }

    function formatDateOnly(dateStr) {
        if (!dateStr) return '<span class="text-muted">-</span>';
        const parts = dateStr.split('-');
        const d = new Date(parts[0], parts[1] - 1, parts[2]); 
        return d.toLocaleDateString('pt-BR');
    }

    // Carrega dados e AGRUPA por cliente
    function loadDebts() {
        axios.get('api.php?action=get_debts')
            .then(res => {
                const rawDebts = res.data;
                const tbody = document.getElementById('debts-list');
                tbody.innerHTML = '';

                if (rawDebts.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma conta em aberto.</td></tr>';
                    return;
                }

                // Agrupamento por Cliente
                const clients = {};

                rawDebts.forEach(debt => {
                    const nameRaw = debt.customer_name ? debt.customer_name.trim() : 'Sem Identificação';
                    const nameKey = nameRaw.toUpperCase();

                    if (!clients[nameKey]) {
                        clients[nameKey] = {
                            name: nameRaw,
                            total: 0,
                            orders: [], // Lista de IDs de pedidos
                            items: [], // Lista consolidada de itens
                            due_dates: [],
                            created_ats: []
                        };
                    }

                    // Acumula total
                    clients[nameKey].total += parseFloat(debt.total);
                    clients[nameKey].orders.push(debt.id);
                    clients[nameKey].created_ats.push(debt.created_at);
                    if (debt.debt_due_date) {
                        clients[nameKey].due_dates.push(debt.debt_due_date);
                    }

                    // Injeta a data do pedido em cada item e adiciona à lista consolidada
                    if (debt.items && debt.items.length > 0) {
                        debt.items.forEach(item => {
                            // Cria uma cópia ou modifica o item para ter a data
                            item.purchase_date = debt.created_at; 
                            clients[nameKey].items.push(item);
                        });
                    }
                });

                // Renderiza cada cliente agrupado
                Object.values(clients).forEach(client => {
                    const tr = document.createElement('tr');
                    
                    // Determina a data de vencimento mais próxima (mais crítica)
                    let displayDate = '-';
                    let dateClass = '';
                    
                    if (client.due_dates.length > 0) {
                        // Ordena datas string YYYY-MM-DD
                        client.due_dates.sort(); 
                        const earliestDue = client.due_dates[0]; // A primeira que vence
                        displayDate = formatDateOnly(earliestDue);

                        const today = new Date();
                        today.setHours(0,0,0,0);
                        const parts = earliestDue.split('-');
                        const due = new Date(parts[0], parts[1] - 1, parts[2]);

                        if (due < today) {
                            dateClass = 'text-danger fw-bold'; 
                            displayDate += ' <i class="fas fa-exclamation-circle small" title="Atrasado"></i>';
                        } else if (due.getTime() === today.getTime()) {
                            dateClass = 'text-warning fw-bold';
                            displayDate += ' <span class="badge bg-warning text-dark ms-1">Hoje</span>';
                        }
                    }

                    // Prepara dados para os botões
                    // Escapar aspas duplas para o JSON no HTML attribute
                    const itemsJson = JSON.stringify(client.items).replace(/"/g, '&quot;');
                    const orderIdsJson = JSON.stringify(client.orders).replace(/"/g, '&quot;');
                    
                    // Data da última compra (ou primeira?) - vamos mostrar a última
                    client.created_ats.sort();
                    const lastPurchase = formatDate(client.created_ats[client.created_ats.length - 1]);

                    tr.innerHTML = `
                        <td>${lastPurchase} <small class="text-muted d-block" style="font-size:0.7em">(Última compra)</small></td>
                        <td class="${dateClass}">${displayDate}</td>
                        <td class="fw-bold">${client.name}</td>
                        <td class="text-danger fw-bold">${formatMoney(client.total)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" onclick="showDetails('${itemsJson}', '${client.name}')">
                                Ver ${client.items.length} itens
                            </button>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-success btn-sm" onclick="settleClientDebt('${orderIdsJson}', '${client.name}', ${client.total})">
                                <i class="fas fa-check-circle"></i> Quitar Tudo
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(err => {
                console.error(err);
                document.getElementById('debts-list').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erro ao carregar dados.</td></tr>';
            });
    }

    function showDetails(itemsStr, clientName) {
        const items = JSON.parse(itemsStr);
        const list = document.getElementById('details-list');
        const modalTitle = document.querySelector('#detailsModal .modal-title');
        
        modalTitle.textContent = `Detalhes - ${clientName}`;
        list.innerHTML = '';

        // Agrupar itens por data de compra (apenas data YYYY-MM-DD)
        const byDate = {};
        items.forEach(item => {
            // item.purchase_date vem do formato SQL: 2026-01-10 11:16:55
            const dateOnly = item.purchase_date.split(' ')[0];
            if (!byDate[dateOnly]) {
                byDate[dateOnly] = [];
            }
            byDate[dateOnly].push(item);
        });

        // Ordenar as datas (mais recentes primeiro ou antigas? Antigas primeiro faz sentido histórico)
        const dates = Object.keys(byDate).sort(); // YYYY-MM-DD sort funciona

        dates.forEach(date => {
            // Cabeçalho da Data
            const parts = date.split('-');
            const displayDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
            
            list.innerHTML += `
                <li class="list-group-item bg-light fw-bold text-primary">
                    <i class="fas fa-calendar-alt me-1"></i> ${displayDate}
                </li>
            `;

            // Itens dessa data
            byDate[date].forEach(item => {
                list.innerHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center ps-4">
                        <div>
                            <span class="fw-bold text-dark">${item.quantity}x</span> ${item.product_name}
                        </div>
                        <span>${formatMoney(item.price * item.quantity)}</span>
                    </li>
                `;
            });
        });

        new bootstrap.Modal(document.getElementById('detailsModal')).show();
    }

    function settleClientDebt(orderIdsStr, name, total) {
        const orderIds = JSON.parse(orderIdsStr);
        
        Swal.fire({
            title: 'Quitar Caderneta Completa',
            html: `
                <p>Cliente: <strong>${name}</strong></p>
                <p>Total Devido: <strong class="text-danger">${formatMoney(total)}</strong></p>
                <p class="text-muted small">Serão baixados ${orderIds.length} pedidos.</p>
                <hr>
                <label class="form-label">Como o cliente pagou?</label>
                <select id="swal-method" class="form-select mb-3">
                    <option value="DINHEIRO">Dinheiro</option>
                    <option value="PIX">Pix</option>
                    <option value="CRÉDITO">Crédito</option>
                    <option value="DÉBITO">Débito</option>
                </select>
                <div class="form-check text-start">
                    <input class="form-check-input" type="checkbox" id="swal-revenue" checked>
                    <label class="form-check-label" for="swal-revenue">
                        Lançar entrada no Caixa de Hoje?
                    </label>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Confirmar Baixa',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                return {
                    method: document.getElementById('swal-method').value,
                    createRevenue: document.getElementById('swal-revenue').checked
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { method, createRevenue } = result.value;
                
                // Mostrar loading
                Swal.fire({
                    title: 'Processando...',
                    text: 'Dando baixa nos pedidos...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const promises = orderIds.map(id => {
                    return axios.post('api.php?action=settle_debt', {
                        order_id: id,
                        payment_method: method,
                        create_revenue: createRevenue 
                    });
                });

                Promise.all(promises)
                    .then(results => {
                        // Verifica se algum falhou
                        const errors = results.filter(r => !r.data.success);
                        if (errors.length > 0) {
                            Swal.fire('Atenção', `Alguns pedidos não puderam ser baixados. (${errors.length} erros)`, 'warning');
                        } else {
                            Swal.fire('Sucesso', 'Todos os pedidos foram quitados!', 'success');
                        }
                        loadDebts();
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Erro', 'Ocorreu um erro de conexão ao processar os pagamentos.', 'error');
                    });
            }
        });
    }
</script>
@endsection
