<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('role')->default('viewer'); // viewer, admin, operator
            $table->string('sso_sub')->nullable(); // subject dari JWT
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_users');
    }
};