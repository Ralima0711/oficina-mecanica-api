<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacaoModel extends Model
{
    protected $table = 'notificacoes';

    public const UPDATED_AT = null;

    protected $fillable = [
        'ordem_servico_id',
        'user_id',
        'tipo',
        'canal',
        'status',
        'enviada_em',
        'lida',
    ];

    protected $casts = [
        'enviada_em' => 'datetime',
        'created_at' => 'datetime',
        'lida' => 'boolean',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServicoModel::class, 'ordem_servico_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
