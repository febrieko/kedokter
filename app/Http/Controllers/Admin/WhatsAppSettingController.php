<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\MessageTemplate;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppSettingController extends Controller
{
    public function __construct(
        protected WhatsAppManager $waManager
    ) {}

    protected function getClinic(Request $request): Clinic
    {
        return $request->user()->clinic ?? Clinic::firstOrFail();
    }

    public function index(Request $request): View
    {
        $clinic = $this->getClinic($request);
        $templates = MessageTemplate::where('clinic_id', $clinic->id)->orderBy('key')->get();

        return view('admin.whatsapp.index', compact('clinic', 'templates'));
    }

    public function updateConfig(Request $request): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        $validated = $request->validate([
            'whatsapp_provider' => ['required', 'in:FONNTE,WAHA'],
            'fonnte_token' => ['nullable', 'string'],
            'waha_base_url' => ['nullable', 'url'],
            'waha_api_key' => ['nullable', 'string'],
            'waha_session' => ['nullable', 'string'],
        ]);

        $config = [];
        if ($validated['whatsapp_provider'] === 'FONNTE') {
            $config = [
                'token' => $validated['fonnte_token'] ?? '',
            ];
        } else {
            $config = [
                'base_url' => $validated['waha_base_url'] ?? 'http://localhost:3000',
                'api_key' => $validated['waha_api_key'] ?? '',
                'session' => $validated['waha_session'] ?? 'default',
            ];
        }

        $clinic->update([
            'whatsapp_provider' => $validated['whatsapp_provider'],
            'whatsapp_config' => $config,
        ]);

        return back()->with('success', 'Konfigurasi WhatsApp Gateway berhasil disimpan.');
    }

    /**
     * Test sending a test message to an inputted phone number
     */
    public function testSend(Request $request): RedirectResponse
    {
        $clinic = $this->getClinic($request);

        $validated = $request->validate([
            'test_phone' => ['required', 'string', 'max:20'],
        ]);

        $msg = "✅ *KONEKSI WHATSAPP BERHASIL*\n\n"
            . "Pesan ini adalah pesan uji coba dari sistem antrian klinik *{$clinic->name}*.\n"
            . "Gateway *{$clinic->whatsapp_provider}* terhubung dengan baik!";

        $result = $this->waManager->send($clinic, $validated['test_phone'], $msg);

        if ($result['success']) {
            return back()->with('success', "Pesan uji coba berhasil dikirim ke {$validated['test_phone']}.");
        }

        return back()->with('error', "Gagal mengirim pesan: " . ($result['error'] ?? 'Periksa token / konfigurasi gateway Anda.'));
    }

    public function updateTemplate(Request $request, MessageTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $template->update(['body' => $validated['body']]);

        return back()->with('success', "Template pesan '{$template->key}' berhasil diperbarui.");
    }
}
