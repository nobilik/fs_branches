<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 255);
            $table->text('comment')->nullable();

            // FK to addresses table — many branches can share one address
            $table->unsignedBigInteger('address_id')->nullable()->index();
            $table->foreign('address_id')
                  ->references('id')->on('addresses')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('branches');
    }
}
