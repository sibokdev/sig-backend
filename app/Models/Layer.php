<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Layer extends Model
{
    protected $table = 'layers';
    protected $primaryKey = 'idlayers';
    public $incrementing = true;
    protected $fillable = ['name','goal','geojson','kmlfileLocation','states_idstates','municipality_idmunicipality','section_idsection','id_config'];
    protected $casts = ['geojson' => 'array'];
    public function state(){ return $this->belongsTo(State::class,'states_idstates','idstates'); }
    public function municipality(){ return $this->belongsTo(Municipality::class,'municipality_idmunicipality','idmunicipality'); }
    public function section(){ return $this->belongsTo(Section::class,'section_idsection','idsection'); }
    public function config(){ return $this->belongsTo(Config::class,'id_config','id'); }
    public function assignedTo()
    {
        return $this->belongsToMany(User::class, 'layer_assignments', 'layer_id', 'user_id');
    }
}
