<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pecas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('codigo')->unique();
            $table->string('categoria')->nullable();
            $table->decimal('preco_unitario', 10, 2);
            $table->integer('estoque_atual')->default(0);
            $table->integer('estoque_minimo')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pecas');
    }
};