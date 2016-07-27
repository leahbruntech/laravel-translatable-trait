<?php

use Illuminate\Database\Migrations\Migration;

class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('translations', function ($table) {
            $table->increments('id');
            $table->string('translatable_type');
            $table->integer('translatable_id');
            $table->string('key');
            $table->string('name');
            $table->longText('content');
            $table->string('locale');
            $table->timestamps();

            $table->index(array('translatable_id', 'translatable_type'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('translations');
    }
}
