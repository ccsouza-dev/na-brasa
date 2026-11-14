# Dicionário de dados — na_brasa_db

Banco físico do Espetinhos da Jaque, executado no MySQL (SGBD relacional). Charset `utf8mb4`, engine InnoDB.

O comando `php artisan na-brasa:install` cria o banco, as tabelas abaixo e o cardápio inicial. As tabelas `users`, `sessions`, `cache`, `jobs` e `migrations` pertencem ao Laravel e não fazem parte do negócio.

Chave: PK é chave primária, FK é chave estrangeira, U é índice único.

## products

Produto vendido no salão.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(6) unsigned | não | PK | Identificador do produto. |
| name | varchar(50) | não | | Nome do produto. |
| price | decimal(10,2) | não | | Preço de venda. |
| category | varchar(30) | não | | BEBIDA, ESPETINHO ou OUTROS. |
| active | tinyint(1) | sim | | 1 aparece no cardápio; 0 fica oculto. Padrão 1. |
| cost_price | decimal(10,2) | sim | | Preço de custo. Padrão 0,00. |
| stock_quantity | int(11) | sim | | Quantidade em estoque. Padrão 0. |
| min_stock | int(11) | sim | | Quantidade mínima antes do alerta. Padrão 10. |
| barcode | varchar(50) | sim | | Código de barras EAN. |
| supplier_id | int(11) | sim | | Fornecedor ligado ao produto. Sem chave estrangeira declarada. |

## orders

Comanda de uma mesa.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(6) unsigned | não | PK | Identificador da comanda. |
| customer_name | varchar(50) | sim | | Nome do cliente. Pode ficar vazio. |
| table_number | int(3) | sim | | Número da mesa, de 1 a 20. |
| status | enum('open','paid') | sim | | open é comanda aberta; paid é comanda fechada. Padrão open. |
| total | decimal(10,2) | sim | | Valor total da comanda. Padrão 0,00. |
| created_at | timestamp | não | | Data e hora de abertura. |
| payment_method | varchar(50) | sim | | DINHEIRO, DÉBITO, CRÉDITO, PIX ou CADERNETA. |
| paid_amount | decimal(10,2) | sim | | Valor entregue pelo cliente. Padrão 0,00. |
| change_amount | decimal(10,2) | sim | | Troco, quando o pagamento é em dinheiro. Padrão 0,00. |
| motoboy_name | varchar(100) | sim | | Nome do entregador, quando houver entrega. |
| paid_at | datetime | sim | | Data e hora do fechamento. |
| debt_due_date | date | sim | | Data prevista de pagamento, quando a forma é caderneta. |
| debt_paid_at | datetime | sim | | Data em que o fiado foi quitado. |

## order_items

Item lançado em uma comanda.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(6) unsigned | não | PK | Identificador do item. |
| order_id | int(6) unsigned | sim | FK | Comanda. Referencia `orders.id`. Ao apagar a comanda, o banco restringe se ainda houver item. |
| product_id | int(6) unsigned | sim | FK | Produto. Referencia `products.id`. A exclusão do produto é restrita se ainda houver item. |
| quantity | int(3) | não | | Quantidade pedida. |
| price | decimal(10,2) | não | | Preço unitário no momento do lançamento. |
| created_at | timestamp | não | | Data e hora do lançamento. |
| status | enum('pending','delivered') | sim | | pending é pendente; delivered é entregue. Padrão pending. |
| delivered_at | timestamp | sim | | Data e hora da entrega. |
| is_paid | tinyint(1) | sim | | 1 se o item já foi pago. Padrão 0. |

## order_payments

Parcela de pagamento de uma comanda.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(11) | não | PK | Identificador do pagamento. |
| order_id | int(6) unsigned | não | FK | Comanda. Referencia `orders.id`. Apagar a comanda apaga os pagamentos. |
| amount | decimal(10,2) | não | | Valor pago nessa parcela. |
| payment_method | varchar(50) | não | | Forma usada nessa parcela. |
| created_at | timestamp | não | | Data e hora do lançamento. |

## suppliers

Fornecedor de compra.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(11) | não | PK | Identificador do fornecedor. |
| name | varchar(255) | não | | Nome do fornecedor. |
| cnpj | varchar(20) | sim | U | CNPJ. Não se repete quando informado. |
| contact_info | text | sim | | Contato ou observação. |
| created_at | timestamp | não | | Data e hora do cadastro. |

## expenses

Despesa do bar.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(11) | não | PK | Identificador da despesa. |
| description | varchar(255) | não | | Descrição da despesa. |
| supplier | varchar(255) | sim | | Nome do fornecedor, em texto. |
| payment_method | varchar(50) | sim | | DINHEIRO, PIX, CRÉDITO, DÉBITO ou ESTORNO. |
| amount | decimal(10,2) | não | | Valor total. |
| paid_by | enum('jaqueline','paloma','caixa') | não | | Quem pagou: Jaqueline, Paloma ou o caixa da empresa. |
| expense_date | datetime | sim | | Data da despesa. Padrão: momento do lançamento. |
| created_at | timestamp | não | | Data e hora do registro. |

## expense_items

Item de uma despesa, usado na entrada de estoque a partir da nota.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(11) | não | PK | Identificador do item. |
| expense_id | int(11) | não | FK | Despesa. Referencia `expenses.id`. Apagar a despesa apaga os itens. |
| product_id | int(6) unsigned | sim | FK | Produto do estoque. Referencia `products.id`. Apagar o produto deixa este campo nulo. |
| product_name | varchar(100) | sim | | Nome do item como veio na nota. |
| quantity | decimal(10,2) | sim | | Quantidade comprada. Padrão 1,00. |
| unit_price | decimal(10,2) | sim | | Custo unitário. Padrão 0,00. |
| total_price | decimal(10,2) | sim | | Custo total do item. Padrão 0,00. |

## revenues

Receita lançada à parte do fechamento de mesa.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | bigint(20) unsigned | não | PK | Identificador da receita. |
| description | varchar(255) | não | | Descrição. |
| source | varchar(255) | sim | | Origem ou cliente. |
| payment_method | varchar(255) | sim | | DINHEIRO, PIX, CRÉDITO, DÉBITO ou ESTORNO. |
| amount | decimal(10,2) | não | | Valor recebido. |
| received_by | enum('jaqueline','paloma','caixa') | não | | Quem recebeu: Jaqueline, Paloma ou o caixa. |
| revenue_date | datetime | não | | Data da receita. |
| created_at | timestamp | não | | Data e hora do registro. |

## transactions

Movimentação de caixa. Uma venda, uma receita ou uma despesa gera um registro aqui.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | bigint(20) unsigned | não | PK | Identificador da movimentação. |
| type | enum('income','expense') | não | | income é entrada; expense é saída. |
| amount | decimal(10,2) | não | | Valor. |
| payment_method | varchar(255) | sim | | Forma de pagamento. |
| description | varchar(255) | não | | Descrição da movimentação. |
| category | varchar(255) | sim | | Categoria, origem ou fornecedor. |
| responsible | varchar(255) | sim | | Quem pagou ou recebeu. |
| transaction_date | datetime | não | | Data da movimentação. |
| order_id | bigint(20) unsigned | sim | | Comanda de origem, quando a entrada veio de uma venda. Sem chave estrangeira. |
| original_source | varchar(255) | sim | | Tabela de origem: orders, revenues ou expenses. |
| original_id | bigint(20) unsigned | sim | | Identificador do registro de origem. |
| created_at | timestamp | sim | | Data e hora de criação. |
| updated_at | timestamp | sim | | Data e hora da última alteração. |

## transaction_items

Itens que compõem uma movimentação de caixa.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | bigint(20) unsigned | não | PK | Identificador do item. |
| transaction_id | bigint(20) unsigned | não | FK | Movimentação. Referencia `transactions.id`. Apagar a movimentação apaga os itens. |
| product_id | bigint(20) unsigned | sim | | Produto. Sem chave estrangeira. |
| name | varchar(255) | não | | Nome do item no momento da movimentação. |
| quantity | decimal(10,3) | não | | Quantidade. |
| unit_price | decimal(10,2) | não | | Preço unitário. |
| total_price | decimal(10,2) | não | | Preço total do item. |
| created_at | timestamp | sim | | Data e hora de criação. |
| updated_at | timestamp | sim | | Data e hora da última alteração. |

## pending_notes

NFC-e lida no celular e ainda não conferida no caixa.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | bigint(20) unsigned | não | PK | Identificador da nota pendente. |
| url | varchar(1000) | não | | Endereço ou conteúdo lido no QR Code. |
| access_key | varchar(255) | sim | | Chave de acesso da NFC-e. |
| supplier_name | varchar(255) | sim | | Nome do fornecedor identificado. |
| total_amount | decimal(10,2) | sim | | Valor total da nota. |
| emission_date | date | sim | | Data de emissão. |
| status | varchar(255) | não | | pending aguarda conferência; processed já foi importada. Padrão pending. |
| created_at | timestamp | sim | | Data e hora do envio. |
| updated_at | timestamp | sim | | Data e hora da última alteração. |

## closings

Fechamento de um período do caixa.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| id | int(11) | não | PK | Identificador do fechamento. |
| start_date | datetime | não | | Início do período. |
| end_date | datetime | não | | Fim do período. |
| total_revenue | decimal(10,2) | não | | Receita do período. Padrão 0,00. |
| total_expenses_caixa | decimal(10,2) | não | | Despesas pagas pelo caixa. Padrão 0,00. |
| reimbursement_jaqueline | decimal(10,2) | não | | Valor a reembolsar para Jaqueline. Padrão 0,00. |
| reimbursement_paloma | decimal(10,2) | não | | Valor a reembolsar para Paloma. Padrão 0,00. |
| fixed_costs_total | decimal(10,2) | não | | Soma dos custos fixos. Padrão 0,00. |
| fixed_costs_details | text | sim | | Detalhe dos custos fixos. |
| net_profit | decimal(10,2) | não | | Lucro líquido. Padrão 0,00. |
| profit_share_jaqueline | decimal(10,2) | não | | Parte do lucro de Jaqueline. Padrão 0,00. |
| profit_share_paloma | decimal(10,2) | não | | Parte do lucro de Paloma. Padrão 0,00. |
| created_at | timestamp | não | | Data e hora do fechamento. |

## settings

Parâmetro do sistema. A chave é o próprio identificador.

| Coluna | Tipo | Nulo | Chave | Descrição |
| --- | --- | --- | --- | --- |
| setting_key | varchar(50) | não | PK | Nome do parâmetro. `sla_yellow` é 15 minutos e `sla_red` é 20 minutos, usados na leitura de tempo de atendimento. |
| setting_value | varchar(255) | não | | Valor do parâmetro. |

## Relacionamentos

- Uma comanda (`orders`) tem vários itens (`order_items`) e vários pagamentos (`order_payments`).
- Um produto (`products`) aparece em itens de comanda e em itens de despesa.
- Uma despesa (`expenses`) tem vários itens (`expense_items`).
- Uma movimentação de caixa (`transactions`) tem vários itens (`transaction_items`).
- `transactions.original_source` e `transactions.original_id` apontam, em conjunto, para a comanda, a receita ou a despesa que originou a movimentação.
