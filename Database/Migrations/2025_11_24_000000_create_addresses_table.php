<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FIAS, KLADR или иной GUID из бесплатного сервиса
            $table->string('guid')->nullable()->index();

            // человекочитаемый полный адрес
            $table->string('full_address')->nullable();

            // дополнительные структурированные поля: дом, улица, координаты
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}
