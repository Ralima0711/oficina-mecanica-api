<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('unidade_medida', 20);
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('estoque_atual', 10, 3)->default(0);
            $table->decimal('estoque_minimo', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};