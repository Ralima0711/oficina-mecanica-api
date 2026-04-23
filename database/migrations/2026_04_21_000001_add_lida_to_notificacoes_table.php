<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('notificacoes', 'lida')) {
            return;
        }

        Schema::table('notificacoes', function (Blueprint $table) {
            $table->boolean('lida')->default(false);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('notificacoes', 'lida')) {
            return;
        }

        Schema::table('notificacoes', function (Blueprint $table) {
            $table->dropColumn('lida');
        });
    }
};
