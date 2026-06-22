<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('codigo', 50)->unique();
            $table->text('descricao')->nullable();
            $table->string('categoria', 100)->nullable();
            $table->decimal('preco_base', 10, 2);
            $table->integer('duracao_estimada_minutos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
