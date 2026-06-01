<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TableConfig;
use App\Models\TableData;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function __construct(private WhatsappService $wa) {}

    /**
     * GET /whatsapp/status
     * Returns all session states.
     */
    public function status()
    {
        return response()->json($this->wa->status());
    }

    /**
     * POST /whatsapp/sessions
     * Body: { id: "phone1" }
     * Create or reconnect a session.
     */
    public function createSession(Request $request)
    {
        $request->validate(['id' => 'required|string|max:64|regex:/^[a-zA-Z0-9_-]+$/']);
        try {
            return response()->json($this->wa->createSession($request->id));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    /**
     * DELETE /whatsapp/sessions/{id}
     * Disconnect and remove a session.
     */
    public function removeSession(string $id)
    {
        $this->wa->removeSession($id);
        return response()->json(['ok' => true, 'id' => $id]);
    }

    /**
     * POST /whatsapp/campaign/{config}
     *
     * Sends a text message to every phone number stored in table_data
     * for the given config. Auto-detects the phone field from the config
     * (first field with type='phone') or accepts an explicit phoneField param.
     *
     * Body: { message: string, phoneField?: string }
     */
    public function sendCampaign(Request $request, TableConfig $config)
    {
        $request->validate([
            'message'    => 'required|string|max:4096',
            'phoneField' => 'nullable|string',
        ]);

        // Auto-detect phone field from config if not provided
        $phoneField = $request->phoneField;
        if (!$phoneField) {
            $phoneCol   = collect($config->config)->firstWhere('type', 'phone');
            $phoneField = $phoneCol['field'] ?? null;
        }

        if (!$phoneField) {
            return response()->json([
                'error' => 'No se encontró ningún campo de tipo teléfono en la configuración de la tabla.',
            ], 422);
        }

        $records = TableData::where('config_id', $config->id)->get();
        $sent    = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($records as $record) {
            $rawPhone = $record->data[$phoneField] ?? null;
            if (!$rawPhone) continue;

            // Normalize: strip non-digits
            $phone = preg_replace('/\D/', '', (string) $rawPhone);
            if (strlen($phone) < 10) {
                $errors[] = "Teléfono inválido ignorado: {$rawPhone}";
                $failed++;
                continue;
            }

            // Add Mexico country code if only 10 digits
            if (strlen($phone) === 10) {
                $phone = '+52' . $phone;
            }

            try {
                $this->wa->send($phone, $request->message);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Tel {$phone}: " . $e->getMessage();
            }
        }

        return response()->json(compact('sent', 'failed', 'errors'));
    }
}
