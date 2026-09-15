<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movement_items', function (Blueprint $table) {
            // Permite que un ítem sea un servicio y no obligatoriamente un producto
            $table->foreignId('product_id')->nullable()->change();
            $table->foreignId('service_id')->nullable()->after('product_id')->constrained('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movement_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};