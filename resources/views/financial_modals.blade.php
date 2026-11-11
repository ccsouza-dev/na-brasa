
<!-- EDIT REVENUE MODAL -->
<div class="modal fade" id="editRevenueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Editar Receita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-rev-id">
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Data</label>
                    <input type="date" class="form-control" id="edit-rev-date">
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Pagamento</label>
                    <select class="form-select" id="edit-rev-method">
                        <option value="DINHEIRO">DINHEIRO</option>
                        <option value="PIX">PIX</option>
                        <option value="CRÉDITO">CRÉDITO</option>
                        <option value="DÉBITO">DÉBITO</option>
                        <option value="ESTORNO">ESTORNO</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Origem / Cliente</label>
                    <input type="text" class="form-control" id="edit-rev-source">
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Descrição</label>
                    <input type="text" class="form-control" id="edit-rev-description">
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Valor (R$)</label>
                    <input type="number" step="0.01" class="form-control" id="edit-rev-amount">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="updateRevenue()">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT EXPENSE MODAL -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Editar Despesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-exp-id">
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Data</label>
                    <input type="date" class="form-control" id="edit-exp-date">
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Pagamento</label>
                    <select class="form-select" id="edit-exp-method">
                        <option value="DINHEIRO">DINHEIRO</option>
                        <option value="PIX">PIX</option>
                        <option value="CRÉDITO">CRÉDITO</option>
                        <option value="DÉBITO">DÉBITO</option>
                        <option value="ESTORNO">ESTORNO</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Fornecedor</label>
                    <input type="text" class="form-control" id="edit-exp-supplier">
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Descrição</label>
                    <input type="text" class="form-control" id="edit-exp-description">
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Quem Pagou?</label>
                    <select class="form-select" id="edit-exp-paid-by">
                        <option value="caixa">Caixa da Empresa</option>
                        <option value="jaqueline">Jaqueline (Reembolsar)</option>
                        <option value="paloma">Paloma (Reembolsar)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">Valor (R$)</label>
                    <input type="number" step="0.01" class="form-control" id="edit-exp-amount">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="updateExpense()">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>
