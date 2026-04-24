<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class InsumoModel extends Model
{
    protected $table = 'insumos';

    protected $fillable = [
        'nome',
        'unidade_medida',
        'preco_unitario',
        'estoque_atual',
        'estoque_minimo',
    ];

    protected $casts = [
        'preco_unitario' => 'float',
        'estoque_atual' => 'float',
        'estoque_minimo' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
