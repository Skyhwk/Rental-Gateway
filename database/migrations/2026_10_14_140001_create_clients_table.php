<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('pic_name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
