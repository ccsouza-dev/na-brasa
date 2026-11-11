@extends('layouts.app')

@section('title', 'Financeiro - Espetinhos da Jaque')
@section('body-class', 'bg-light')
@section('page-title', 'Financeiro')

@section('content')
<div class="d-flex financial-layout">
    <!-- Sidebar / Tabs -->
    <div class="bg-white border-end financial-sidebar">
        <div class="list-group list-group-flush pt-2">
            <button class="list-group-item list-group-item-action border-0 active fw-bold py-3 px-4 d-flex align-items-center" data-bs-toggle="pill" data-bs-target="#dashboard-view" id="btn-tab-dashboard">
                <i class="fas fa-th-large me-3 text-secondary"></i> Visão Geral
            </button>
            <button class="list-group-item list-group-item-action border-0 fw-bold py-3 px-4 d-flex align-items-center" data-bs-toggle="pill" data-bs-target="#revenues-view">
                <i class="fas fa-hand-holding-usd me-3 text-secondary"></i> Receitas
            </button>
            <button class="list-group-item list-group-item-action border-0 fw-bold py-3 px-4 d-flex align-items-center" data-bs-toggle="pill" data-bs-target="#expenses-view">
                <i class="fas fa-file-invoice-dollar me-3 text-secondary"></i> Despesas
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 financial-content">
        <!-- Toolbar: Filters & Actions (Global) -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <!-- Date Filters -->
            <div class="btn-group shadow-sm" role="group">
                <button class="btn btn-outline-secondary btn-sm btn-filter-period active" data-period="daily" onclick="applyFinancialFilter('daily')">Diário</button>
                <button class="btn btn-outline-secondary btn-sm btn-filter-period" data-period="weekly" onclick="applyFinancialFilter('weekly')">Semanal</button>
                <button class="btn btn-outline-secondary btn-sm btn-filter-period" data-period="biweekly" onclick="applyFinancialFilter('biweekly')">Quinzenal</button>
                <button class="btn btn-outline-secondary btn-sm btn-filter-period" data-period="monthly" onclick="applyFinancialFilter('monthly')">Mensal</button>
                <button class="btn btn-outline-secondary btn-sm btn-filter-period" data-period="quarterly" onclick="applyFinancialFilter('quarterly')">Trimestral</button>
                <button class="btn btn-outline-secondary btn-sm btn-filter-period" data-period="semiannual" onclick="applyFinancialFilter('semiannual')">Semestral</button>
                <button class="btn btn-outline-secondary btn-sm btn-filter-period" data-period="annual" onclick="applyFinancialFilter('annual')">Anual</button>
            </div>

            <!-- Quick Actions -->
            <div class="d-flex gap-2">
                <button class="btn btn-success btn-sm shadow-sm" onclick="document.querySelector('[data-bs-target=\'#revenues-view\']').click()"><i class="fas fa-plus me-1"></i> Receita</button>
                <button class="btn btn-dark btn-sm shadow-sm" onclick="document.querySelector('[data-bs-target=\'#expenses-view\']').click()"><i class="fas fa-minus me-1"></i> Despesa</button>
            </div>
        </div>

        <div class="tab-content" id="financialTabContent">
            
            <!-- DASHBOARD VIEW -->
            <div class="tab-pane fade show active" id="dashboard-view">

                <!-- KPI Cards -->
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
                    <div class="col">
                        <div class="card border-0 shadow h-100 card-hover overflow-hidden position-relative">
                            <div class="position-absolute top-0 start-0 h-100 bg-success" style="width: 5px;"></div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="text-muted mb-1 small text-uppercase fw-bold ls-1">Receita Total</h6>
                                        <h4 class="fw-bold text-dark mb-0 text-nowrap">R$ <span id="dash-revenue">0.00</span></h4>
                                    </div>
                                    <div class="bg-success-subtle text-success rounded-3 p-2">
                                        <i class="fas fa-arrow-up fa-lg"></i>
                                    </div>
                                </div>
                                <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> Período selecionado</small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card border-0 shadow h-100 card-hover overflow-hidden position-relative" style="cursor: pointer;" onclick="window.location.href='{{ route('caderneta') }}'" title="Clique para gerenciar a Caderneta">
                            <div class="position-absolute top-0 start-0 h-100 bg-warning" style="width: 5px;"></div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="text-muted mb-1 small text-uppercase fw-bold ls-1">A Receber</h6>
                                        <h4 class="fw-bold text-dark mb-0 text-nowrap">R$ <span id="dash-receivables">0.00</span></h4>
                                    </div>
                                    <div class="bg-warning-subtle text-warning rounded-3 p-2">
                                        <i class="fas fa-clock fa-lg"></i>
                                    </div>
                                </div>
                                <small class="text-muted fw-bold" id="dash-receivables-date" style="font-size: 0.75rem;">
                                    <i class="fas fa-calendar-alt me-1"></i> Aguardando...
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card border-0 shadow h-100 card-hover overflow-hidden position-relative">
                            <div class="position-absolute top-0 start-0 h-100 bg-danger" style="width: 5px;"></div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="text-muted mb-1 small text-uppercase fw-bold ls-1">Despesas</h6>
                                        <h4 class="fw-bold text-dark mb-0 text-nowrap">R$ <span id="dash-expenses">0.00</span></h4>
                                    </div>
                                    <div class="bg-danger-subtle text-danger rounded-3 p-2">
                                        <i class="fas fa-arrow-down fa-lg"></i>
                                    </div>
                                </div>
                                <small class="text-muted"><i class="fas fa-wallet me-1"></i> Total pago</small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card border-0 shadow h-100 card-hover overflow-hidden position-relative">
                            <div class="position-absolute top-0 start-0 h-100 bg-primary" style="width: 5px;"></div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="text-muted mb-1 small text-uppercase fw-bold ls-1">Lucro Líquido</h6>
                                        <h4 class="fw-bold text-dark mb-0 text-nowrap">R$ <span id="dash-profit">0.00</span></h4>
                                    </div>
                                    <div class="bg-primary-subtle text-primary rounded-3 p-2">
                                        <i class="fas fa-coins fa-lg"></i>
                                    </div>
                                </div>
                                <small class="text-success fw-bold"><i class="fas fa-chart-line me-1"></i> <span id="dash-margin">0</span>% Margem</small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card border-0 shadow h-100 card-hover bg-gradient-primary text-white position-relative overflow-hidden">
                            <div class="position-absolute top-0 end-0 opacity-10 p-3">
                                <i class="fas fa-piggy-bank fa-4x"></i>
                            </div>
                            <div class="card-body position-relative z-1">
                                <h6 class="text-white-50 mb-2 small text-uppercase fw-bold ls-1">Balanço</h6>
                                <h3 class="fw-bold mb-3">Resumo</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Summary Row -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-calendar-day me-2"></i>Resumo do Dia</h6>
                                <div class="col-auto">
                                    <select class="form-select form-select-sm" id="dailySummaryDate">
                                        <option value="">Carregando datas...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body d-flex justify-content-center">
                                <div style="height: 300px; width: 100%; max-width: 600px;">
                                    <canvas id="dailySummaryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow h-100">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-chart-bar me-2"></i>Fluxo de Caixa</h6>
                                <span class="badge bg-light text-muted border">Receitas vs Despesas</span>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow h-100">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-wallet me-2"></i>Formas de Pagamento</h6>
                            </div>
                            <div class="card-body position-relative d-flex align-items-center justify-content-center">
                                <canvas id="paymentMethodChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow h-100">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="fw-bold mb-0 text-danger"><i class="fas fa-chart-pie me-2"></i>Despesas por Pagador</h6>
                            </div>
                            <div class="card-body position-relative d-flex align-items-center justify-content-center">
                                <canvas id="expensePayerChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow h-100">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="fw-bold mb-0 text-success"><i class="fas fa-chart-pie me-2"></i>Vendas por Categoria</h6>
                            </div>
                            <div class="card-body position-relative d-flex align-items-center justify-content-center">
                                <canvas id="salesByCategoryChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions Row -->
                <div class="row g-4 mb-4">
                    <!-- Recent Transactions Table -->
                    <div class="col-12">
                        <div class="card border-0 shadow h-100">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-list-alt me-2"></i>Últimas Movimentações</h6>
                                <button class="btn btn-sm btn-outline-primary" id="btn-go-expenses">Ver Detalhes</button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Data</th>
                                                <th>Entidade</th>
                                                <th>Descrição</th>
                                                <th class="text-end pe-4">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dash-transactions-body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REVENUES VIEW -->
            <div class="tab-pane fade" id="revenues-view">
                <div class="row">
                    <div class="col-lg-5 mb-3">
                         <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white d-flex align-items-center">
                                <i class="fas fa-plus-circle me-2"></i> <h6 class="mb-0">Nova Receita</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Data</label>
                                        <input type="date" class="form-control" id="rev-date">
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Pagamento</label>
                                        <select class="form-select" id="rev-payment-method">
                                            <option value="DINHEIRO">DINHEIRO</option>
                                            <option value="PIX">PIX</option>
                                            <option value="CRÉDITO">CRÉDITO</option>
                                            <option value="DÉBITO">DÉBITO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Origem / Cliente</label>
                                    <input type="text" class="form-control" id="rev-source" placeholder="Ex: Venda Balcão, Ifood...">
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Descrição</label>
                                    <input type="text" class="form-control" id="rev-description" placeholder="Ex: Venda de Espetinhos">
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Quem Recebeu</label>
                                    <select class="form-select" id="rev-received-by">
                                        <option value="CAIXA">CAIXA</option>
                                        <option value="JAQUELINE">JAQUELINE</option>
                                        <option value="PALOMA">PALOMA</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Valor (R$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="rev-amount" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-success" onclick="saveRevenue()">
                                        <i class="fas fa-save me-2"></i> Salvar Receita
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="fw-bold mb-0 text-success"><i class="fas fa-list me-2"></i>Últimas Receitas</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Data</th>
                                                <th>Origem</th>
                                                <th>Descrição</th>
                                                <th class="text-end">Valor</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="revenue-list-body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXPENSES VIEW -->
            <div class="tab-pane fade" id="expenses-view">
                <div class="row">
                    <div class="col-lg-5 mb-3">
                         <div class="card border-0 shadow-sm">
                            <div class="card-header bg-dark text-white d-flex align-items-center">
                                <i class="fas fa-plus-circle me-2"></i> <h6 class="mb-0">Nova Despesa</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Data</label>
                                        <input type="date" class="form-control" id="note-date">
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Pagamento</label>
                                        <select class="form-select" id="note-payment-method">
                                            <option value="DINHEIRO">DINHEIRO</option>
                                            <option value="PIX">PIX</option>
                                            <option value="CRÉDITO">CRÉDITO</option>
                                            <option value="DÉBITO">DÉBITO</option>
                                            <option value="ESTORNO">ESTORNO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Chave/QR da NFC-e</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="note-nfce-key" placeholder="Aproxime o leitor de QRCode ou cole o link da NFC-e">
                                        <button class="btn btn-outline-secondary" type="button" id="btn-open-nfce-camera">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">CNPJ do Fornecedor</label>
                                    <input type="text" class="form-control" id="note-cnpj" placeholder="00.000.000/0000-00">
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Fornecedor</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="note-supplier" placeholder="Ex: Atacadão, Açougue...">
                                        <div id="supplier-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; display: none;"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Descrição Geral</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="note-description" placeholder="Ex: Compras da semana">
                                        <div id="description-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; display: none;"></div>
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Quem Pagou?</label>
                                        <select class="form-select" id="note-paid-by">
                                            <option value="caixa">Caixa da Empresa</option>
                                            <option value="jaqueline">Jaqueline (Reembolsar)</option>
                                            <option value="paloma">Paloma (Reembolsar)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Valor Total (R$)</label>
                                        <input type="number" step="0.01" class="form-control fw-bold" id="note-amount" placeholder="0.00">
                                    </div>
                                </div>
                                
                                <hr class="my-3">
                                <h6 class="small fw-bold text-secondary mb-2"><i class="fas fa-list"></i> Itens da Nota (Opcional - Controle de Estoque)</h6>
                                
                                <div class="bg-light p-2 rounded mb-3">
                                    <div class="row g-2 align-items-end mb-2">
                                        <div class="col-8">
                                            <input type="text" class="form-control form-control-sm" id="item-product-search" placeholder="Buscar produto...">
                                            <div id="expense-product-suggestions" class="list-group position-absolute w-100 shadow-sm product-suggestions-list"></div>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control form-control-sm" id="item-quantity" placeholder="Qtd">
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-8">
                                            <input type="number" step="0.01" class="form-control form-control-sm" id="item-price" placeholder="Preço Unit.">
                                        </div>
                                        <div class="col-4 d-grid">
                                            <button class="btn btn-sm btn-secondary" id="btn-add-expense-item"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <ul class="list-group list-group-flush small mb-3" id="expense-items-list"></ul>

                                <div class="d-grid">
                                    <button class="btn btn-dark" id="btn-add-expense"><i class="fas fa-check me-2"></i> Lançar Despesa</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0">Histórico de Despesas</h6>
                                <div class="input-group input-group-sm w-50">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" placeholder="Filtrar..." id="expense-filter-input">
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive h-100">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="ps-3">Data</th>
                                                <th>Fornecedor</th>
                                                <th>Descrição</th>
                                                <th class="text-end">Valor</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="expense-list-body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REVENUES VIEW -->
            <div class="tab-pane fade" id="revenues-view">
                <div class="row">
                    <div class="col-lg-5 mb-3">
                         <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white d-flex align-items-center">
                                <i class="fas fa-plus-circle me-2"></i> <h6 class="mb-0">Nova Receita</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Data</label>
                                        <input type="date" class="form-control" id="revenue-date">
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Pagamento</label>
                                        <select class="form-select" id="revenue-payment-method">
                                            <option value="DINHEIRO">DINHEIRO</option>
                                            <option value="PIX">PIX</option>
                                            <option value="CRÉDITO">CRÉDITO</option>
                                            <option value="DÉBITO">DÉBITO</option>
                                            <option value="ESTORNO">ESTORNO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Fonte / Origem</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="revenue-source" placeholder="Ex: Venda Extra, Ifood...">
                                        <div id="source-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; display: none;"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Descrição</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="revenue-description" placeholder="Ex: Evento particular">
                                        <div id="revenue-description-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; display: none;"></div>
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Recebido Por</label>
                                        <select class="form-select" id="revenue-received-by">
                                            <option value="caixa">Caixa da Empresa</option>
                                            <option value="jaqueline">Jaqueline</option>
                                            <option value="paloma">Paloma</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">Valor Total (R$)</label>
                                        <input type="number" step="0.01" class="form-control fw-bold" id="revenue-amount" placeholder="0.00">
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button class="btn btn-success" id="btn-add-revenue"><i class="fas fa-check me-2"></i> Lançar Receita</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0">Histórico de Receitas</h6>
                                <div class="input-group input-group-sm w-50">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" placeholder="Filtrar..." id="revenue-filter-input">
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive h-100">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="ps-3">Data</th>
                                                <th>Origem</th>
                                                <th>Descrição</th>
                                                <th class="text-end">Valor</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="revenue-list-body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>

<div class="modal fade" id="nfceCameraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ler QR da NFC-e</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="nfce-qr-reader" style="width: 100%;"></div>
                <p class="small text-muted mt-2">Aponte a câmera para o QR Code da NFC-e.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="nfcePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Importação da NFC-e</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Data de Emissão</small>
                            <strong id="nfce-preview-date">-</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Fornecedor</small>
                            <strong id="nfce-preview-supplier">-</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">CNPJ</small>
                            <strong id="nfce-preview-cnpj">-</strong>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Valor Total da Nota</small>
                    <strong id="nfce-preview-total">R$ 0,00</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Qtd</th>
                                <th class="text-end">Vlr Unit.</th>
                                <th class="text-end">Vlr Total</th>
                            </tr>
                        </thead>
                        <tbody id="nfce-preview-items-body">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-nfce-import">Confirmar Importação</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal structure moved to app.blade.php -->

    <!-- Edit Revenue Modal -->
    <div class="modal fade" id="editRevenueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Receita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-rev-id">
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" id="edit-rev-date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método</label>
                        <select class="form-select" id="edit-rev-method">
                            <option value="DINHEIRO">DINHEIRO</option>
                            <option value="PIX">PIX</option>
                            <option value="CRÉDITO">CRÉDITO</option>
                            <option value="DÉBITO">DÉBITO</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Origem</label>
                        <input type="text" class="form-control" id="edit-rev-source">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="edit-rev-description">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" class="form-control" id="edit-rev-amount" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="updateRevenue()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Pedido (Financeiro)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-order-id">
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-1"></i> Alterar estes dados não afeta os itens do pedido, apenas o registro financeiro total e método de pagamento.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data e Hora</label>
                        <input type="datetime-local" class="form-control" id="edit-order-date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pagamento</label>
                        <select class="form-select" id="edit-order-method">
                            <option value="DINHEIRO">DINHEIRO</option>
                            <option value="PIX">PIX</option>
                            <option value="CRÉDITO">CRÉDITO</option>
                            <option value="DÉBITO">DÉBITO</option>
                            <option value="CADERNETA">CADERNETA</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor Total (R$)</label>
                        <input type="number" class="form-control" id="edit-order-amount" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="updateOrderFinancial()">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>

@include('financial_modals')

@push('scripts')
    <script src="{{ asset('assets/js/financial.js') }}?v={{ time() }}"></script>
@endpush

@endsection
