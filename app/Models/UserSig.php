<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserSig extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'iduserId';
    public $incrementing = true;
    protected $fillable = ['username','pwd'];
    public $timestamps = true;
}
