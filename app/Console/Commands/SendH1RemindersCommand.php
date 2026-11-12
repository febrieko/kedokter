<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendH1RemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clinic:send-h1-reminders {--clinic= : ID atau Slug Klinik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pesan pengingat WhatsApp H-1 untuk pasien yang memiliki jadwal antrian besok';

    /**
     * Execute the console command.
     */
    public function handle(ReminderService $reminderService): int
    {
        $this->info("Memulai proses pengiriman pengingat WhatsApp H-1...");

        $clinicId = $this->option('clinic');
        $result = $reminderService->sendH1Reminders($clinicId);

        $this->table(
            ['Total Antrian Besok', 'Pesan Terkirim', 'Gagal / Dilewati'],
            [
                [$result['total_eligible'], $result['sent'], $result['failed']]
            ]
        );

        $this->info("Proses pengingat H-1 selesai. {$result['sent']} pesan berhasil dikirim.");

        return Command::SUCCESS;
    }
}
