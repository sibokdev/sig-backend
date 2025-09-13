<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Municipality;

class MunicipalityController extends Controller
{
    public function index(Request $request){
        $stateId = $request->query('state_id');
        $q = Municipality::query();
        if($stateId) $q->where('states_idstates',$stateId);
        return response()->json($q->get());
    }
    public function show($id){ return response()->json(Municipality::findOrFail($id)); }
}
