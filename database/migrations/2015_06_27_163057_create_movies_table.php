<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMoviesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movies', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('movie_id')->unsigned();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('service', ['animeland', 'anidub', 'animespirit', 'anistar']);
            $table->json('info');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('movies');
    }
}
