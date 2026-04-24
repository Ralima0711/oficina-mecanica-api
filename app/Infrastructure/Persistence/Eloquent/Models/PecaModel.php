<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PecaModel extends Model
{
    protected $table = 'pecas';

    protected $fillable = [
        'nome',
        'codigo',
        'categoria',
        'preco_unitario',
        'estoque_atual',
        'estoque_minimo',
    ];

    protected $casts = [
        'preco_unitario' => 'float',
        'estoque_atual' => 'integer',
        'estoque_minimo' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
