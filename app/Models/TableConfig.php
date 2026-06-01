<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableConfig extends Model
{
    protected $fillable = ['table_name', 'config', 'user_in_charge', 'goal', 'national_admin_id'];

    protected $casts = [
        'config' => 'array'
    ];

    public function nationalAdmin()
    {
        return $this->belongsTo(\App\Models\User::class, 'national_admin_id', 'iduserId');
    }
}
