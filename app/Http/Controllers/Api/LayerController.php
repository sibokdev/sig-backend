<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Layer;
use App\Models\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LayerController extends Controller
{
    public function index(){ return response()->json(Layer::all()); }

    public function store(Request $request){
       /* $v = Validator::make($request->all(), [
            'name'=>'nullable|string|max:120',
            'layer'=>'required|file',
            'states_idstates'=>'nullable|integer',
            'municipality_idmunicipality'=>'nullable|integer',
            'section_idsection'=>'nullable|integer',
        ]);
        if($v->fails()) return response()->json(['errors'=>$v->errors()],422);
        $file = $request->file('layer');
        $ext = $file->getClientOriginalExtension();
        $filename = time().'_'.uniqid().'.'.$ext;
        $path = $file->storeAs('layers',$filename,'private');
        $geojson = null;
        if(in_array(strtolower($ext),['geojson','json'])){
            $content = file_get_contents($file->getRealPath());
            $decoded = json_decode($content,true);
            if($decoded!==null) $geojson = $decoded;
        }*/
        $max = Layer::max('idlayers') ?? 0;
        $layer = Layer::create([
            'idlayers' => $max + 1,
            'name' => $request->input('name') ,//?? $file->getClientOriginalName(),
            'geojson' => json_encode($request->input('geojson')),
            'kmlfileLocation' => $request->input('kmlfileLocation') ,
            'states_idstates' => $request->input('states_idstates'),
            'municipality_idmunicipality' => $request->input('municipality_idmunicipality'),
            'section_idsection' => $request->input('section_idsection'),
        ]);
        return response()->json(['ok'=>true,'layer'=>$layer],201);
    }

    public function show($id){ return response()->json(Layer::findOrFail($id)); }

    public function getAllStatesLayers(){
         $states = Layer::select('states_idstates')->distinct()->get();

         return response()->json($states);
    }
    public function getLayersByState($stateid){
         $layers = Layer::where('states_idstates', $stateid)->get();

         return response()->json($layers);
    }

    public function getLayersByMunicipality($stateid,$municipalityid){
         $layers = Layer::where('states_idstates', $stateid)
              ->where('municipality_idmunicipality', $municipalityid)
              ->get();

         return response()->json($layers);
    }

    public function getMinimalAllLayers(){
         $states = Layer::select(['idlayers','name'])->get();

         return response()->json($states);
    }
    public function getMinimalNational(){
         $states = Layer::select(['idlayers','name','states_idstates'])
         ->whereNull('states_idstates')
         ->whereNull('municipality_idmunicipality')   
         ->whereNull('section_idsection')   
         ->get();

         return response()->json($states);
    }

    public function getMinimalStates(){
         $states = Layer::select(['idlayers','name', 'states_idstates'])
         ->whereNotNull('states_idstates')
         ->whereNull('municipality_idmunicipality')   
         ->whereNull('section_idsection')   
         ->get();

         return response()->json($states);
    }

    public function getMinimalMunicipality(){
         $states = Layer::select(['idlayers','name','states_idstates','municipality_idmunicipality' ])
         ->whereNotNull('states_idstates')
         ->whereNotNull('municipality_idmunicipality')   
         ->whereNull('section_idsection')   
         ->get();

         return response()->json($states);
    }

    public function getMinimalSections(){
         $states = Layer::select(['idlayers','name','states_idstates','municipality_idmunicipality' ])
         ->whereNotNull('states_idstates')
         ->whereNotNull('municipality_idmunicipality')   
         ->whereNotNull('section_idsection')   
         ->get();

         return response()->json($states);
    }

    public function getMinimalStateById($stateid){
         $states = Layer::select(['idlayers','name', 'states_idstates'])->where('states_idstates', $stateid)->get();

         return response()->json($states);
    }

    public function getMinimalMunicipalityById($stateid, $municipalityid){
         $states = Layer::select(['idlayers','name','states_idstates','municipality_idmunicipality' ])->where('states_idstates', $stateid)->where('municipality_idmunicipality', $municipalityid)->get();

         return response()->json($states);
    }

    public function destroy($id){
        $layer = Layer::findOrFail($id);
        if($layer->kmlfileLocation && Storage::disk('private')->exists($layer->kmlfileLocation)){
            Storage::disk('private')->delete($layer->kmlfileLocation);
        }
        $layer->delete();
        return response()->json(['ok'=>true]);
    }

    public function downloadFile($id){
        $layer = Layer::findOrFail($id);
        $path = $layer->kmlfileLocation;
        if(!$path || !Storage::disk('private')->exists($path)) return response()->json(['error'=>'file not found'],404);
        return Storage::disk('private')->download($path);
    }

    public function saveLayerConfig(Request $request,$id){
        $layer = Layer::findOrFail($id);
        $max = Config::max('id') ?? 0;
        $config = Config::create([
            'idlayers' => $max + 1,
            'config' => json_encode($request->input('config')),
        ]);
        $layer->id_config = $config->id;
        $layer->save();

        return response()->json(['ok'=>true,'config'=>$config],201);
    }

    public function getConfigById($id){
          $layer = Layer::findOrFail($id);
          return response()->json($layer->config); // usa la relación
    }

}
