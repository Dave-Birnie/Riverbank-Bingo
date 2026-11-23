<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->unsignedTinyInteger('weekday');
            $table->unsignedTinyInteger('interval_weeks')->default(2);
            $table->string('start_time', 8);
            $table->unsignedSmallInteger('duration_minutes')->default(150);
            $table->unsignedSmallInteger('sales_close_minutes_before')->nullable();
            $table->json('prizes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->dateTime('card_sales_close_at')->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->text('cancellation_note')->nullable();
            $table->json('prizes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
        Schema::dropIfExists('game_templates');
    }
};
