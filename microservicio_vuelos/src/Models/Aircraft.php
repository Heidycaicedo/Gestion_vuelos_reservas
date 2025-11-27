<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aircraft extends Model
{
    protected $table = 'naves';
    protected $fillable = ['name', 'capacity', 'model'];
    public $timestamps = true;
}
