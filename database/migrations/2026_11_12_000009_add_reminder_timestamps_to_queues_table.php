<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->timestamp('reminder_h1_sent_at')->nullable()->after('last_called_at');
            $table->timestamp('near_turn_sent_at')->nullable()->after('reminder_h1_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn(['reminder_h1_sent_at', 'near_turn_sent_at']);
        });
    }
};
