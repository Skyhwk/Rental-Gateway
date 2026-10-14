<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationDomainsTable extends Migration
{
    public function up()
    {
        Schema::create('application_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('hostname');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique('hostname');
            $table->index(['application_id', 'is_primary']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('application_domains');
    }
}
