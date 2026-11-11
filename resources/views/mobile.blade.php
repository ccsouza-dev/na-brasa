<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espetinhos da Jaque App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        window.API_URL = "{{ route('api.legacy') }}";
    </script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/mobile.css') }}?v={{ time() }}">
</head>
<body class="text-gray-800 font-sans antialiased" x-data="app()">

    <!-- HEADER FIXO -->
    <header class="fixed top-0 left-0 right-0 h-16 glass shadow-sm z-50 flex items-center justify-between px-4 overflow-hidden">
        <div class="flex items-center gap-2" x-show="view !== 'order'">
            <img src="{{ asset('Jaque.png') }}" alt="Espetinhos da Jaque" class="h-12 w-auto object-contain">
        </div>

        <div class="flex-1 flex items-center justify-center">
            <span class="text-sm font-bold text-gray-700 tracking-wide uppercase">Controle de Comandas</span>
        </div>

        <div class="flex items-center gap-3">
            <button @click="openNfceScanner()" class="text-gray-500 hover:text-brand-600 transition">
                <i class="fas fa-camera text-xl"></i>
            </button>
            <button x-show="view === 'tables'" @click="fetchTables()" class="text-gray-500 hover:text-brand-600 transition">
                <i class="fas fa-sync-alt text-xl" :class="{'fa-spin': loading}"></i>
            </button>
        </div>
    </header>

    <!-- CONTAINER PRINCIPAL -->
    <main class="pt-20 pb-24 px-4 min-h-screen">

        <!-- VIEW: SELEÇÃO DE MESA -->
        <div x-show="view === 'tables'" x-transition.opacity>
            <h2 class="text-xl font-bold mb-4 text-gray-700">Escolha uma Mesa</h2>
            
            <div class="grid grid-cols-2 gap-3">
                <template x-for="table in tables" :key="table.number">
                    <div @click="openTable(table)" 
                         class="rounded-2xl p-3 shadow-sm border-2 transition active:scale-95 cursor-pointer relative overflow-hidden flex flex-col justify-between min-h-[11rem] h-auto"
                         :class="{
                            'bg-gray-50 border-gray-200 text-gray-400 opacity-60': table.status === 'free',
                            'bg-blue-50 border-blue-400 text-blue-900': table.status === 'busy'
                         }">
                        
                        <!-- Header: Número e Status Dot -->
                        <div class="flex justify-between items-center w-full mb-2">
                            <div class="flex items-baseline gap-1 overflow-hidden flex-1">
                                <span class="text-lg font-black whitespace-nowrap" 
                                      :class="table.status === 'free' ? 'text-gray-500' : 'text-blue-700'"
                                      x-text="'Mesa ' + table.number"></span>
                            </div>
                            <div class="w-3 h-3 rounded-full shadow-sm flex-shrink-0 ml-1" 
                                 :class="{
                                    'bg-gray-400': table.status === 'free',
                                    'bg-blue-500': table.status === 'busy'
                                 }"></div>
                        </div>

                        <!-- Lista de Clientes/Comandas (Se ocupada) -->
                        <div x-show="table.status === 'busy'" class="flex-1 w-full flex flex-col gap-2 mb-2">
                             <template x-if="table.orders && table.orders.length > 0">
                                 <div class="space-y-2">
                                    <template x-for="order in table.orders">
                                         <div class="bg-white/60 rounded-lg p-2 border border-black/5 backdrop-blur-sm shadow-sm">
                                             <div class="flex justify-between items-center mb-1 border-b border-black/5 pb-1">
                                                 <span class="font-bold text-xs truncate max-w-[60%]" x-text="order.customer_name || 'Cliente'"></span>
                                                 <div class="flex items-center gap-2">
                                                     <button @click.stop="deleteOrder(order.id, table.number)" class="w-6 h-6 rounded-full bg-red-100 border border-red-200 flex items-center justify-center text-[10px] text-red-500 hover:bg-red-200 active:scale-95">
                                                         <i class="fas fa-trash"></i>
                                                     </button>
                                                     <span class="font-black text-xs text-green-700" x-text="'R$ ' + formatMoney(order.total)"></span>
                                                 </div>
                                             </div>
                                             
                                             <!-- Itens -->
                                             <div class="space-y-1 mb-1">
                                                 <template x-for="item in getOrderItems(table, order.id)">
                                                     <div class="flex justify-between text-[10px] text-gray-600 items-center">
                                                         <div class="flex gap-1 overflow-hidden">
                                                             <span x-text="item.qty + 'x'" class="font-bold text-gray-800 shrink-0"></span>
                                                             <span x-text="item.name" class="truncate"></span>
                                                         </div>
                                                         <span class="shrink-0 ml-1 opacity-70 font-medium" x-text="formatMoney(item.subtotal)"></span>
                                                     </div>
                                                 </template>
                                                 <div x-show="getOrderItems(table, order.id).length === 0" class="text-[9px] italic opacity-50">Sem itens</div>
                                             </div>

                                             <!-- Footer do Card Cliente -->
                                             <div class="text-[9px] text-gray-400 text-right pt-0.5 border-t border-black/5">
                                                 Total Itens: <span x-text="getOrderTotalItems(table, order.id)"></span>
                                             </div>
                                         </div>
                                     </template>
                                 </div>
                             </template>
                             <template x-if="!table.orders || table.orders.length === 0">
                                 <div class="flex-1 flex items-center justify-center text-xs opacity-50 italic">
                                     Sem comandas
                                 </div>
                             </template>
                        </div>
                        
                        <!-- Status Livre (Se livre) -->
                        <div x-show="table.status === 'free'" class="flex-1 flex items-center justify-center">
                            <span class="text-xs font-bold uppercase text-gray-400 tracking-widest">Livre</span>
                        </div>

                        <!-- Footer: Total Geral da Mesa -->
                        <div class="flex justify-between items-end w-full pt-2 border-t border-black/10 mt-auto">
                            <span class="text-[10px] font-bold uppercase opacity-70" 
                                  x-text="table.status === 'free' ? '' : 'Ocupada'">
                            </span>
                            <div x-show="table.status === 'busy'" class="text-sm font-black bg-white/50 px-2 py-0.5 rounded text-gray-800 border border-black/5">
                                R$ <span x-text="formatMoney(table.total)"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- VIEW: DENTRO DA MESA (PEDIDO) -->
        <div x-show="view === 'order'" x-transition.opacity x-cloak>
            
            <!-- Info da Mesa -->
            <div class="bg-white p-4 rounded-xl shadow-sm mb-4 border-l-4 border-brand-500 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wide">Mesa Selecionada</p>
                    <h2 class="text-3xl font-black text-brand-600 mb-1" x-text="'Mesa ' + currentTable.number"></h2>
                    <div class="flex items-center gap-2 text-blue-600">
                        <i class="fas fa-user text-sm"></i>
                        <p class="text-lg font-bold" x-text="currentTable.customer || 'Cliente não identificado'"></p>
                    </div>
                </div>
                <div class="text-right">
                     <!-- Se a mesa já existir no backend (status busy), mostramos o status. Se for nova, mostramos 'Nova' -->
                     <span class="px-3 py-1 rounded-full text-xs font-bold" 
                           :class="currentTable.status === 'busy' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'">
                         <i class="fas fa-circle text-[8px] mr-1"></i>
                         <span x-text="currentTable.status === 'busy' ? 'Aberta' : 'Nova'"></span>
                     </span>
                </div>
            </div>

            <div class="flex items-center gap-2 mb-4 overflow-x-auto no-scrollbar">
                <div class="flex flex-col items-start justify-center">
                    <span class="text-[10px] font-bold text-gray-400 uppercase leading-none">Mesa</span>
                    <span class="text-xl font-black text-gray-800 leading-none" x-text="currentTable ? currentTable.number : ''"></span>
                </div>
                
                <template x-for="order in (currentTable && currentTable.orders ? currentTable.orders : [])" :key="order.id">
                    <button @click="selectOrder(order)" 
                            class="px-3 py-1 rounded-lg text-sm font-bold whitespace-nowrap transition border shrink-0 flex flex-col items-start justify-center leading-none h-10 min-w-[80px]"
                            :class="(currentOrder && currentOrder.id === order.id) ? 'bg-brand-600 text-white border-brand-600 shadow-md' : 'bg-gray-50 text-gray-600 border-gray-200'">
                        <span class="truncate max-w-[100px]" x-text="order.customer_name || 'Cliente'"></span>
                        <span class="text-[10px] font-normal" :class="(currentOrder && currentOrder.id === order.id) ? 'text-white/80' : 'text-gray-400'" x-text="'R$ ' + parseFloat(order.total || 0).toFixed(2)"></span>
                    </button>
                </template>
            
                <button @click="openNewOrderForCurrentTable()" class="w-10 h-10 rounded-lg bg-green-50 text-green-600 border border-green-200 flex items-center justify-center shadow-sm shrink-0 active:scale-95 transition">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <!-- Resumo do Consumo (MESA COMPLETA) -->
            <div x-data="{ expanded: false }"
                 class="bg-gray-800 text-white rounded-xl shadow-md mb-6 relative overflow-hidden transition-all duration-300"
                 :class="expanded ? 'pb-2' : ''">
                
                <!-- Cabeçalho do Resumo -->
                <div @click="expanded = !expanded" 
                     class="p-4 flex items-center justify-between cursor-pointer relative z-10">
                    <div>
                        <p class="text-xs text-gray-400 font-bold uppercase mb-1 flex items-center gap-2">
                            <i class="fas fa-receipt"></i> Total
                        </p>
                        <h3 class="text-3xl font-black tracking-tight" x-text="'R$ ' + formatMoney(calculateOrderTotal())"></h3>
                    </div>
                    <div class="flex flex-col items-end">
                         <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mb-1">
                             <i class="fas fa-chevron-down transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                         </div>
                         <span class="text-[10px] opacity-60">Ver Itens</span>
                    </div>
                </div>

                <!-- Lista de Itens do Resumo (Expansível) -->
                <div x-show="expanded" x-collapse class="px-4 border-t border-white/10 pt-2">
                    <template x-for="item in groupedHistory" :key="item.id">
                        <div class="mb-3 pb-2 border-b border-white/5 last:border-0 flex justify-between items-center">
                            <div>
                                <div class="flex items-center gap-2">
                                    <!-- Controles de Quantidade -->
                                    <div class="flex items-center bg-white/10 rounded-lg p-1 gap-2">
                                        <button @click.stop="changeItemQty(item, -1)" class="w-6 h-6 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded transition font-bold">-</button>
                                        <span class="text-sm font-bold w-4 text-center" x-text="item.count"></span>
                                        <button @click.stop="changeItemQty(item, 1)" class="w-6 h-6 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 rounded transition font-bold">+</button>
                                    </div>
                                    <span class="text-sm font-medium ml-1" x-text="item.name"></span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-1 pl-1">
                                    <span x-text="item.status === 'pending' ? 'Pendente' : 'Entregue'" :class="item.status === 'pending' ? 'text-yellow-400' : 'text-green-400'"></span>
                                </div>
                            </div>
                            <strong class="text-sm" x-text="'R$ ' + formatMoney(item.totalPrice)"></strong>
                        </div>
                    </template>
                    
                    <!-- Botão Fechar Conta -->
                    <div class="mt-4 pt-2 border-t border-white/10">
                         <button @click="closeOrderMobile()" 
                                 class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg flex items-center justify-center gap-2 transition active:scale-95">
                             <i class="fas fa-check-circle text-lg"></i>
                             <span>FECHAR CONTA</span>
                         </button>
                    </div>
                </div>
            </div>
            
            <!-- Botão Voltar -->
             <button @click="view = 'tables'; currentTable = {}; cart = []; showOrderHistory = false;" class="mb-6 text-gray-500 font-bold text-sm flex items-center gap-2 hover:text-gray-800 transition px-2">
                <i class="fas fa-arrow-left"></i> Voltar para Mesas
            </button>

            <!-- Menu de Categorias (Horizontal Scroll) -->
            <div class="flex overflow-x-auto pb-2 mb-4 gap-2 no-scrollbar">
                <template x-for="cat in categories" :key="cat">
                    <button @click="activeCategory = cat" 
                            class="px-5 py-2 rounded-full whitespace-nowrap text-sm font-bold transition shadow-sm border"
                            :class="activeCategory === cat ? getCategoryButtonActiveClasses(cat) : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                        <span x-text="getCategoryLabel(cat)"></span>
                    </button>
                </template>
            </div>

            <!-- Grid de Produtos -->
            <div class="pb-24">
                <template x-if="activeCategory !== 'BEBIDAS'">
                    <div class="grid grid-cols-1 gap-3">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div @click="addItemImmediately(product)" class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between active:scale-95 transition-transform duration-100 cursor-pointer">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <template x-if="getProductLogo(product.name)">
                                        <img :src="getProductLogo(product.name)" class="w-10 h-10 rounded-xl object-contain bg-white border border-gray-100 shrink-0" alt="">
                                    </template>
                                    <template x-if="!getProductLogo(product.name)">
                                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-500 flex items-center justify-center font-bold text-lg shrink-0">
                                            <span x-text="product.name.charAt(0)"></span>
                                        </div>
                                    </template>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-gray-800 text-sm truncate" x-text="product.name"></h4>
                                        <p class="text-xs text-gray-400 font-medium" x-text="'R$ ' + formatMoney(product.price)"></p>
                                    </div>
                                </div>
                                <div class="bg-brand-500 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg shadow-brand-200">
                                    <i class="fas fa-plus text-xs"></i>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="activeCategory === 'BEBIDAS'">
                    <div class="space-y-4">
                        <!-- Grupo Cervejas -->
                        <div>
                            <h3 class="font-bold text-gray-600 mb-2 pl-1 text-sm uppercase">Cervejas</h3>
                            <div class="grid grid-cols-1 gap-3">
                                <template x-for="product in filteredProducts.filter(p => getDrinkSubCategory(p) === 'CERVEJA')" :key="product.id">
                                    <div @click="addItemImmediately(product)" class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between active:scale-95 transition-transform duration-100 cursor-pointer">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <template x-if="getProductLogo(product.name)">
                                                <img :src="getProductLogo(product.name)" class="w-10 h-10 rounded-xl object-contain bg-white border border-gray-100 shrink-0" alt="">
                                            </template>
                                            <template x-if="!getProductLogo(product.name)">
                                                <div class="w-10 h-10 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center font-bold text-lg shrink-0">
                                                    <i class="fas fa-beer"></i>
                                                </div>
                                            </template>
                                            <div class="min-w-0">
                                                <h4 class="font-bold text-gray-800 text-sm truncate" x-text="product.name"></h4>
                                                <p class="text-xs text-gray-400 font-medium" x-text="'R$ ' + formatMoney(product.price)"></p>
                                            </div>
                                        </div>
                                        <div class="bg-brand-500 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg shadow-brand-200">
                                            <i class="fas fa-plus text-xs"></i>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Grupo Refrigerante -->
                        <div>
                            <h3 class="font-bold text-gray-600 mb-2 pl-1 text-sm uppercase">Refrigerante</h3>
                            <div class="grid grid-cols-1 gap-3">
                                <template x-for="product in filteredProducts.filter(p => getDrinkSubCategory(p) === 'REFRIGERANTE')" :key="product.id">
                                    <div @click="addItemImmediately(product)" class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between active:scale-95 transition-transform duration-100 cursor-pointer">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <template x-if="getProductLogo(product.name)">
                                                <img :src="getProductLogo(product.name)" class="w-10 h-10 rounded-xl object-contain bg-white border border-gray-100 shrink-0" alt="">
                                            </template>
                                            <template x-if="!getProductLogo(product.name)">
                                                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center font-bold text-lg shrink-0">
                                                    <i class="fas fa-glass-whiskey"></i>
                                                </div>
                                            </template>
                                            <div class="min-w-0">
                                                <h4 class="font-bold text-gray-800 text-sm truncate" x-text="product.name"></h4>
                                                <p class="text-xs text-gray-400 font-medium" x-text="'R$ ' + formatMoney(product.price)"></p>
                                            </div>
                                        </div>
                                        <div class="bg-brand-500 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg shadow-brand-200">
                                            <i class="fas fa-plus text-xs"></i>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Grupo Água / Suco -->
                        <div>
                            <h3 class="font-bold text-gray-600 mb-2 pl-1 text-sm uppercase">Água / Suco</h3>
                            <div class="grid grid-cols-1 gap-3">
                                <template x-for="product in filteredProducts.filter(p => getDrinkSubCategory(p) === 'AGUA_SUCO')" :key="product.id">
                                    <div @click="addItemImmediately(product)" class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between active:scale-95 transition-transform duration-100 cursor-pointer">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <template x-if="getProductLogo(product.name)">
                                                <img :src="getProductLogo(product.name)" class="w-10 h-10 rounded-xl object-contain bg-white border border-gray-100 shrink-0" alt="">
                                            </template>
                                            <template x-if="!getProductLogo(product.name)">
                                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center font-bold text-lg shrink-0">
                                                    <i class="fas fa-tint"></i>
                                                </div>
                                            </template>
                                            <div class="min-w-0">
                                                <h4 class="font-bold text-gray-800 text-sm truncate" x-text="product.name"></h4>
                                                <p class="text-xs text-gray-400 font-medium" x-text="'R$ ' + formatMoney(product.price)"></p>
                                            </div>
                                        </div>
                                        <div class="bg-brand-500 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg shadow-brand-200">
                                            <i class="fas fa-plus text-xs"></i>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </main>



    <script src="{{ asset('assets/js/mobile.js') }}?v={{ time() }}"></script>
</body>
</html>
