<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ordens_servico')) {
            DB::table('ordens_servico')
                ->where('status', 'ABERTA')
                ->update(['status' => 'RECEBIDA']);

            $this->setStatusDefault('RECEBIDA');
            $this->syncMysqlEnum([
                'RECEBIDA',
                'EM_DIAGNOSTICO',
                'AGUARDANDO_APROVACAO',
                'APROVADA',
                'EM_EXECUCAO',
                'FINALIZADA',
                'ENTREGUE',
            ]);
        }

        if (Schema::hasTable('notificacoes')) {
            DB::table('notificacoes')
                ->where('tipo', 'OS_ABERTA')
                ->update(['tipo' => 'OS_RECEBIDA']);

            DB::table('notificacoes')
                ->where('tipo', 'OS_ABERTA_LEMBRETE_24H')
                ->update(['tipo' => 'OS_RECEBIDA_LEMBRETE_24H']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ordens_servico')) {
            DB::table('ordens_servico')
                ->where('status', 'RECEBIDA')
                ->update(['status' => 'ABERTA']);

            $this->setStatusDefault('ABERTA');
            $this->syncMysqlEnum([
                'ABERTA',
                'EM_DIAGNOSTICO',
                'AGUARDANDO_APROVACAO',
                'APROVADA',
                'EM_EXECUCAO',
                'FINALIZADA',
                'ENTREGUE',
            ]);
        }

        if (Schema::hasTable('notificacoes')) {
            DB::table('notificacoes')
                ->where('tipo', 'OS_RECEBIDA')
                ->update(['tipo' => 'OS_ABERTA']);

            DB::table('notificacoes')
                ->where('tipo', 'OS_RECEBIDA_LEMBRETE_24H')
                ->update(['tipo' => 'OS_ABERTA_LEMBRETE_24H']);
        }
    }

    private function setStatusDefault(string $default): void
    {
        try {
            DB::statement("ALTER TABLE ordens_servico ALTER COLUMN status SET DEFAULT '{$default}'");
            return;
        } catch (\Throwable) {
            // Fallback for SQL dialects that do not support ALTER COLUMN syntax.
        }

        try {
            DB::statement("ALTER TABLE ordens_servico ALTER status SET DEFAULT '{$default}'");
        } catch (\Throwable) {
            // Ignore when default cannot be altered by SQL dialect.
        }
    }

    private function syncMysqlEnum(array $values): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $database = DB::getDatabaseName();
        $column = DB::selectOne(
            "SELECT COLUMN_TYPE AS column_type
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = 'ordens_servico'
               AND COLUMN_NAME = 'status'",
            [$database]
        );

        if (!isset($column->column_type) || stripos((string) $column->column_type, 'enum(') !== 0) {
            return;
        }

        $enumValues = implode(',', array_map(fn(string $value) => "'{$value}'", $values));
        $default = $values[0];

        DB::statement(
            "ALTER TABLE ordens_servico
             MODIFY COLUMN status ENUM({$enumValues}) NOT NULL DEFAULT '{$default}'"
        );
    }
};
