<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationsTable extends Migration
{
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_node_id')->constrained('gateway_nodes')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('internal_host')->default('127.0.0.1');
            $table->unsignedInteger('internal_port');
            $table->string('access_status', 20)->default('active');
            $table->string('gateway_applied_status', 20)->nullable();
            $table->string('health_status', 20)->default('unknown');
            $table->json('deployment_meta')->nullable();
            $table->text('technical_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gateway_node_id', 'internal_port']);
            $table->index(['client_id', 'access_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('applications');
    }
}
