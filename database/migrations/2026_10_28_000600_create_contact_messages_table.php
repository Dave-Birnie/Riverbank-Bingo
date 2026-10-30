<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kept as well as emailed, so a message survives a mail hiccup.
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 30)->default('general');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('message');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
