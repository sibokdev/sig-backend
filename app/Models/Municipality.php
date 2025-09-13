<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Municipality extends Model
{
    protected $table = 'municipality';
    protected $primaryKey = 'idmunicipality';
    public $incrementing = true;
    protected $fillable = ['name','states_idstates'];
    public function state(){ return $this->belongsTo(State::class,'states_idstates','idstates'); }
    public function sections(){ return $this->hasMany(Section::class,'municipality_idmunicipality','idmunicipality'); }
}
