<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Espetinhos da Jaque')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}?v={{ time() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    @stack('styles')
</head>
<body class="@yield('body-class', '')">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <img src="{{ asset('Jaque.png') }}" alt="Espetinhos da Jaque" height="50" class="d-inline-block align-text-top me-2 bg-white rounded p-1">
            <span class="h4 mb-0">@yield('page-title', 'Gestão')</span>
        </a>

        <!-- Total em Mesas Abertas (Visível apenas na Dashboard) -->
        @if(Request::routeIs('dashboard'))
        <div class="d-none d-md-flex align-items-center text-white me-auto ms-4 bg-secondary bg-opacity-25 px-3 py-1 rounded border border-secondary border-opacity-50">
            <small class="me-2 text-warning fw-bold text-uppercase" style="font-size: 0.75rem;">Em Aberto</small>
            <span class="fs-5 fw-bold text-success" id="total-open-tables-value">R$ 0,00</span>
        </div>
        @endif

        <div>
            <button class="btn btn-outline-info me-2" id="btn-connect-mobile">
                <i class="fas fa-mobile-alt"></i> Mobile
            </button>
            <a href="{{ route('stock') }}" class="btn btn-outline-light me-2 position-relative @if(Request::routeIs('stock')) active @endif">
                <i class="fas fa-boxes"></i> Estoque
                <span id="stock-alert-badge" class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle d-none">
                    <span class="visually-hidden">Alerta</span>
                </span>
            </a>

            <a href="{{ route('financial') }}" class="btn btn-success me-2 @if(Request::routeIs('financial')) active @endif position-relative">
                <i class="fas fa-wallet"></i> Financeiro
                <span id="sidebar-pending-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                    0
                    <span class="visually-hidden">notas pendentes</span>
                </span>
            </a>
            <a href="{{ route('caderneta') }}" class="btn btn-warning me-2 @if(Request::routeIs('caderneta')) active @endif">
                <i class="fas fa-book"></i> Caderneta
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid mb-3">
    <div id="pending-notes-alert" class="alert alert-warning d-none d-flex justify-content-between align-items-center mx-auto container shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-bell me-2 animated swing infinite text-danger"></i>
            <div>
                Há <strong id="pending-count-text" class="fs-5">0</strong> nota(s) pendente(s) enviada(s) pelo celular!
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-danger" onclick="deleteAllPendingNotes()" title="Ignorar Todas">
                <i class="fas fa-trash-alt"></i> Ignorar
            </button>
            <button class="btn btn-sm btn-warning fw-bold text-dark shadow-sm" onclick="openPendingNotesModal()">
                <i class="fas fa-search-dollar"></i> Verificar Agora
            </button>
        </div>
    </div>
</div>

@yield('content')

<!-- Modal Conectar Mobile -->
<div class="modal fade" id="connectMobileModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Conectar Celular</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-3">Escaneie para acessar:</p>
                <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                <p class="text-muted small" id="mobile-url-display">Carregando...</p>
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle"></i> Certifique-se que o celular está no Wi-Fi.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lista de Notas Pendentes -->
<div class="modal fade" id="pendingNotesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-list me-2"></i>Notas Pendentes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="pending-notes-list" class="list-group list-group-flush">
                    <!-- Lista carregada via JS -->
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteAllPendingNotes()">
                    <i class="fas fa-trash-alt me-1"></i> Limpar Tudo
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmação de Importação NFC-e -->
<div class="modal fade" id="confirmNfceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Importar Nota Fiscal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="small text-muted fw-bold">Fornecedor</label>
                        <input type="text" class="form-control bg-light" id="confirm-supplier" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold">Data Emissão</label>
                        <input type="text" class="form-control bg-light" id="confirm-date" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold">Total</label>
                        <input type="text" class="form-control bg-light fw-bold text-success" id="confirm-total" readonly>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3">Itens Identificados</h6>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Vl. Unit.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="confirm-items-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold px-4" id="btn-confirm-import">
                    <i class="fas fa-check-circle me-2"></i> Confirmar Importação
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.API_URL = "{{ route('api.legacy') }}";
</script>
<script src="{{ asset('assets/js/main.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/add_item.js') }}?v={{ time() }}"></script>
@stack('scripts')

</body>
</html>
