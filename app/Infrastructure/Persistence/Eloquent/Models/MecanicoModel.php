<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class MecanicoModel extends Model
{
    protected $table = 'mecanicos';

    protected $fillable = [
        'user_id',
        'especialidade',
    ];
}
