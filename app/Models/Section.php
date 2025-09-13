<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Section extends Model
{
    protected $table = 'section';
    protected $primaryKey = 'idsection';
    public $incrementing = true;
    protected $fillable = ['name','municipality_idmunicipality'];
    public function municipality(){ return $this->belongsTo(Municipality::class,'municipality_idmunicipality','idmunicipality'); }
}
