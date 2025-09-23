<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TableConfig;
use App\Models\TableData;
use League\Csv\Reader;

class TableDataController extends Controller
{
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
}
