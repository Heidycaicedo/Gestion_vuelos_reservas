<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    protected $table = 'vuelos';
    protected $fillable = ['numero_vuelo', 'nave_id', 'origen', 'destino', 'fecha_salida', 'fecha_llegada', 'asientos_disponibles'];
}
