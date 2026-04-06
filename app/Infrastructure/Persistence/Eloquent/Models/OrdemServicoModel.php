<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CAMADA DE INFRAESTRUTURA — Eloquent Model
 * Responsável pela persistência. Completamente separado da entidade de domínio.
 */
class OrdemServicoModel extends Model
{
    protected $table = 'ordens_servico';

    protected $fillable = [
        'cliente_id',
        'veiculo_id',
        'status',
        'descricao_problema',
        'diagnostico',
        'valor_total',
        'concluida_em',
    ];

    protected $casts = [
        'valor_total'  => 'float',
        'concluida_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteModel::class, 'cliente_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(VeiculoModel::class, 'veiculo_id');
    }
}
