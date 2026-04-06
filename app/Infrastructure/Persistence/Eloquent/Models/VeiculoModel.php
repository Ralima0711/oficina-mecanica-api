<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CAMADA DE INFRAESTRUTURA — Eloquent Model
 */
class VeiculoModel extends Model
{
    protected $table = 'veiculos';

    protected $fillable = [
        'cliente_id',
        'placa',
        'marca',
        'modelo',
        'ano',
        'cor',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteModel::class, 'cliente_id');
    }
}
