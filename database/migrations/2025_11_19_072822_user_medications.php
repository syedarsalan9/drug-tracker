<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('rxcui')->index();
            $table->string('drug_name');
            $table->json('base_names')->nullable();
            $table->json('dosage_forms')->nullable();
            $table->timestamps();
            
            // Prevent duplicate entries for same user and drug
            $table->unique(['user_id', 'rxcui']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_medications');
    }
};