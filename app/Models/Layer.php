<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Layer extends Model
{
    protected $table = 'layers';
    protected $primaryKey = 'idlayers';
    public $incrementing = true;
    protected $fillable = ['name','geojson','kmlfileLocation','states_idstates','municipality_idmunicipality','section_idsection'];
    protected $casts = ['geojson' => 'array'];
    public function state(){ return $this->belongsTo(State::class,'states_idstates','idstates'); }
    public function municipality(){ return $this->belongsTo(Municipality::class,'municipality_idmunicipality','idmunicipality'); }
    public function section(){ return $this->belongsTo(Section::class,'section_idsection','idsection'); }
}
