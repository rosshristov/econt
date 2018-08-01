<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EcontStreets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('econt_streets', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('city_id')->index('idx_city');
            $table->string('name');
            $table->string('name_en');
            $table->unsignedInteger('city_post_code')->index('idx_city_post_code');
            $table->datetime('updated_time')->nullable()->default(null);
            $table->timestamps();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('econt_streets');
    }
}
