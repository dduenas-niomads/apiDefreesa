<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->foreign('users_id')->references('id')->on('users');
            $table->integer('bs_suppliers_id');
            $table->foreign('bs_suppliers_id')->references('id')->on('bs_suppliers');
            $table->bigInteger('delivery_users_id');
            $table->foreign('delivery_users_id')->references('id')->on('delivery_users');            
            $table->decimal('total', 10,4)->nullable();
            $table->tinyInteger('status')->nullable(1);
            $table->tinyInteger('delivery_status')->nullable(1);
            $table->tinyInteger('flag_active')->nullable(1);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
