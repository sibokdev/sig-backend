<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableConfig extends Model
{
    protected $fillable = ['table_name', 'config'];

    protected $casts = [
        'config' => 'array'
    ];
}
