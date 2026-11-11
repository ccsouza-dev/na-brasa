function app() {
    return {
        view: 'tables',
        loading: false,
        tables: [],
        products: [],
        categories: [],
        activeCategory: '',
        currentTable: {},
        currentOrder: null,
        currentTableOrders: [],
        cart: [],
        showCartDetails: false, // Controla se a lista detalhada aparece
        showOrderHistory: false,
        orderHistory: [],
        showDeliveryModal: false,
        lastSentItems: [],

        get groupedHistory() {
            const groups = {};
            this.orderHistory.forEach(item => {
                const key = item.name + '|' + item.status;
                if(!groups[key]) {
                    groups[key] = {
                        ...item,
                        ids: [],
                        items: [],
                        count: 0,
                        totalPrice: 0
                    };
                }
                groups[key].ids.push(item.id);
                groups[key].items.push(item);
                let qty = parseInt(item.quantity || 1);
                groups[key].count += qty;
                groups[key].totalPrice += parseFloat(item.price) * qty;
            });
            // Ordenar: Pendentes primeiro
            return Object.values(groups).sort((a, b) => {
                if(a.status === 'pending' && b.status !== 'pending') return -1;
                if(a.status !== 'pending' && b.status === 'pending') return 1;
                return 0;
            });
        },

        init() {
            // Configuração Global do SweetAlert2 (Mixin para Toasts)
            const MobileToast = Swal.mixin({
                toast: true,
                position: 'top',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: false,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            window.MobileToast = MobileToast; // Tornar global para usar em outros métodos

            this.fetchTables();
            this.fetchProducts();

            // Lógica de Histórico do Navegador (Botão Voltar do Android)
            window.addEventListener('popstate', (event) => {
                if (this.view === 'order') {
                    this.view = 'tables';
                    this.currentTable = {};
                    this.cart = [];
                    this.showOrderHistory = false;
                    
                    // Atualizar mesas e rolar para o topo
                    this.fetchTables();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            // Atualizar status das mesas a cada 30s
            setInterval(() => {
                if(this.view === 'tables') this.fetchTables();
                if(this.showOrderHistory) this.fetchOrderHistory();
            }, 30000);
        },

        async fetchOrderHistory() {
            if (!this.currentTable.order_id) return;
            try {
                const res = await axios.post((window.API_URL || 'api.php') + '?action=get_table_items', {
                    order_id: this.currentTable.order_id
                });
                this.orderHistory = res.data;
            } catch (e) {
                console.error(e);
            }
        },

        async markDelivered(itemId) {
            try {
                await axios.post((window.API_URL || 'api.php') + '?action=mark_delivered', {
                    item_id: itemId
                });
                // Atualizar lista localmente para feedback instantâneo
                const item = this.orderHistory.find(i => i.id === itemId);
                if(item) {
                    item.status = 'delivered';
                }
                // Toca um som ou vibração suave
                if(navigator.vibrate) navigator.vibrate(50);
            } catch (e) {
                // Silencioso
            }
        },

        calculateOrderTotal() {
            return this.orderHistory.reduce((acc, item) => acc + parseFloat(item.price), 0);
        },

        formatMoney(value) {
            return parseFloat(value).toFixed(2).replace('.', ',');
        },

        async addItemImmediately(product) {
            // Lógica para itens com valor variável (Dose, Diversos)
            if (product.name.toUpperCase() === 'DIVERSOS' || product.name.toUpperCase() === 'DOSE') {
                const { value: price } = await Swal.fire({
                    title: 'Valor do Item',
                    text: `Informe o valor para ${product.name}`,
                    input: 'number',
                    inputValue: '', // Começa vazio
                    inputAttributes: {
                        step: '0.01',
                        min: '0.01',
                        inputmode: 'decimal' // Ajuda no teclado mobile
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Adicionar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ef4444',
                    inputValidator: (value) => {
                        if (!value || value <= 0) {
                            return 'Por favor, informe um valor válido!';
                        }
                    }
                });

                if (!price) return; // Cancelado pelo usuário

                // Cria uma cópia do produto com o novo preço para não alterar o original da lista
                product = { ...product, price: parseFloat(price) };
            }

            // Feedback visual
            if (navigator.vibrate) navigator.vibrate(50);
            
            MobileToast.fire({
                icon: 'success',
                title: 'Adicionado: ' + product.name
            });

            try {
                // Se não tiver comanda aberta, cria uma
                if (!this.currentTable.order_id) {
                    await this.createOrderOnServer();
                    // Se falhar a criação, o catch pega
                }

                await axios.post((window.API_URL || 'api.php') + '?action=add_item', {
                    order_id: this.currentTable.order_id,
                    product_id: product.id,
                    quantity: 1,
                    price: product.price
                });

                // Atualiza histórico e totais
                this.fetchOrderHistory();
                this.fetchTables();
            } catch (e) {
                console.error(e);
                MobileToast.fire({
                    icon: 'error',
                    title: 'Erro ao adicionar item'
                });
            }
        },

        async changeItemQty(group, change) {
            // group é um objeto do groupedHistory
            
            if (change > 0) {
                // Adicionar +1 (usa o mesmo endpoint de add_item)
                // Precisamos do product_id. O groupedHistory copia propriedades do primeiro item.
                // Itens vindos do getTableItems têm product_id.
                
                await this.addItemImmediately({
                    id: group.product_id,
                    name: group.name,
                    price: group.price // ou product_price
                });
            } else {
                // Remover -1
                // Encontrar um item para remover. Preferencialmente um com qty=1 para deletar a linha,
                // ou reduzir qty se houver linhas com qty > 1.
                
                // Vamos simplificar: Se houver item com qty > 1, reduz. Se não, deleta o mais recente.
                // Mas a lógica de groupedHistory não garante ordem cronológica nos items array.
                // Vamos pegar o último do array items.
                
                if (!group.items || group.items.length === 0) return;
                
                const targetItem = group.items[group.items.length - 1]; // Pega o último
                
                if (parseInt(targetItem.quantity) > 1) {
                    // Reduz quantidade
                    try {
                        await axios.post((window.API_URL || 'api.php') + '?action=update_item_quantity', {
                            item_id: targetItem.id,
                            quantity: parseInt(targetItem.quantity) - 1
                        });
                        this.fetchOrderHistory();
                        this.fetchTables();
                    } catch(e) {
                        console.error(e);
                    }
                } else {
                    // Deleta item (qty=1)
                    // Pede confirmação apenas se for o último do grupo (zera o contador visual) ?
                    // O usuário quer agilidade. "alterar quantidade".
                    // Se eu tenho 5 cervejas, clicar em (-) deve remover uma sem perguntar.
                    // Se eu tenho 1 cerveja, clicar em (-) deve remover e sumir.
                    
                    // Vou colocar confirmação SOMENTE se for deletar o item.
                    // Como a UX de +/- é rápida, talvez sem confirmação seja melhor, como no iFood.
                    // Vou deixar sem confirmação para ser rápido.
                    
                    try {
                        await axios.post((window.API_URL || 'api.php') + '?action=delete_order_item', {
                            item_id: targetItem.id
                        });
                        this.fetchOrderHistory();
                        this.fetchTables();
                    } catch(e) {
                        console.error(e);
                    }
                }
            }
        },

        removeFromCart(productId) {
            // Remove apenas a última ocorrência desse produto
            const index = this.cart.findIndex(i => i.id === productId);
            if (index > -1) {
                this.cart.splice(index, 1);
                if(this.cart.length === 0) this.showCartDetails = false;
                if(navigator.vibrate) navigator.vibrate(20);
            }
        },

        getGroupedCart() {
            // Agrupa itens iguais para mostrar na lista (ex: 2x Cerveja)
            const grouped = {};
            this.cart.forEach(item => {
                if (!grouped[item.id]) {
                    grouped[item.id] = { ...item, qty: 0 };
                }
                grouped[item.id].qty++;
            });
            return Object.values(grouped);
        },

        getOrderItems(table, orderId) {
            if (!table || !table.items) return [];
            return table.items.filter(i => i.order_id === orderId);
        },

        getOrderTotalItems(table, orderId) {
            const items = this.getOrderItems(table, orderId);
            return items.reduce((acc, i) => acc + parseFloat(i.qty), 0);
        },

        async fetchTables() {
            this.loading = true;
            try {
                const res = await axios.get((window.API_URL || 'api.php') + '?action=get_tables_status');
                this.tables = res.data;
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        async fetchProducts() {
            try {
                const res = await axios.get((window.API_URL || 'api.php') + '?action=get_products');
                this.products = res.data;
                
                // Categorias fixas
                this.categories = ['BEBIDAS', 'ESPETINHO', 'OUTROS'];

                if (this.categories.length > 0) this.activeCategory = this.categories[0];
            } catch (e) {
                console.error(e);
            }
        },

        get filteredProducts() {
            // Se for BEBIDAS, retornamos tudo que for bebida para processamento no template
            // O template vai chamar getDrinkSubCategory para agrupar
            if (this.activeCategory === 'BEBIDAS') {
                return this.products.filter(p => this.isDrink(p));
            }
            return this.products.filter(p => p.category === this.activeCategory);
        },

        getProductLogo(name) {
            if(!name) return null;
            const n = name.toLowerCase();
            
            // 1. Local Images (Priority based on file list found in public/)
            if(n.includes('heineken')) return '109_heineken.jpg';
            if(n.includes('amstel')) return 'amstel-logo-0-2048x2048.png';
            if(n.includes('original') || n.includes('antarctica')) return 'antartica original.jpg';
            if(n.includes('brahma')) return 'brahma-logo-png_seeklogo-21957.png';
            if(n.includes('del valle')) return 'del-valle-vector-logo-11573922718mzzzfoklzt.png';
            if(n.includes('skol')) return 'skol.jpg';
            if(n.includes('coração') || n.includes('coracao')) return 'coração de frango.jpg';
            if(n.includes('espetinho') && n.includes('frango')) return 'espetinho_frango-600x450.jpg';
            if(n.includes('espetinho') && (n.includes('queijo') || n.includes('coalho'))) return 'espetinho_queijo_coalho_branco.png';
            if(n.includes('carne') && n.includes('porco')) return 'carne de porco.jpg';
            if(n.includes('misto') || (n.includes('boi') && n.includes('legumes'))) return 'boi + pimentao + cebola.png';
            if(n.includes('carne') || n.includes('porco')) return 'carne.jpg';
            if(n.includes('kafta')) return 'kafta.jpg';
            if(n.includes('linguiça') || n.includes('linguica')) return 'linguiça.jpg';
            if(n.includes('pão de alho') || n.includes('pao de alho')) return 'pão de alho.jpg';
            if(n.includes('pururuca')) return 'pururuca.jpg';
            if(n.includes('amendoim')) return 'amendoim.jfif';
            if(n.includes('torcida')) return 'torcida.jpg';
            if(n.includes('keleck')) return 'keleck.png';
            if(n.includes('salgado')) return 'salgados.jpg';
            if(n.includes('pepe legal')) return 'pepe legal.jpg';

            // 2. Specific User Provided Links
            if(n.includes('fanta')) return 'https://www.clipartmax.com/png/small/22-226155_image-result-for-fanta-fanta-logo.png';
            if(n.includes('coca') || n.includes('coke')) return 'https://www.clipartmax.com/png/small/104-1049163_coca-cola-bottle-logo-vector-free-vector-silhouette-coca-cola-vector-png.png';
            if(n.includes('budweiser')) return 'https://www.clipartmax.com/png/small/121-1211579_budweiser-logo-simple-budweiser-logo-png.png';
            
            // 3. Clearbit Fallback (for others)
            if(n.includes('pepsi')) return 'https://logo.clearbit.com/pepsi.com';
            if(n.includes('guarana')) return 'https://logo.clearbit.com/ambev.com.br'; 
            if(n.includes('sprite')) return 'https://logo.clearbit.com/sprite.com';
            if(n.includes('stella')) return 'https://logo.clearbit.com/stellaartois.com';
            if(n.includes('spaten')) return 'https://logo.clearbit.com/spaten.com.br';
            if(n.includes('corona')) return 'https://logo.clearbit.com/corona.com';
            if(n.includes('eisenbahn')) return 'https://logo.clearbit.com/eisenbahn.com.br';
            if(n.includes('red bull')) return 'https://logo.clearbit.com/redbull.com';
            if(n.includes('schweppes')) return 'https://logo.clearbit.com/schweppes.com.br';
            if(n.includes('h2o')) return 'https://logo.clearbit.com/pepsico.com';
            
            return null;
        },

        isDrink(p) {
            const cat = (p.category || '').toUpperCase();
            const name = (p.name || '').toUpperCase();
            // Verifica se é alguma categoria de bebida ou produto conhecido
            return ['BEBIDA', 'BEBIDAS', 'CERVEJA', 'REFRIGERANTE', 'AGUA', 'SUCO'].includes(cat) || 
                   name.includes('DEL VALLE') || 
                   name.includes('HEINEKEN') || 
                   name.includes('COCA') || 
                   name.includes('FANTA');
        },

        getDrinkSubCategory(p) {
            const cat = (p.category || '').toUpperCase();
            const name = (p.name || '').toUpperCase();

            // Lógica de agrupamento
            if (cat === 'AGUA' || cat === 'SUCO' || name.includes('DEL VALLE') || name.includes('AGUA') || name.includes('H2O')) {
                return 'AGUA_SUCO';
            }
            if (cat === 'CERVEJA' || name.includes('CERVEJA') || name.includes('HEINEKEN') || name.includes('AMSTEL') || name.includes('BRAHMA') || name.includes('SKOL') || name.includes('SPATEN') || name.includes('STELLA') || name.includes('BUDWEISER') || name.includes('ORIGINAL') || name.includes('ANTARCTICA') || name.includes('BOHEMIA') || name.includes('IMPERIO')) {
                return 'CERVEJA';
            }
            if (cat === 'REFRIGERANTE' || name.includes('REFRIGERANTE') || name.includes('COCA') || name.includes('FANTA') || name.includes('GUARANA') || name.includes('SPRITE') || name.includes('PEPSI') || name.includes('KUAT') || name.includes('SUKITA') || name.includes('SCHWEPPES') || name.includes('SODA')) {
                return 'REFRIGERANTE';
            }
            return 'OUTROS';
        },

        getCategoryLabel(cat) {
            const labels = {
                'AGUA_SUCO': 'Água / Suco',
                'CERVEJA': 'Cerveja',
                'REFRIGERANTE': 'Refrigerante',
                'ESPETINHO': 'Espetinhos',
                'PORCAO': 'Porções',
                'OUTROS': 'Outros',
                'BEBIDAS': 'Bebidas'
            };
            return labels[cat] || cat.charAt(0).toUpperCase() + cat.slice(1).toLowerCase();
        },

        get tableSummary() {
            if (!this.currentTable || !this.currentTable.orders) return [];
            
            return this.currentTable.orders.map(order => {
                const orderItems = (this.currentTable.items || []).filter(i => i.order_id === order.id);
                const itemCount = orderItems.reduce((acc, i) => acc + parseFloat(i.qty), 0);
                
                // Mapeia os itens detalhados
                const items = orderItems.map(i => ({
                    name: i.name,
                    qty: parseFloat(i.qty),
                    subtotal: parseFloat(i.subtotal)
                }));
                
                return {
                    id: order.id,
                    name: order.customer_name || 'Cliente',
                    total: parseFloat(order.total),
                    count: itemCount,
                    items: items
                };
            });
        },

        get tableTotal() {
            return (this.currentTable.orders || []).reduce((acc, o) => acc + parseFloat(o.total), 0);
        },

        get cartTotal() {
            return this.cart.reduce((sum, item) => sum + parseFloat(item.price), 0);
        },

        get cartTotalQty() {
            return this.cart.length;
        },

        get cartTotalValue() {
            return this.cartTotal;
        },

        getCategoryButtonActiveClasses(category) {
            return 'bg-brand-600 text-white shadow-md ring-2 ring-brand-300';
        },

        getCategoryCardClasses(category) {
            return 'border-brand-200 hover:border-brand-400';
        },
        
        openTable(table) {
            this.currentTable = table;
            this.cart = [];

            if (table.status === 'free') {
                this.currentTableOrders = [];
                this.currentOrder = null;
                this.promptNewTable(table);
                return;
            }

            const orders = Array.isArray(table.orders) ? table.orders : [];

            // Sempre abre a primeira comanda (ou a mais recente se preferir lógica mais complexa)
            // A troca entre clientes é feita pela barra superior
            const selected = orders[0];
            this.currentOrder = selected;
            this.currentTable.order_id = selected.id;
            this.view = 'order';
            this.fetchOrderHistory();
            history.pushState({view: 'order'}, '', '#mesa' + table.number);
        },

        selectOrder(order) {
            if (!order || !this.currentTable) return;
            this.currentOrder = order;
            this.currentTable.order_id = order.id;
            this.view = 'order';
            this.fetchOrderHistory();
            // Optional: Scroll to active button if needed
        },

        openNewOrderForCurrentTable() {
            if (!this.currentTable || !this.currentTable.number) return;
            const table = this.tables.find(t => t.number === this.currentTable.number) || this.currentTable;
            this.currentTableOrders = Array.isArray(table.orders) ? table.orders : [];
            this.currentOrder = null;
            this.promptNewTable(table);
        },

        async promptNewTable(table) {
             const { value: customerName } = await Swal.fire({
                title: 'Nova Mesa ' + table.number,
                text: 'Nome do Cliente (Opcional)',
                input: 'text',
                inputPlaceholder: 'Ex: João',
                showCancelButton: true,
                confirmButtonText: 'Abrir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444'
            });

            if (customerName !== undefined) {
                this.currentTable.customer = customerName || 'Cliente';
                this.createOrderOnServer();
            }
        },

        async createOrderOnServer() {
            try {
                const res = await axios.post((window.API_URL || 'api.php') + '?action=create_order', {
                    table: this.currentTable.number,
                    customer: this.currentTable.customer
                });
                
                if (res.data.success) {
                    const newOrder = {
                        id: res.data.order_id,
                        customer_name: this.currentTable.customer,
                        total: 0,
                        sla_status: 'ok'
                    };
                    this.currentOrder = newOrder;
                    this.currentTable.order_id = res.data.order_id;
                    this.currentTable.status = 'busy';
                    
                    if (!this.currentTable.orders) this.currentTable.orders = [];
                    this.currentTable.orders.push(newOrder);

                    this.view = 'order';
                    this.fetchOrderHistory();
                    history.pushState({view: 'order'}, '', '#mesa' + this.currentTable.number);
                    this.fetchTables();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ops!',
                        text: res.data.message
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Conexão Perdida',
                    text: 'Não foi possível conectar ao PC. Se o IP mudou, escaneie o QR Code novamente.',
                    confirmButtonText: 'Entendi'
                });
            }
        },

        async closeOrderMobile() {
            if (!this.currentTable || !this.currentTable.order_id) return;
            
            const total = this.calculateOrderTotal();
            const orderId = this.currentTable.order_id;
            const tableNum = this.currentTable.number;
            const customer = this.currentTable.customer || 'Cliente';

            const { isConfirmed } = await Swal.fire({
                title: 'Fechar Conta?',
                html: `Cliente: <strong>${customer}</strong><br>Total: <strong>R$ ${this.formatMoney(total)}</strong>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, fechar!',
                cancelButtonText: 'Cancelar'
            });

            if (isConfirmed) {
                this.selectPaymentMethod(tableNum, total, customer, orderId);
            }
        },

        async selectPaymentMethod(tableNum, total, customer, orderId) {
            const { value: paymentMethod } = await Swal.fire({
                title: 'Forma de Pagamento',
                input: 'select',
                inputOptions: {
                    'DINHEIRO': 'Dinheiro',
                    'PIX': 'PIX',
                    'DEBITO': 'Débito',
                    'CREDITO': 'Crédito',
                    'CADERNETA': 'Caderneta (Fiado)'
                },
                inputPlaceholder: 'Selecione...',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                inputValidator: (value) => {
                    return new Promise((resolve) => {
                        if (value) {
                            resolve();
                        } else {
                            resolve('Selecione uma forma de pagamento');
                        }
                    });
                }
            });

            if (!paymentMethod) return;

            if (paymentMethod === 'CADERNETA') {
                this.askDueDateAndClose(tableNum, total, customer, orderId);
            } else if (paymentMethod === 'DINHEIRO') {
                this.askCashAmountAndClose(tableNum, total, customer, orderId);
            } else {
                this.finalizeCloseOrder(tableNum, total, customer, paymentMethod, orderId);
            }
        },

        async askCashAmountAndClose(tableNum, total, customer, orderId) {
            let confirmed = false;
            while (!confirmed) {
                const { value: amount, isDismissed } = await Swal.fire({
                    title: 'Pagamento em Dinheiro',
                    html: `Total: <strong>R$ ${this.formatMoney(total)}</strong><br>Valor recebido:`,
                    input: 'number',
                    inputValue: '',
                    inputAttributes: {
                        step: '0.01',
                        min: total
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Calcular Troco',
                    preConfirm: (val) => {
                        if (parseFloat(val) < parseFloat(total)) {
                            Swal.showValidationMessage(`Valor menor que o total (R$ ${this.formatMoney(total)})`);
                        }
                        return val;
                    }
                });

                if (isDismissed) return;

                if (amount) {
                    const paid = parseFloat(amount);
                    const change = paid - parseFloat(total);
                    
                    // Mostra troco simples no mobile (apenas texto por enquanto, ou adaptar o visual complexo depois)
                    const { isConfirmed: isPaid, dismiss } = await Swal.fire({
                        title: 'Troco',
                        html: `
                            <div class="text-lg mb-2">
                                Valor Pago: <strong>R$ ${this.formatMoney(paid)}</strong><br>
                                Total: <strong>R$ ${this.formatMoney(total)}</strong>
                            </div>
                            <div class="text-3xl text-green-600 font-bold mb-4">
                                Troco: R$ ${this.formatMoney(change)}
                            </div>
                        `,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Confirmar Pagamento',
                        confirmButtonColor: '#198754',
                        cancelButtonText: 'Voltar'
                    });

                    if (isPaid) {
                        this.finalizeCloseOrder(tableNum, total, customer, 'DINHEIRO', orderId);
                        confirmed = true;
                    }
                    // Se cancelar (Voltar), o loop continua e pede o valor de novo
                }
            }
        },

        async askDueDateAndClose(tableNum, total, customer, orderId) {
            const { value: date } = await Swal.fire({
                title: 'Previsão de Pagamento',
                html: '<input type="date" id="debt-due-date" class="swal2-input">',
                confirmButtonText: 'Salvar na Caderneta',
                showCancelButton: true,
                preConfirm: () => {
                    const d = document.getElementById('debt-due-date').value;
                    if (!d) Swal.showValidationMessage('Informe uma data prevista');
                    return d;
                }
            });

            if (date) {
                this.finalizeCloseOrder(tableNum, total, customer, 'CADERNETA', orderId, date);
            }
        },

        async finalizeCloseOrder(tableNum, total, customer, paymentMethod, orderId, dueDate = null) {
            try {
                const res = await axios.post((window.API_URL || 'api.php') + '?action=close_order', {
                    order_id: orderId,
                    payment_method: paymentMethod,
                    due_date: dueDate
                });

                if (res.data.success) {
                    await Swal.fire('Sucesso!', 'Conta fechada com sucesso.', 'success');
                    
                    // Reseta estado e volta para mesas
                    this.view = 'tables';
                    this.currentTable = {};
                    this.cart = [];
                    this.showOrderHistory = false;
                    this.fetchTables();
                } else {
                    Swal.fire('Erro', res.data.message || 'Erro ao fechar conta', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Erro', 'Erro ao fechar conta', 'error');
            }
        },

        addToCart(product) {
            this.cart.push(product);
            // Feedback visual tátil (vibração se suportado)
            if (navigator.vibrate) navigator.vibrate(50);
            
            MobileToast.fire({
                icon: 'success',
                title: 'Adicionado!',
                text: product.name
            });
        },

        confirmOrder() {
            this.submitOrder();
        },

        async deleteOrder(orderId, tableNumber) {
             const { isConfirmed } = await Swal.fire({
                title: 'Tem certeza?',
                text: "Essa ação excluirá a comanda e todos os itens!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            });

            if (isConfirmed) {
                try {
                    const res = await axios.post('api.php?action=delete_order', {
                        order_id: orderId
                    });
                    
                    if(res.data.success) {
                        MobileToast.fire({
                            icon: 'success',
                            title: 'Comanda excluída'
                        });
                        this.fetchTables();
                        if(this.currentTable && this.currentTable.number === tableNumber) {
                             this.view = 'tables';
                             this.currentTable = {};
                             this.cart = [];
                             this.showOrderHistory = false;
                        }
                    } else {
                        Swal.fire('Erro', res.data.message || 'Erro ao excluir', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire('Erro', 'Falha na comunicação com o servidor', 'error');
                }
            }
        },

        async submitOrder() {
            if (this.cart.length === 0) return;

            let orderId = this.currentTable.order_id;

            // Feedback de envio
            Swal.fire({
                title: 'Enviando...',
                text: 'Aguarde um momento',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                // Prepara o payload para envio em lote (agrupado por produto)
                const itemsPayload = this.getGroupedCart().map(item => ({
                    product_id: item.id,
                    quantity: item.qty,
                    price: item.price
                }));

                // Envia todos os itens em uma única requisição
                const res = await axios.post('api.php?action=add_items', {
                    order_id: this.currentTable.order_id,
                    items: itemsPayload
                });

                // Fecha o loading
                Swal.close();

                if (res.data.success) {
                    // O backend retorna IDs dos itens criados.
                    // Como agrupamos, teremos menos IDs que itens no cart original se tivéssemos duplicatas.
                    // Mas para o histórico, precisamos refletir o que foi salvo.
                    // O ideal é recarregar o histórico do servidor.
                    
                    this.cart = []; // Limpa o carrinho
                    this.showCartDetails = false;
                    
                    // Abre o modal (Alpine + DOM Direct Fallback)
                    this.showDeliveryModal = true;
                    const modalEl = document.getElementById('delivery-modal');
                    if(modalEl) modalEl.style.display = 'flex';
                    
                    // Atualiza histórico em background
                    this.fetchOrderHistory();
                    this.fetchTables(); // Atualiza resumo da mesa
                } else {
                    throw new Error(res.data.message || 'Erro ao adicionar itens');
                }

            } catch (e) {
                Swal.close(); 
                console.error(e);
                Swal.fire({icon: 'error', title: 'Erro', text: 'Não foi possível enviar o pedido. Tente novamente.'});
            }
        },

        async markSentItemDelivered(index) {
            const item = this.lastSentItems[index];
            if(item.delivered) return; // Já entregue

            try {
                await axios.post((window.API_URL || 'api.php') + '?action=mark_delivered', {
                    item_id: item.item_id
                });
                // Atualiza estado local no modal
                this.lastSentItems[index].delivered = true;
                
                // Feedback visual
                if(navigator.vibrate) navigator.vibrate(50);
            } catch (e) {
                console.error(e);
            }
        },

        async markAllSentDelivered() {
            // Marca todos visualmente primeiro para feedback rápido
            this.lastSentItems.forEach((item, idx) => {
                if(!item.delivered) this.markSentItemDelivered(idx);
            });
        },

        closeDeliveryModal() {
            this.showDeliveryModal = false;
            const modalEl = document.getElementById('delivery-modal');
            if(modalEl) modalEl.style.display = 'none';

            this.lastSentItems = [];
            
            // Volta para a tela inicial (tables)
            this.view = 'tables';
            this.currentTable = {}; // Limpa mesa selecionada
            this.cart = [];
            this.showOrderHistory = false;
            
            // Atualizar mesas e rolar para o topo
            this.fetchTables();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Limpa histórico do navegador
            history.pushState(null, '', 'mobile.php');
        }
    }
}

// Force Uppercase Global Listener
document.addEventListener('input', function(e) {
    const el = e.target;
    // Ignore non-text inputs
    if (el.tagName === 'TEXTAREA' || (el.tagName === 'INPUT' && 
        !['password', 'email', 'number', 'date', 'datetime-local', 'time', 'month', 'week', 'color', 'range', 'file', 'checkbox', 'radio', 'hidden', 'submit', 'button', 'reset', 'image'].includes(el.type))) {
        
        try {
            const start = el.selectionStart;
            const end = el.selectionEnd;
            const upper = el.value.toUpperCase();
            if (el.value !== upper) {
                el.value = upper;
                if (typeof start === 'number' && typeof end === 'number') {
                    el.setSelectionRange(start, end);
                }
            }
        } catch (err) {
            // Ignore errors
        }
    }
}, true);
