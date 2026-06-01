<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeoSection;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $q = GeoSection::query();
        if ($request->cve_ent) $q->where('cve_ent', $request->cve_ent);
        if ($request->cve_mun) $q->where('cve_mun', $request->cve_mun);
        return response()->json($q->orderBy('cve_seccion')->get(['cve_ent', 'cve_mun', 'cve_seccion']));
    }
}
