<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winners', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->date('won_on')->index();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('prize_label')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('story')->nullable();
            $table->boolean('consent_confirmed')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winners');
    }
};
