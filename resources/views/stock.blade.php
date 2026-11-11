@extends('layouts.app')

@section('title', 'Estoque - Espetinhos da Jaque')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Produtos em Estoque</h2>
        <button class="btn btn-success" id="btn-open-new-product">
            <i class="fas fa-plus"></i> Novo Produto
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <ul class="nav nav-pills" id="stock-category-tabs">
                    <li class="nav-item">
                        <button class="nav-link active stock-category-btn" data-category="bebida">
                            Bebidas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link stock-category-btn" data-category="espetinho">
                            Espetinhos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link stock-category-btn" data-category="outros">
                            Outros
                        </button>
                    </li>
                </ul>
            </div>
            <div id="stock-alerts" class="mb-3"></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Produto</th>
                            <th>EAN</th>
                            <th>Custo</th>
                            <th>Venda</th>
                            <th>Estoque</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody id="stock-table-body">
                        <!-- JS Preenche -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Produto -->
<div class="modal fade" id="newProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cadastrar Novo Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Código EAN</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="new-ean" placeholder="Ex: 7891234567890">
                        <button class="btn btn-outline-secondary" type="button" id="btn-lookup-ean">Buscar</button>
                    </div>
                    <div class="form-text">Digite ou escaneie o código de barras para preencher o nome.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nome do Produto</label>
                    <input type="text" class="form-control text-uppercase" id="new-name" style="text-transform: uppercase;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" id="new-category">
                        <option value="bebida">Bebida</option>
                        <option value="espetinho">Espetinho</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Preço de Custo (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="new-cost" value="0.00">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Preço de Venda (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="new-price" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Estoque Inicial</label>
                        <input type="number" class="form-control" id="new-stock" value="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Estoque Mínimo</label>
                        <input type="number" class="form-control" id="new-min" value="10">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-save-new-product">Cadastrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edição Produto -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id">
                <div class="mb-3">
                    <label class="form-label">Produto</label>
                    <input type="text" class="form-control text-uppercase" id="edit-name" style="text-transform: uppercase;">
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" id="edit-category">
                        <option value="BEBIDA">Bebida</option>
                        <option value="ESPETINHO">Espetinho</option>
                        <option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Código EAN</label>
                    <input type="text" class="form-control" id="edit-ean">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Preço de Custo (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="edit-cost">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Preço de Venda (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="edit-price">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Estoque Mínimo</label>
                        <input type="number" class="form-control" id="edit-min">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-success fw-bold">Entrada de Estoque (+)</label>
                        <input type="number" class="form-control border-success" id="edit-add-stock" value="0">
                        <div class="form-text">Qtd a adicionar ao atual.</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Estoque Atual</label>
                        <input type="number" class="form-control" id="edit-current-stock-input">
                    </div>
                    <div class="col-6 mb-3 d-flex align-items-end">
                        <div class="alert alert-info py-2 w-100 mb-0">
                            Estoque Atual: <strong id="edit-current-stock">0</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-save-product">Salvar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/stock.js') }}?v={{ time() }}"></script>
@endpush
