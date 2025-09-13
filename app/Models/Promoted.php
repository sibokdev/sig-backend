<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Promoted extends Model
{
    protected $table = 'promoted';
    protected $fillable = ['nombre','apellidop','apellidom','direccion','sexo','edad','latitud','longitud','fechaRecepcion','fotografiaBeneficiario','imagenFirma','fotografiaIneFront','fotografiaIneBack','claveIne','seccion','promotor','telefono','tipoApoyo','militante','correo','facebook','twitter','instagram','cantidad','unidadMedida','fotografiaInicio','fotografiaDurante1','fotografiaDurante2','fotografiaDurante3','fotografiaDurante4','fotografiaDurante5','fotografiaFinal'];
}
