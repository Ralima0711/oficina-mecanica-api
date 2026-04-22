<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CAMADA DE INFRAESTRUTURA — Eloquent Model
 */
class ClienteModel extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'tipo',
        'documento',
        'telefone',
        'email',
    ];

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServicoModel::class, 'cliente_id');
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(VeiculoModel::class, 'cliente_id');
    }
}
