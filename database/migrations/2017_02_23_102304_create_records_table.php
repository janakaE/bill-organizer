<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('records', function (Blueprint $table) {
            $table->increments('id');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('period');
            $table->double('amount', 15, 2);
            $table->string('path_to_file', 1024); // this should be unique, but unique constraint creates an index so i'm not adding it
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->integer('record_issuer_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('record_issuer_id')->references('id')->on('record_issuers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('records');
    }
}
