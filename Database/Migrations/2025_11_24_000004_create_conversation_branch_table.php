<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationBranchTable extends Migration
{
    public function up()
    {
        Schema::create('conversation_branch', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('branch_id')->index();

            $table->unsignedBigInteger('attached_by')->nullable(); // user id who attached
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            // Гарантируем, что у одной беседы только один текущий branch,
            // но оставляем возможность истории — enforce uniqueness on conversation_id.
            $table->unique('conversation_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversation_branch');
    }
}
