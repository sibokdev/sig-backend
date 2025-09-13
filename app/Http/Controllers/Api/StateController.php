<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\State;

class StateController extends Controller
{
    public function index(){ return response()->json(State::with('municipalities')->get()); }
    public function show($id){ return response()->json(State::with('municipalities')->findOrFail($id)); }
}
