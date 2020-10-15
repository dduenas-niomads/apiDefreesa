<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_user', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('lastname', 255)->nullable();
            $table->string('email')->unique();
            $table->string('phone', 255)->nullable();
            $table->char('type_document', 5)->nullable();
            $table->string('document_number', 25)->nullable();
            $table->json('address_info')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->tinyInteger('active')->nullable();
            $table->string('activation_token', 60)->nullable();
            $table->string('forgot_password_token', 60)->nullable();
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
        Schema::dropIfExists('delivery_user');
    }
}
