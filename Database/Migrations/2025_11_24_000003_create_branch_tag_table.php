<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBranchTagTable extends Migration
{
    public function up()
    {
        Schema::create('branch_tag', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('tag_id')->index();

            // сохраняем время привязки
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            // tags таблица в FreeScout может быть в другой неймспейсе и называться `tags`
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');

            // уникальность: один тег не должен быть повторно привязан к одному объекту
            $table->unique(['branch_id', 'tag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_tag');
    }
}
