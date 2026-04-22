<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsumoOsModel extends Model
{
    protected $table = 'insumos_os';

    public $timestamps = false;

    protected $fillable = [
        'ordem_servico_id',
        'insumo_id',
        'quantidade',
        'preco_unitario',
        'subtotal',
    ];

    protected $casts = [
        'quantidade' => 'float',
        'preco_unitario' => 'float',
        'subtotal' => 'float',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServicoModel::class, 'ordem_servico_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(InsumoModel::class, 'insumo_id');
    }
}
