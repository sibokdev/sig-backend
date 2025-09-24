<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TableConfig;
use App\Models\TableData;
use League\Csv\Reader;

class TableDataController extends Controller
{
    public function saveTableConfig(Request $request){
        $max = TableConfig::max('id') ?? 0;
        $config = TableConfig::create([
            'id' => $max + 1,
            'table_name' => $request->input('tableName'),
            'config' => json_encode($request->input('config')),
        ]);

        return response()->json(['ok'=>true,'config'=>$config],201);
    }

    public function uploadCsv(Request $request, TableConfig $config)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048'
        ]);

        // Cargar config JSON
        $configJson = json_decode($config->config, true);
        $expectedColumns = collect($configJson['columns'])->pluck('field')->toArray();

        // Leer CSV
        $csv = Reader::createFromPath($request->file('file')->getRealPath(), 'r');
        $csv->setHeaderOffset(0); // la primera fila como encabezados

        $headers = $csv->getHeader();

        // Validar que coincidan los encabezados con la config
        if ($headers !== $expectedColumns) {
            return response()->json([
                'error' => 'Las columnas del CSV no coinciden con la configuración esperada.',
                'expected' => $expectedColumns,
                'received' => $headers
            ], 422);
        }

        $records = iterator_to_array($csv->getRecords());

        // Guardar cada registro
        foreach ($records as $record) {
            TableData::create([
                'config_id' => $config->id,
                'data' => json_encode($record)
            ]);
        }

        return response()->json([
            'message' => 'Archivo CSV procesado y registros guardados correctamente',
            'total_records' => count($records)
        ]);
    }

    public function getTableConfig(){
         $tablconfig = TableConfig::where('id', 1)->get();

         return response()->json($tablconfig);
    }

     public function getData(TableConfig $config)
    {
        $records = TableData::where('config_id', $config->id)->get();

        // Decodificar cada registro antes de devolverlo
        $decoded = $records->map(function ($r) {
            return $r->data;
        });

        return response()->json($decoded);
    }

    public function store(Request $request, TableConfig $config)
    {
        $validated = $request->validate([
            'data' => 'required|array'
        ]);

        $record = TableData::create([
            'config_id' => $config->id,
            'data' => $validated['data']
        ]);

        return response()->json([
            'message' => 'Registro guardado correctamente',
            'record' => $record->data
        ]);
    }

}
