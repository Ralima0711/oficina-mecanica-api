<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemOsModel extends Model
{
    protected $table = 'itens_os';

    public $timestamps = false;

    protected $fillable = [
        'ordem_servico_id',
        'peca_id',
        'quantidade',
        'preco_unitario',
        'subtotal',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'preco_unitario' => 'float',
        'subtotal' => 'float',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServicoModel::class, 'ordem_servico_id');
    }

    public function peca(): BelongsTo
    {
        return $this->belongsTo(PecaModel::class, 'peca_id');
    }
}
