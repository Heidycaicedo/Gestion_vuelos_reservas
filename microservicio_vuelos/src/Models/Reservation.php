<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservas';
    protected $fillable = ['usuario_id', 'vuelo_id', 'numero_asiento', 'estado'];
}
