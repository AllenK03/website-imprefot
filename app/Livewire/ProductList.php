<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Darryldecode\Cart\Facades\CartFacade as Cart;

class ProductList extends Component
{
    public $search = ''; // Para el filtro
    public $showModal = false; // Controla si se ve el resumen
    public $selectedProductId = null;
    public $showServicesModal = false;

    #[Layout('layouts.app')]

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        $cartItem = Cart::get($productId);
        $currentQtyInCart = $cartItem ? $cartItem->quantity : 0;

        // VALIDACIÓN DE STOCK
        if ($product->stock > $currentQtyInCart) {
            Cart::add([
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
            ]);
        } else {
            // Aquí podrías lanzar una alerta
            session()->flash('error-' . $productId, 'Sin stock suficiente');
        }
    }

    public function increment($id) {
        $product = Product::find($id);
        $cartItem = Cart::get($id);
        if ($product->stock > $cartItem->quantity) {
            Cart::update($id, ['quantity' => 1]);
        }
    }

    public function decrement($id) { Cart::update($id, ['quantity' => -1]); }
    public function removeItem($id) { Cart::remove($id); }
    public function toggleModal() { $this->showModal = !$this->showModal; }
    
    // Función para mostrar/ocultar descripción
    public function showDescription($productId) { 
        $this->selectedProductId = ($this->selectedProductId == $productId) ? null : $productId; 
    }    

    public function render()
    {
        // FILTRO POR NOMBRE
        $products = Product::where('is_active', true)
            ->where('name', 'like', '%' . $this->search . '%')
            ->get();

        return view('livewire.product-list', [
            'products' => $products,
            'services' => \App\Models\Service::where('is_active', true)->get(),
            'cartItems' => Cart::getContent()->sortBy('id'),
            'cartTotal' => Cart::getTotal(),
            'cartCount' => Cart::getContent()->count()
        ]);
    }

    public function toggleServicesModal() { $this->showServicesModal = !$this->showServicesModal; }

    public function checkout()
    {
        $cartItems = Cart::getContent()->sortBy('id');
        $cartTotal = Cart::getTotal();
        $num = "+584241106067"; // Asegúrate de que sea el número correcto

        // 1. Construimos el mensaje (igual que lo hacíamos en la vista)
        $texto = "¡Hola Imprefot! Quisiera cotizar:\n\n";
        foreach($cartItems as $item) {
            $texto .= "• " . $item->quantity . "x " . $item->name . " ($" . number_format($item->price * $item->quantity, 2) . ")\n";
        }
        $texto .= "\n*Total: $" . number_format($cartTotal, 2) . "*";
    
        $url = "https://wa.me/" . $num . "?text=" . urlencode($texto);

        // 2. VACIAR EL CARRITO
        Cart::clear();

        // 3. Cerrar el modal
        $this->showModal = false;

        // 4. Redirigir a WhatsApp
        return redirect()->away($url);
}
}