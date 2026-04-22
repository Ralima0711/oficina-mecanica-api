<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('documento', 18)->default('')->after('nome');
            $table->string('tipo', 2)->default('pf')->after('documento');
        });

        DB::table('clientes')->update([
            'documento' => DB::raw('cpf'),
            'tipo' => 'pf',
        ]);

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_cpf_unique');
            $table->dropColumn('cpf');
            $table->unique(['documento', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cpf', 14)->default('')->after('nome');
        });

        DB::table('clientes')->update(['cpf' => DB::raw('documento')]);

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_documento_tipo_unique');
            $table->dropColumn(['documento', 'tipo']);
            $table->unique('cpf');
        });
    }
};