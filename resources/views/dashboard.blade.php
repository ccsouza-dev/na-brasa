@extends('layouts.app')

@section('title', 'Espetinhos da Jaque - Controle de Caixa')
@section('page-title', 'Gestão')

@section('content')
<div class="container-fluid">
    <!-- Mobile Action Button (Visible on all sizes but optimized for mobile) -->
    <div class="d-md-none fixed-bottom p-3 text-end" style="z-index: 1030; pointer-events: none;">
        <button class="btn btn-primary rounded-circle shadow-lg p-3" id="btn-mobile-scan" style="width: 60px; height: 60px; pointer-events: auto;">
            <i class="fas fa-camera fa-lg"></i>
        </button>
    </div>

    <div class="row" id="orders-container">
        <!-- Mesas aparecerão aqui -->
        <div class="col-12 text-center mt-5">
            <div class="spinner-border text-danger" role="status"></div>
            <p>Carregando pedidos...</p>
        </div>
    </div>
</div>

<!-- Modal Scanner Mobile -->
<div class="modal fade" id="mobileScanModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Escanear NFC-e</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-black d-flex align-items-center justify-content-center">
                <div id="mobile-reader" style="width: 100%; height: 100%;"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <p class="text-muted small mb-0">Aponte para o QR Code da nota</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Item -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Adicionar Item - Mesa <span id="add-item-table-num"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="add-item-order-id">
                
                <div class="row mb-3">
                    <div class="col-12 mb-2">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="product-search-input" placeholder="Buscar produto...">
                        </div>
                    </div>
                    <div class="col-12">
                         <div class="d-flex overflow-auto pb-2 border-bottom" id="category-tabs-container" style="white-space: nowrap;">
                            <button type="button" class="btn btn-sm btn-primary me-2 category-tab active" data-category="BEBIDAS">Bebidas</button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 category-tab" data-category="ESPETINHO">Espetinhos</button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2 category-tab" data-category="OUTROS">Outros</button>
                        </div>
                        <input type="hidden" id="product-category-filter" value="BEBIDAS">
                    </div>
                </div>

                <div class="list-group shadow-sm" id="product-selection-list" style="max-height: 400px; overflow-y: auto;">
                    <!-- JS Preenche -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Pedido (Aberto) -->
<div class="modal fade" id="editOpenOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Pedido - Mesa <span id="edit-open-table-num"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-open-order-id">
                <div class="mb-3">
                    <label class="form-label">Cliente</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="edit-open-customer">
                        <button class="btn btn-outline-primary" type="button" onclick="updateOpenOrderCustomer()">
                            <i class="fas fa-save"></i>
                        </button>
                    </div>
                </div>
                <hr>
                <h6>Itens do Pedido</h6>
                <div class="list-group" id="edit-open-items-list" style="max-height: 300px; overflow-y: auto;">
                    <!-- JS fills this -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
