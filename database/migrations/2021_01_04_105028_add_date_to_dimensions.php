<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateToDimensions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dimensions', function (Blueprint $table) {
            if (Schema::hasColumn('dimensions', 'date')) {
                return;
            }
            $table->string('date', 30)->nullable()->index()->comment('统计的时间(天)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dimensions', function (Blueprint $table) {
            //
        });
    }
}
