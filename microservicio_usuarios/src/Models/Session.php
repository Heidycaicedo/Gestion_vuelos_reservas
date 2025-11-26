<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sesiones';
    protected $fillable = ['usuario_id', 'token', 'fecha_expiracion'];
}
