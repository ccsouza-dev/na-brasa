<?php
// Verificação automática de instalação
include_once __DIR__ . '/../legacy/config.php';
try {
    $test_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($test_conn->connect_error && strpos($test_conn->connect_error, 'Unknown database') !== false) {
        header("Location: install.php");
        exit;
    }
    $test_conn->close();
} catch (Exception $e) {
    // Falha silenciosa
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espetinhos da Jaque App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/js/tailwind-config.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/mobile.css?v=<?= time() ?>">
</head>
<body class="text-gray-800 font-sans antialiased" x-data="app()">

    <!-- HEADER FIXO -->
    <header class="fixed top-0 left-0 right-0 h-16 glass shadow-sm z-50 flex items-center justify-between px-4 overflow-hidden">
        <div class="flex items-center gap-2" x-show="view !== 'order'">
            <img src="Jaque.png" alt="Espetinhos da Jaque" class="h-12 w-auto object-contain">
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
                            'bg-blue-100 border-blue-200 text-blue-900': table.status === 'busy' && table.sla_status === 'completed',
                            'bg-green-100 border-green-200 text-green-900': table.status === 'busy' && table.sla_status === 'ok',
                            'bg-yellow-100 border-yellow-200 text-yellow-900 animate-pulse': table.status === 'busy' && table.sla_status === 'warning',
                            'bg-red-100 border-red-200 text-red-900 animate-pulse': table.status === 'busy' && table.sla_status === 'critical'
                         }">
                        
                        <!-- Header: Número e Status Dot -->
                        <div class="flex justify-between items-center w-full mb-2">
                            <div class="flex items-baseline gap-1 overflow-hidden flex-1">
                                <span class="text-lg font-black whitespace-nowrap" 
                                      :class="table.status === 'free' ? 'text-gray-500' : 'text-current'"
                                      x-text="'Mesa ' + table.number"></span>
                            </div>
                            <div class="w-3 h-3 rounded-full shadow-sm flex-shrink-0 ml-1" 
                                 :class="{
                                    'bg-gray-400': table.status === 'free',
                                    'bg-blue-500': table.status === 'busy' && table.sla_status === 'completed',
                                    'bg-green-500': table.status === 'busy' && table.sla_status === 'ok',
                                    'bg-yellow-500': table.status === 'busy' && table.sla_status === 'warning',
                                    'bg-red-500': table.status === 'busy' && table.sla_status === 'critical'
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
                                  x-text="table.status === 'free' ? '' : (table.sla_status === 'critical' ? 'Atrasado' : (table.sla_status === 'warning' ? 'Atenção' : (table.sla_status === 'completed' ? 'Entregue' : 'Ocupada')))">
                            </span>
                            <div x-show="table.status === 'busy'" class="text-sm font-black bg-white/50 px-2 py-0.5 rounded text-gray-800 border border-black/5">
                                R$ <span x-text="formatMoney(table.total)"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- VIEW: ESTOQUE (REMOVIDO) -->

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
                            <i class="fas fa-receipt"></i> Total da Mesa
                        </p>
                        <h3 class="text-3xl font-black tracking-tight" x-text="'R$ ' + formatMoney(tableTotal)"></h3>
                    </div>
                    <div class="flex flex-col items-end">
                         <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mb-1">
                             <i class="fas fa-chevron-down transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                         </div>
                         <span class="text-[10px] opacity-60">Ver Detalhes</span>
                    </div>
                </div>

                <!-- Lista de Itens do Resumo (Expansível) -->
                <div x-show="expanded" x-collapse class="px-4 border-t border-white/10 pt-2">
                    <template x-for="summary in tableSummary" :key="summary.id">
                        <div class="mb-3 pb-2 border-b border-white/5 last:border-0">
                            <div class="flex justify-between items-center mb-1">
                                <strong class="text-sm text-brand-300" x-text="summary.name"></strong>
                                <strong class="text-sm" x-text="'R$ ' + formatMoney(summary.total)"></strong>
                            </div>
                            <!-- Itens individuais -->
                            <div class="pl-2 border-l-2 border-white/10 space-y-1">
                                <template x-for="item in summary.items">
                                    <div class="flex justify-between text-xs opacity-70">
                                        <span x-text="item.qty + 'x ' + item.name"></span>
                                        <span x-text="formatMoney(item.subtotal)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
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
                        <span x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></span>
                    </button>
                </template>
            </div>

            <!-- Grid de Produtos -->
            <div class="grid grid-cols-1 gap-3 pb-24">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div @click="addToCart(product)" 
                         class="p-3 rounded-2xl shadow-sm border flex flex-col justify-between h-32 relative overflow-hidden group active:scale-95 transition"
                         :class="getCategoryCardClasses(activeCategory)">
                        
                        <!-- Efeito Ripple/Highlight ao clicar -->
                        <div class="absolute inset-0 bg-brand-50 opacity-0 group-active:opacity-100 transition-opacity"></div>
                        
                        <div>
                            <h3 class="font-bold text-gray-800 leading-tight mb-1 line-clamp-2" x-text="product.name"></h3>
                            <p class="text-[10px] text-gray-400" x-text="product.description || product.category"></p>
                        </div>
                        <div class="flex justify-between items-end mt-2 relative z-10">
                            <span class="font-black text-lg text-brand-600" x-text="'R$ ' + formatMoney(product.price)"></span>
                            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center shadow-sm">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>

                        <!-- Badge de Qtd no Carrinho -->
                        <div x-show="getItemQty(product.id) > 0" 
                             class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center shadow-md animate-bounce-in">
                            <span x-text="getItemQty(product.id)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </main>

    <!-- BARRA INFERIOR FLUTUANTE (CARRINHO) -->
    <div x-show="view === 'order' && cart.length > 0" 
         class="fixed bottom-0 left-0 right-0 p-4 z-40 bg-gradient-to-t from-white via-white to-transparent pb-6"
         x-transition.move.up>
        
        <!-- Detalhes do Carrinho (Expansível) -->
        <div x-show="showCartDetails" x-collapse class="bg-white rounded-t-2xl shadow-[0_-5px_20px_-5px_rgba(0,0,0,0.1)] border-x border-t border-gray-100 mb-[-1px] overflow-hidden">
             <div class="p-4 max-h-[50vh] overflow-y-auto">
                 <div class="flex justify-between items-center mb-3">
                     <h3 class="font-bold text-gray-700">Seu Pedido</h3>
                     <button @click="showCartDetails = false" class="text-gray-400 p-2"><i class="fas fa-times"></i></button>
                 </div>
                 
                 <template x-for="item in getGroupedCart()" :key="item.id">
                     <div class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                         <div class="flex items-center gap-3">
                             <div class="flex items-center gap-2 bg-gray-100 rounded-lg p-1">
                                 <button @click="removeFromCart(item.id)" class="w-6 h-6 flex items-center justify-center text-red-500 font-bold bg-white rounded shadow-sm">-</button>
                                 <span class="font-bold text-sm w-4 text-center" x-text="item.qty"></span>
                                 <button @click="addToCart(item)" class="w-6 h-6 flex items-center justify-center text-green-600 font-bold bg-white rounded shadow-sm">+</button>
                             </div>
                             <span class="text-sm font-medium text-gray-700 truncate max-w-[120px]" x-text="item.name"></span>
                         </div>
                         <span class="font-bold text-sm" x-text="'R$ ' + formatMoney(parseFloat(item.price) * item.qty)"></span>
                     </div>
                 </template>
             </div>
        </div>

        <!-- Botão de Ação Principal -->
        <button @click="showCartDetails ? submitOrder() : showCartDetails = true" 
                :disabled="submitting"
                :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                class="w-full bg-brand-600 text-white font-bold rounded-2xl shadow-lg shadow-brand-200 py-4 px-6 flex justify-between items-center active:scale-95 transition relative z-50 overflow-hidden">
            <div class="flex items-center gap-3">
                <div class="bg-black/20 w-8 h-8 rounded-full flex items-center justify-center text-sm font-black">
                    <span x-text="cart.length"></span>
                </div>
                <span x-text="showCartDetails ? 'Confirmar Pedido' : 'Ver Carrinho'"></span>
            </div>
            <span class="text-xl font-black" x-text="'R$ ' + formatMoney(cartTotal)"></span>
        </button>
    </div>

    <!-- BOTÃO FLUTUANTE: VER PEDIDOS JÁ ENVIADOS -->
    <div x-show="view === 'order' && cart.length === 0 && orderHistory.length > 0" 
         class="fixed bottom-6 right-4 z-30">
        <button @click="showOrderHistory = true" class="bg-white text-brand-600 shadow-lg border border-brand-100 p-3 rounded-full flex items-center gap-2 pr-4 active:scale-90 transition">
            <div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center">
                <i class="fas fa-list-ul"></i>
            </div>
            <span class="font-bold text-sm">Ver Comanda</span>
        </button>
    </div>

    <!-- DRAWER: HISTÓRICO DA COMANDA -->
    <div x-show="showOrderHistory" class="fixed inset-0 z-50 flex justify-end" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showOrderHistory = false" x-transition.opacity></div>
        
        <div class="bg-white w-full max-w-md h-full shadow-2xl relative z-10 flex flex-col transform transition-transform duration-300"
             x-transition:enter="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="translate-x-0"
             x-transition:leave-end="translate-x-full">
             
             <div class="p-4 bg-brand-600 text-white flex justify-between items-center shadow-md shrink-0">
                 <div>
                     <p class="text-xs text-brand-200 uppercase font-bold">Comanda da Mesa</p>
                     <h2 class="text-xl font-black" x-text="'Mesa ' + currentTable.number"></h2>
                 </div>
                 <button @click="showOrderHistory = false" class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                     <i class="fas fa-times"></i>
                 </button>
             </div>
             
            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                <template x-for="item in groupedHistory" :key="item.name + item.status">
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-gray-800">
                               <span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-sm mr-1" x-text="item.count + 'x'"></span>
                               <span x-text="item.name"></span>
                            </h3>
                            <div class="flex items-center gap-2 text-xs mt-1 text-gray-600">
                                <span class="font-bold" x-text="'R$ ' + formatMoney(item.totalPrice)"></span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button @click="updateItemQuantity(item.ids[0], item.count, item.name)" 
                                    class="w-10 h-10 rounded-full bg-white border shadow-sm text-blue-500 hover:bg-blue-50 flex items-center justify-center transition active:scale-90">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deleteOrderItem(item.ids[0], item.name)" 
                                    class="w-10 h-10 rounded-full bg-white border shadow-sm text-red-500 hover:bg-red-50 flex items-center justify-center transition active:scale-90">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            
            <div class="p-4 border-t bg-gray-50 text-center">
                <p class="text-sm text-gray-500">Total Parcial: <strong class="text-gray-800">R$ <span x-text="formatMoney(calculateOrderTotal())"></span></strong></p>
            </div>
        </div>
    </div>

    <div x-show="showNfceScanner" class="fixed inset-0 z-[80] bg-black/90 flex flex-col" x-cloak>
        <div class="flex items-center justify-between px-4 py-3 text-white">
            <div class="flex items-center gap-2">
                <i class="fas fa-receipt text-lg"></i>
                <span class="font-bold text-sm">Ler QR da NFC-e</span>
            </div>
            <button @click="closeNfceScanner()" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 flex flex-col items-center justify-center px-4">
            <div class="w-full max-w-xs aspect-square bg-black rounded-2xl overflow-hidden border border-white/20 flex items-center justify-center">
                <div id="mobile-nfce-reader" class="w-full h-full"></div>
            </div>
            <p class="text-xs text-gray-300 mt-4 text-center px-6">Aponte a câmera para o QR Code da NFC-e. A nota será enviada como pendente para o caixa.</p>
        </div>
    </div>

    <script src="assets/js/mobile.js?v=<?= time() ?>"></script>
</body>
</html>
