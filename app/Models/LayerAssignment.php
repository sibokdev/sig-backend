<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayerAssignment extends Model
{
    protected $table = 'layer_assignments';

    protected $fillable = ['layer_id', 'user_id'];

    public function layer()
    {
        return $this->belongsTo(Layer::class, 'layer_id', 'idlayers');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'iduserId');
    }
}
