<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ServicoModel extends Model
{
    protected $table = 'servicos';

    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'categoria',
        'preco_base',
        'duracao_estimada_minutos',
    ];
}
