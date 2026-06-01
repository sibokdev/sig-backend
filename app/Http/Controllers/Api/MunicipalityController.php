<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeoMunicipality;

class MunicipalityController extends Controller
{
    public function index(Request $request){
        $q = GeoMunicipality::query();
        if ($request->cve_ent) {
            $q->where('cve_ent', $request->cve_ent);
        }
        return response()->json($q->orderBy('nombre')->get(['cve_ent', 'cve_mun', 'nombre']));
    }
    public function show($id){ return response()->json(GeoMunicipality::findOrFail($id)); }
}
