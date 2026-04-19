<div class="relative min-h-screen bg-gray-50 flex flex-col">
    
    {{-- NAVBAR ESTÁTICO: SE QUEDA ARRIBA AL HACER SCROLL --}}
    <header class="bg-white shadow-sm border-b border-gray-100 w-full">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-6">
                
                {{-- 1. LOGO Y NOMBRE --}}
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo-imprefot.png') }}" alt="Imprefot" class="h-22 w-auto hidden sm:block">
                    <div class="flex flex-col">
                        <h1 class="text-3xl font-black italic tracking-tighter text-impre-blue leading-none">
                            IMPREFOT<span class="text-impre-orange">09</span>
                        </h1>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.4em] mt-1 ml-1">Tienda en Línea</span>
                    </div>
                </div>

                {{-- 2. INFORMACIÓN DE CONTACTO Y LOGÍSTICA --}}
                <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-4">
                    {{-- Instagram --}}
                    <a href="https://www.instagram.com/imprefot09/" target="_blank" class="flex items-center gap-2 text-pink-600 hover:scale-105 transition-all group">
                        <i class="fab fa-instagram text-xl"></i>
                        <span class="font-black text-[10px] uppercase tracking-tighter text-gray-700">imprefot09</span>
                    </a>

                    {{-- Teléfono --}}
                    <div class="flex items-center gap-2">
                        <i class="fas fa-phone-alt text-green-600 text-lg"></i>
                        <span class="font-black text-[10px] uppercase tracking-tighter text-gray-700">+58 239 248 24 02</span>
                    </div>

                    {{-- Delivery --}}
                    <div class="flex items-center gap-2">
                        <i class="fas fa-motorcycle text-impre-orange text-xl"></i>
                        <span class="font-black text-[10px] uppercase tracking-tighter text-gray-700">Servicios de Delivery</span>
                    </div>

                    {{-- Envíos --}}
                    <div class="flex items-center gap-2">
                        <i class="fas fa-truck text-impre-blue text-xl"></i>
                        <span class="font-black text-[10px] uppercase tracking-tighter text-gray-700">Envíos a Nivel Nacional</span>
                    </div>
                </div>

                {{-- 3. BOTÓN SERVICIOS --}}
                <button wire:click="toggleServicesModal" class="bg-impre-blue text-white px-8 py-3 rounded-2xl font-black hover:bg-cyan-700 transition-all shadow-md active:scale-95 text-xs tracking-widest uppercase cursor-pointer">
                    Servicios
                </button>
            </div>
        </div>
    </header>

    {{-- CUERPO PRINCIPAL --}}
    <main class="flex-grow p-6 max-w-7xl mx-auto w-full mt-4">
        {{-- BUSCADOR --}}
        <div class="mb-10">
            <div class="relative max-w-xl mx-auto">
                <input wire:model.live="search" type="text" placeholder="¿Qué estás buscando?" 
                       class="w-full pl-14 pr-4 py-4 rounded-2xl border-2 border-gray-400 bg-white text-gray-800 shadow-sm focus:border-impre-blue focus:ring-4 focus:ring-cyan-50 transition-all outline-none text-lg">
                <i class="fas fa-search absolute left-5 top-5 text-gray-300 text-2xl"></i>
            </div>
        </div>

        {{-- GRID DE PRODUCTOS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            @forelse($products as $product)
                <div class="bg-white rounded-[2rem] shadow-sm border border-gray-200 overflow-hidden flex flex-col group hover:shadow-2xl transition-all duration-500">
                    <div class="relative h-52 bg-gray-50 flex items-center justify-center border-b border-gray-100 overflow-hidden">
                        <div class="absolute top-4 right-4 z-10">
                            <span class="{{ $product->stock > 0 ? 'bg-green-500' : 'bg-red-500' }} text-white px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg italic">
                                {{ $product->stock > 0 ? 'Stock: ' . $product->stock : 'Agotado' }}
                            </span>
                        </div>
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        @else
                            <i class="fas fa-image text-4xl text-gray-200"></i>
                        @endif
                    </div>

                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="font-black text-gray-800 text-lg leading-tight h-12 overflow-hidden uppercase tracking-tighter">{{ $product->name }}</h3>
                        <p class="text-impre-blue font-black text-3xl mt-2 italic">${{ number_format($product->price, 2) }}</p>
                        
                        {{-- El Botón: Ahora pasamos el ID numérico --}}
                        <button wire:click="showDescription({{ $product->id }})" 
                                class="text-left text-[10px] font-black text-gray-400 mt-4 flex items-center gap-2 hover:text-impre-orange transition-colors uppercase tracking-widest cursor-pointer">
                            <i class="fas fa-plus-circle text-sm"></i> Detalles del producto
                        </button>

                        {{-- La Condición: Comparamos IDs, que nunca fallan --}}
                        @if($selectedProductId == $product->id)
                            <div class="mt-4 p-4 bg-gray-50 rounded-2xl text-xs text-gray-600 border border-gray-100 animate-fade-in font-medium whitespace-pre-line">
                                {!! nl2br(e($product->description)) !!}
                            </div>
                        @endif

                        <div class="mt-auto pt-6">
                            <button wire:click="addToCart({{ $product->id }})" 
                                    {{ $product->stock <= 0 ? 'disabled' : '' }}
                                    class="w-full {{ $product->stock > 0 ? 'bg-impre-blue hover:bg-cyan-700 cursor-pointer' : 'bg-gray-200 cursor-not-allowed text-gray-400' }} text-white font-black py-4 rounded-2xl transition-all active:scale-95 shadow-md flex items-center justify-center gap-3">
                                <i class="fas fa-cart-plus"></i> AÑADIR AL PEDIDO
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200 text-gray-300 font-black uppercase tracking-[0.3em]">Sin resultados</div>
            @endforelse
        </div>
    </main>

    {{-- PUBLICIDAD ST MONKEY (LENGUAJE ESPECÍFICO) --}}
    <section class="max-w-7xl mx-auto w-full px-6 mb-12 mt-8">
        <div class="bg-gradient-to-r from-gray-900 to-blue-900 rounded-[2.5rem] p-1 shadow-2xl overflow-hidden group">
            <div class="bg-white rounded-[2.4rem] p-8 flex flex-col md:flex-row items-center gap-8 relative">
                <img src="{{ asset('images/logo-stmonkey.png') }}" alt="ST Monkey Logo" class="h-28 w-28 rounded-2xl shadow-xl border-4 border-white group-hover:rotate-3 transition-transform">
                
                <div class="text-center md:text-left z-10 flex-1">
                    <h4 class="text-2xl font-black text-gray-900 tracking-tighter uppercase leading-none">¿Te gustaría tener un Sitio Web como este para tu tienda?</h4>
                    <p class="text-gray-600 mt-2 text-lg font-medium italic">En <span class="text-blue-600 font-black">Soluciones Tecnológicas Monkey</span> lo hacemos realidad.</p>
                
                    <a href="https://wa.me/584124923958?text={{ urlencode('¡Hola! Vengo de la web de Imprefot y me gustaría cotizar un sitio web.') }}" 
                       target="_blank" 
                       class="mt-6 inline-flex items-center gap-3 bg-blue-600 text-white px-8 py-4 rounded-full font-black shadow-lg hover:bg-blue-700 hover:scale-105 transition-all text-sm uppercase tracking-widest active:scale-95">
                        <i class="fab fa-whatsapp text-2xl"></i> 
                        Contáctanos al +58 412 492 39 58
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER CON DIRECCIÓN ESPECÍFICA --}}
    <footer class="bg-gray-900 text-white py-12 px-6">
        <div class="max-w-7xl mx-auto flex flex-col items-center">
            {{-- BLOQUE DE UBICACIÓN --}}
            <div class="bg-gray-800 p-8 rounded-3xl border border-gray-700 max-w-3xl w-full text-center mb-10 shadow-inner">
                <h5 class="text-impre-orange font-black mb-4 uppercase tracking-[0.3em] text-[10px]">Ubicación de la Tienda</h5>
                <p class="text-gray-200 leading-relaxed italic text-base">
                    Avenida Tosta García, con calle 8 Zamora, justo al frente de Repuestos Guárico, Charallave, Estado Miranda.
                </p>
            </div>

            {{-- CRÉDITOS Y DERECHOS UNIFICADOS --}}
            <div class="text-center space-y-4">
                <p class="text-[10px] text-gray-400 uppercase tracking-[0.4em]">
                    &copy; {{ date('Y') }} <span class="text-white font-bold">Inversiones Imprefot 09, C.A.</span> - Todos los derechos reservados.
                </p>
            
                <div class="h-px w-20 bg-gray-700 mx-auto"></div> {{-- Separador sutil --}}

                <p class="text-[9px] text-gray-500 uppercase tracking-[0.4em]">
                    Sitio web diseñado por <span class="text-white font-black tracking-normal">Soluciones Tecnológicas Monkey</span>
                </p>
            </div>
        </div>
    </footer>

    {{-- MODAL DE SERVICIOS --}}
    @if($showServicesModal)
    <div class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div wire:click="toggleServicesModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative bg-white rounded-[2.5rem] w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col shadow-2xl">
            <div class="p-8 border-b flex justify-between items-center bg-gray-50">
                <h2 class="text-3xl font-black text-impre-blue uppercase italic">Nuestros Servicios</h2>
                <button wire:click="toggleServicesModal" class="text-gray-400 hover:text-red-500 text-5xl font-light cursor-pointer">&times;</button>
            </div>
            <div class="p-8 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($services as $service)
                    <div class="flex gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <img src="{{ asset('storage/'.$service->image) }}" class="w-24 h-24 object-cover rounded-xl shadow-md border-2 border-white">
                        <div>
                            <h3 class="font-black text-gray-900 uppercase leading-tight">{{ $service->name }}</h3>
                            <p class="text-[11px] text-gray-500 mt-2 font-medium leading-relaxed italic">{{ $service->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL DE CARRITO / PEDIDO --}}
    @if($showModal)
    <div class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div wire:click="toggleModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative bg-white rounded-[3rem] w-full max-w-xl overflow-hidden shadow-2xl z-[210]">
            <div class="p-10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-3xl font-black text-gray-900 tracking-tighter uppercase italic">Pedido Actual</h3>
                    <button wire:click="toggleModal" class="text-gray-300 hover:text-red-500 text-4xl cursor-pointer">&times;</button>
                </div>
                <div class="space-y-4 max-h-[45vh] overflow-y-auto pr-2">
                    @foreach($cartItems as $item)
                        <div class="flex justify-between items-center bg-gray-50 p-6 rounded-[2rem] border border-gray-100">
                            <div class="flex-1">
                                <p class="font-black text-gray-800 text-sm uppercase">{{ $item->name }}</p>
                                <p class="text-[10px] text-gray-400 font-bold mt-1 italic">${{ number_format($item->price, 2) }} UNITARIO</p>
                            </div>
                            <div class="flex items-center gap-3 ml-4">
                                <button wire:click="decrement({{ $item->id }})" class="bg-white border-2 border-gray-100 h-10 w-10 rounded-full font-black text-gray-400 hover:text-gray-900 transition-colors cursor-pointer">-</button>
                                <span class="font-black text-xl w-6 text-center">{{ $item->quantity }}</span>
                                <button wire:click="increment({{ $item->id }})" class="bg-white border-2 border-gray-100 h-10 w-10 rounded-full font-black text-gray-400 hover:text-gray-900 transition-colors cursor-pointer">+</button>
                                <button wire:click="removeItem({{ $item->id }})" class="ml-4 text-red-300 hover:text-red-500 text-xl cursor-pointer"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-10 pt-8 border-t-2 border-dashed border-gray-100 flex justify-between items-center font-black italic">
                    <span class="text-gray-400 uppercase text-xs tracking-widest">Inversión Total</span>
                    <span class="text-impre-blue text-4xl">${{ number_format($cartTotal, 2) }}</span>
                </div>
            </div>
            <div class="bg-gray-50 p-10">
                <button wire:click="checkout" class="w-full bg-[#25D366] text-white py-6 rounded-[2rem] font-black text-xl hover:bg-green-600 transition-all flex items-center justify-center gap-4 shadow-xl active:scale-95 group cursor-pointer">
                     <i class="fab fa-whatsapp text-3xl group-hover:scale-110 transition-transform"></i>
                     ENVIAR POR WHATSAPP
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- BOTÓN FLOTANTE DEL CARRITO (Solo visible si hay productos) --}}
    @if($cartCount > 0)
    <div class="fixed bottom-10 right-10 z-[150]">
        <button wire:click="toggleModal" class="bg-impre-orange text-white px-8 py-5 rounded-full shadow-2xl flex items-center gap-4 hover:scale-110 active:scale-95 transition-all border-4 border-white cursor-pointer group">
            <div class="relative">
                <i class="fas fa-shopping-basket text-2xl group-hover:rotate-12 transition-transform"></i>
                <span class="absolute -top-4 -right-4 bg-white text-impre-orange text-[10px] font-black rounded-full h-7 w-7 flex items-center justify-center shadow-lg border-2 border-impre-orange italic">{{ $cartCount }}</span>
            </div>
            <span class="font-black text-lg uppercase tracking-widest italic">Mi Pedido</span>
        </button>
    </div>
    @endif

</div>