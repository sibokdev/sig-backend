<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\GeoState;

class StateController extends Controller
{
    public function index()
    {
        return response()->json(GeoState::orderBy('nombre')->get(['cve_ent', 'nombre']));
    }

    public function show($cveEnt)
    {
        return response()->json(GeoState::where('cve_ent', $cveEnt)->firstOrFail());
    }
}
