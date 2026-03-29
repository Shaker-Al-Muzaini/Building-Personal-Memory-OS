<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('relation')->default('صديق'); // العلاقة: عائلة، صديق، عمل
            $table->enum('importance', ['عالية', 'متوسطة', 'منخفضة'])->default('متوسطة');
            $table->date('last_contact')->nullable(); // آخر مرة تواصل معهم
            $table->text('gifts_notes')->nullable(); // هدايا، مناسبات، وملاحظات
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
