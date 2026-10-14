<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGatewaySyncTables extends Migration
{
    public function up()
    {
        Schema::create('gateway_config_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('checksum', 128);
            $table->string('config_snapshot_path')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
        });

        Schema::create('gateway_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->string('desired_status', 20);
            $table->string('sync_status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('gateway_config_version_id')->nullable()->constrained('gateway_config_versions')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 50)->default('system');
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
            $table->index('sync_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gateway_sync_logs');
        Schema::dropIfExists('gateway_config_versions');
    }
}
