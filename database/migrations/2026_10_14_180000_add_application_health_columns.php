<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicationHealthColumns extends Migration
{
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->timestamp('health_checked_at')->nullable()->after('health_status');
            $table->unsignedSmallInteger('health_http_code')->nullable()->after('health_checked_at');
            $table->string('health_last_error', 500)->nullable()->after('health_http_code');
        });
    }

    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['health_checked_at', 'health_http_code', 'health_last_error']);
        });
    }
}
