<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('veiculo_id')->constrained('veiculos')->onDelete('restrict');
            $table->foreignId('mecanico_id')->nullable()->constrained('mecanicos')->onDelete('set null');
            $table->string('status')->default('ABERTA');
            $table->text('descricao_problema');
            $table->text('diagnostico')->nullable();
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_servico');
    }
};