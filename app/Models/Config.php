<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Config extends Model
{
    protected $table = 'layers_config';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $fillable = ['config'];
    protected $casts = ['config' => 'array'];
}
