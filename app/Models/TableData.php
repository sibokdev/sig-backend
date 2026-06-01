<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableData extends Model
{
    protected $fillable = ['config_id', 'data', 'captured_by', 'lat', 'lng'];

    protected $casts = [
        'data' => 'array'
    ];
}
