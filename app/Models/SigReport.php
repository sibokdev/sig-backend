<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SigReport extends Model
{
    protected $table = 'sigreports';
    protected $primaryKey = 'idsigreports';
    public $incrementing = true;
    protected $fillable = ['reportname','filedir','datecreated'];
}
