<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_inventories', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->json('snapshot_data'); // Congela el estado (id, nombre, stock, precio) de cada producto de la tabla products
            $table->integer('total_products');
            $table->integer('total_stock');
            $table->decimal('total_value', 12, 2);
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'month']); // Asegura que solo exista un cierre por mes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_inventories');
    }
};