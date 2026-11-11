
// --- ADD ITEM TO ORDER LOGIC ---

let productsCache = [];

function openAddItemModal(orderId, tableNum) {
    document.getElementById('add-item-order-id').value = orderId;
    document.getElementById('add-item-table-num').innerText = tableNum;
    
    // Clear search
    document.getElementById('product-search-input').value = '';
    
    // Set default category to BEBIDAS since 'todos' tab is removed
    const defaultCategory = 'BEBIDAS';
    document.getElementById('product-category-filter').value = defaultCategory;

    // Reset tabs visual state
    document.querySelectorAll('.category-tab').forEach(tab => {
        if(tab.dataset.category === defaultCategory) {
            tab.classList.add('btn-primary', 'active');
            tab.classList.remove('btn-outline-primary');
        } else {
            tab.classList.remove('btn-primary', 'active');
            tab.classList.add('btn-outline-primary');
        }
    });

    // Setup tab listeners
    setupCategoryTabs();

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('addItemModal'));
    modal.show();

    // Load products
    loadProductsForSelection();
}

function setupCategoryTabs() {
    const tabs = document.querySelectorAll('.category-tab');
    tabs.forEach(tab => {
        tab.onclick = function() {
            // Update UI
            tabs.forEach(t => {
                t.classList.remove('btn-primary', 'active');
                t.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary', 'active');

            // Update Filter
            document.getElementById('product-category-filter').value = this.dataset.category;
            
            // Reload
            loadProductsForSelection();
        };
    });
}

function loadProductsForSelection() {
    const list = document.getElementById('product-selection-list');
    const search = document.getElementById('product-search-input').value.toLowerCase();
    const category = document.getElementById('product-category-filter').value;

    list.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>';

    // Helper to render
    const render = (products) => {
        let filtered = products.filter(p => {
            const matchSearch = p.name.toLowerCase().includes(search) || (p.ean && p.ean.includes(search));
            
            if (category === 'BEBIDAS') {
                return matchSearch && isDrink(p);
            }
            
            const matchCat = category === 'todos' || p.category === category;
            return matchSearch && matchCat;
        });

        if (filtered.length === 0) {
            list.innerHTML = '<div class="text-center p-3 text-muted">Nenhum produto encontrado.</div>';
            return;
        }

        let html = '';
        
        // Se for categoria BEBIDAS, agrupar visualmente
        if (category === 'BEBIDAS') {
            const groups = {
                'CERVEJA': { label: 'Cervejas', items: [] },
                'REFRIGERANTE': { label: 'Refrigerantes', items: [] },
                'AGUA_SUCO': { label: 'Água / Suco', items: [] },
                'OUTROS': { label: 'Outras Bebidas', items: [] }
            };

            filtered.forEach(p => {
                const sub = getDrinkSubCategory(p);
                if(groups[sub]) groups[sub].items.push(p);
                else groups['OUTROS'].items.push(p);
            });

            // Renderiza grupos
            for (const key in groups) {
                const group = groups[key];
                if (group.items.length > 0) {
                    html += `<h6 class="bg-light p-2 mb-0 fw-bold border-top border-bottom text-uppercase text-primary"><i class="fas fa-wine-bottle me-2"></i>${group.label}</h6>`;
                    group.items.forEach(p => {
                        html += renderProductItem(p);
                    });
                }
            }
        } else {
            // Renderização padrão flat
            filtered.forEach(p => {
                html += renderProductItem(p);
            });
        }
        
        list.innerHTML = html;
    };

    function renderProductItem(p) {
        let onclick = `addItemToOrder(${p.id}, ${p.price}, '${p.name}')`;
        if (p.name.toUpperCase() === 'DIVERSOS' || p.name.toUpperCase() === 'DOSE') {
            onclick = `askPriceAndAdd(${p.id}, '${p.name}')`;
        }
        
        const logo = getProductLogo(p.name);
        const iconHtml = logo 
            ? `<img src="${logo}" alt="${p.name}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: contain; background: #fff; border: 1px solid #eee;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
               <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; display: none;">
                   <i class="fas fa-utensils text-muted"></i>
               </div>`
            : `<div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                   <i class="fas fa-utensils text-muted"></i>
               </div>`;

        return `
            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center" onclick="${onclick}">
                ${iconHtml}
                <div class="flex-grow-1">
                    <div class="fw-bold">${p.name}</div>
                    <small class="text-muted">Estoque: ${p.stock_quantity}</small>
                </div>
                <span class="badge bg-primary rounded-pill">R$ ${parseFloat(p.price).toFixed(2)}</span>
            </button>
        `;
    }

    function getProductLogo(name) {
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
    }

    function isDrink(p) {
        const cat = (p.category || '').toUpperCase();
        const name = (p.name || '').toUpperCase();
        return ['BEBIDA', 'BEBIDAS', 'CERVEJA', 'REFRIGERANTE', 'AGUA', 'SUCO'].includes(cat) || 
               name.includes('DEL VALLE') || 
               name.includes('HEINEKEN') || 
               name.includes('COCA') || 
               name.includes('FANTA');
    }

    function getDrinkSubCategory(p) {
        const cat = (p.category || '').toUpperCase();
        const name = (p.name || '').toUpperCase();

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
    }

    // Use cache if available (optional optimization, but good for speed)
    // Actually, always fetch fresh to get stock? Or fetch once per session?
    // Let's fetch fresh for now to ensure stock is correct.
    axios.get((window.API_URL || 'api.php') + '?action=get_products')
        .then(res => {
            productsCache = res.data || [];
            render(productsCache);
        })
        .catch(err => {
            console.error(err);
            list.innerHTML = '<div class="text-center text-danger p-3">Erro ao carregar produtos.</div>';
        });
}

function addItemToOrder(productId, price, name) {
    const orderId = document.getElementById('add-item-order-id').value;
    
    // Optional: Ask for quantity? For now, add 1.
    // Or maybe show a small popup for quantity.
    // Let's just add 1 for speed, usually POS is click-click-click.
    
    // Add small feedback
    let btn = null;
    let originalContent = '';
    
    if (typeof event !== 'undefined' && event && event.currentTarget) {
        btn = event.currentTarget;
        originalContent = btn.innerHTML;
        btn.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Adicionando...';
        btn.disabled = true;
    }

    axios.post((window.API_URL || 'api.php') + '?action=add_item', {
        order_id: orderId,
        product_id: productId,
        quantity: 1,
        price: price
    }).then(res => {
        if (res.data.success) {
            // Toast success
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: 'Adicionado!',
                text: `${name} (1x)`
            });

            // Reload orders in background
            loadOrders();
        } else {
            Swal.fire('Erro', res.data.message || 'Erro ao adicionar.', 'error');
        }
    }).catch(err => {
        Swal.fire('Erro', 'Falha na comunicação.', 'error');
    }).finally(() => {
        // Reset button state
        if (btn) {
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    });
}

function askPriceAndAdd(productId, name) {
    // Strategy: Temporarily hide the bootstrap modal to avoid focus conflicts completely.
    // This ensures the SweetAlert2 input is fully accessible.
    const modalEl = document.getElementById('addItemModal');
    let modalInstance = null;
    
    if (modalEl) {
        modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
        }
    }

    Swal.fire({
        title: 'Valor do Item',
        text: `Informe o valor para ${name}`,
        input: 'number',
        inputAttributes: {
            step: '0.01',
            min: '0.01'
        },
        showCancelButton: true,
        confirmButtonText: 'Adicionar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const input = Swal.getInput();
            if (input) {
                input.focus();
            }
        },
        inputValidator: (value) => {
            if (!value || value <= 0) {
                return 'Por favor, informe um valor válido!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            addItemToOrder(productId, result.value, name);
        }
        // Restore modal if needed? Or user can reopen it.
        // Usually better to let user reopen to avoid confusion or just keep it closed.
    });
}
