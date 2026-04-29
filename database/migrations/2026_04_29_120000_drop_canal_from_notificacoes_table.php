<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('notificacoes', 'canal')) {
            Schema::table('notificacoes', function (Blueprint $table) {
                $table->dropColumn('canal');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('notificacoes', 'canal')) {
            Schema::table('notificacoes', function (Blueprint $table) {
                $table->string('canal')->default('email');
            });
        }
    }
};
