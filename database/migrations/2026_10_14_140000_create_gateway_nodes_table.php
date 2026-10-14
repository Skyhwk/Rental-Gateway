<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGatewayNodesTable extends Migration
{
    public function up()
    {
        Schema::create('gateway_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        DB::table('gateway_nodes')->insert([
            'name' => 'Primary Node',
            'slug' => 'primary',
            'description' => 'Default gateway node (single-server MVP)',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('gateway_nodes');
    }
}
