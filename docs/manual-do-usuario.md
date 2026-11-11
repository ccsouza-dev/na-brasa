# Manual de uso — Espetinhos da Jaque

Sistema de controle de mesas, estoque, caixa e fiado do bar Espetinhos da Jaque (projeto Na Brasa).

Não há tela de login. Quem abre o endereço entra direto na operação.

Quem baixou o código cria o banco na primeira visita, depois de copiar o `.env` e gerar a chave. O passo a passo está no README do repositório. O MySQL precisa estar ligado. O sistema cria o banco `na_brasa_db`, as tabelas e o cardápio.

## 1. Como abrir

1. No XAMPP, deixe o Apache e o MySQL ligados.
2. No computador do caixa, abra o navegador em:

   `http://localhost/Na_Brasa/public`

3. A primeira tela é a **Gestão**. No topo ficam os atalhos **Mobile**, **Estoque**, **Financeiro** e **Caderneta**.
4. O valor **Em aberto** mostra a soma de todas as mesas ocupadas.

O salão tem 20 mesas, numeradas de 1 a 20.

## 2. Gestão das mesas

Cada mesa aparece como um cartão.

- Borda verde e selo **LIVRE**: a mesa não tem comanda.
- Borda vermelha e selo **OCUPADA**: já existe pedido. O cartão lista o cliente, o número da comanda, os itens e o total da mesa.

### Abrir uma mesa

1. Na mesa livre, clique em **Abrir Mesa**.
2. Informe o nome do cliente, se quiser. O nome pode ficar em branco.
3. Clique em **Abrir**.

### Lançar itens

1. Na mesa ocupada, clique no botão **+**.
2. Escolha a aba **Bebidas**, **Espetinhos** ou **Outros**, ou digite o nome na busca.
3. Clique no produto para incluir na comanda.

### Corrigir a comanda

O botão de lápis abre **Editar Pedido**.

- Altere o nome do cliente e clique no ícone de salvar.
- Na lista de itens, remova o que foi lançado por engano.

A lixeira exclui a comanda inteira. Os itens voltam para o estoque. Essa ação não pode ser desfeita.

### Fechar a mesa

1. Clique em **Fechar**.
2. Confira o total e escolha a forma de pagamento:
   - **Dinheiro**: informe quanto o cliente entregou. O sistema calcula o troco e mostra as notas e moedas.
   - **Débito**, **crédito** ou **Pix**: confirme o pagamento.
   - **Caderneta (fiado)**: informe a data prevista de pagamento. O padrão é 30 dias. A conta passa para a tela Caderneta e não entra como dinheiro recebido na hora.
3. Clique em **Confirmar Pagamento**.

## 3. Celular do salão

No computador, clique em **Mobile**. Aparece um QR Code com o endereço do celular. O aparelho precisa estar no mesmo Wi-Fi.

O app se chama **Controle de Comandas**.

1. Toque na mesa. Mesa livre abre uma comanda nova. Mesa ocupada mostra as comandas já abertas.
2. Dentro da mesa, o botão **+** cria outra comanda na mesma mesa, para outro cliente.
3. Escolha a categoria e toque no produto para lançar.
4. Toque em **Total** para ver os itens. Use **−** e **+** para mudar a quantidade.
5. **Fechar conta** pede a forma de pagamento, com as mesmas opções do caixa: dinheiro, débito, crédito, Pix ou caderneta.
6. A seta **Voltar para Mesas** retorna à grade. O ícone de atualizar recarrega as mesas.

O ícone da câmera lê o QR Code de uma NFC-e. A nota não entra sozinha no financeiro: ela fica pendente para o caixa conferir no computador.

## 4. Notas vindas do celular

Quando o celular envia uma NFC-e, o computador mostra um aviso amarelo: “Há nota(s) pendente(s) enviada(s) pelo celular”.

- **Verificar Agora** abre a lista.
- Confira fornecedor, data, total e itens e confirme a importação.
- **Ignorar** ou **Limpar Tudo** descarta as notas sem lançar.

No computador, a câmera de NFC-e também aparece no botão flutuante quando a Gestão é aberta numa tela estreita.

## 5. Estoque

Abra **Estoque**. Os produtos ficam separados em **Bebidas**, **Espetinhos** e **Outros**. A tabela mostra nome, código EAN, custo, preço de venda e quantidade.

O ponto vermelho no botão Estoque avisa que algum produto chegou no estoque mínimo.

### Cadastrar produto

1. Clique em **Novo Produto**.
2. Digite o código de barras e clique em **Buscar** para tentar preencher o nome. Se a busca não achar, digite o nome.
3. Escolha a categoria.
4. Informe custo, preço de venda, estoque inicial e estoque mínimo. O mínimo começa em 10.
5. Clique em **Cadastrar**.

### Ajustar um produto

Na linha do produto, abra a edição.

- Altere nome, categoria, EAN, custo, venda e estoque mínimo.
- **Entrada de Estoque (+)** soma uma quantidade ao estoque atual. Use isso numa compra.
- **Estoque Atual** permite corrigir a quantidade contada.

Clique em **Salvar**.

## 6. Financeiro

Abra **Financeiro**. À esquerda ficam **Visão Geral**, **Receitas** e **Despesas**.

No topo, o período pode ser diário, semanal, quinzenal, mensal, trimestral, semestral ou anual.

### Visão geral

Os cartões mostram:

- **Receita total** do período.
- **A receber**: fiado ainda em aberto. O cartão abre a Caderneta.
- **Despesas** pagas no período.
- **Lucro líquido** e a margem.

Abaixo aparecem o resumo do dia, o fluxo de caixa, as formas de pagamento, as despesas por quem pagou, as vendas por categoria e as últimas movimentações.

### Receitas

Use **Nova Receita** para um valor que não veio do fechamento de mesa.

Informe data, forma de pagamento (dinheiro, Pix, crédito, débito ou estorno), origem ou cliente, descrição, quem recebeu (**Caixa**, **Jaqueline** ou **Paloma**) e o valor. O histórico permite editar ou excluir o lançamento.

O fechamento de mesa em dinheiro, débito, crédito ou Pix já gera a receita. Não lance a mesma venda de novo.

### Despesas

Em **Nova Despesa**, informe data, forma de pagamento, fornecedor, descrição, quem pagou e o valor.

Quem pagou pode ser:

- **Caixa da Empresa**
- **Jaqueline (Reembolsar)**
- **Paloma (Reembolsar)**

Para uma nota fiscal:

1. Cole a chave ou o QR da NFC-e, ou use a câmera.
2. Confira o CNPJ e o nome do fornecedor.
3. Se quiser dar entrada no estoque, preencha os itens da nota.
4. Clique em **Lançar Despesa** ou confirme a importação.

O histórico permite editar ou excluir a despesa.

## 7. Caderneta

Abra **Caderneta**. A lista junta, por cliente, todas as comandas fechadas como fiado.

Cada linha mostra a última compra, a previsão de pagamento, o nome, o total e os itens.

- A data em vermelho está atrasada.
- O selo **Hoje** é o vencimento do dia.
- **Ver itens** abre o consumo agrupado por data.
- **Quitar Tudo** baixa todos os pedidos daquele cliente. Escolha dinheiro, Pix, crédito ou débito. A opção **Lançar entrada no Caixa de Hoje** vem marcada: desmarque só se o dinheiro não deve entrar no caixa.

**Atualizar** recarrega a lista.

## 8. Rotina sugerida do dia

1. Ligue Apache e MySQL e abra a Gestão.
2. Abra as mesas conforme os clientes sentam e lance os pedidos pelo caixa ou pelo celular.
3. Feche cada mesa na forma de pagamento correta. Fiado só com o nome do cliente e a data prevista.
4. Quando chegar mercadoria, lance a despesa e a entrada de estoque, ou confirme a NFC-e enviada pelo celular.
5. No fim do período, confira a Visão Geral e quite a Caderneta dos clientes que pagaram.
