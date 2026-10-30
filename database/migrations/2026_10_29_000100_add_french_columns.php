<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * French twins for every piece of text players read. A blank French field
 * shows the English, so the owner can translate at their own pace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_fr')->nullable()->after('title');
            $table->text('intro_fr')->nullable()->after('intro');
            $table->longText('body_fr')->nullable()->after('body');
            $table->string('meta_description_fr')->nullable()->after('meta_description');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('title_fr')->nullable()->after('title');
            $table->text('excerpt_fr')->nullable()->after('excerpt');
            $table->longText('body_fr')->nullable()->after('body');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('message_fr')->nullable()->after('message');
            $table->text('body_fr')->nullable()->after('body');
            $table->string('link_label_fr')->nullable()->after('link_label');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->string('title_fr')->nullable()->after('title');
            $table->text('cancellation_note_fr')->nullable()->after('cancellation_note');
            $table->text('notes_fr')->nullable()->after('notes');
        });

        Schema::table('game_templates', function (Blueprint $table) {
            $table->string('title_fr')->nullable()->after('title');
            $table->text('notes_fr')->nullable()->after('notes');
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->text('hours_fr')->nullable()->after('hours');
            $table->text('notes_fr')->nullable()->after('notes');
        });

        Schema::table('winners', function (Blueprint $table) {
            $table->string('prize_label_fr')->nullable()->after('prize_label');
            $table->text('story_fr')->nullable()->after('story');
        });

        // Which language the visitor wrote in, so the reply can match.
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('locale', 5)->default('en')->after('topic');
        });
    }

    public function down(): void
    {
        $columns = [
            'pages' => ['title_fr', 'intro_fr', 'body_fr', 'meta_description_fr'],
            'posts' => ['title_fr', 'excerpt_fr', 'body_fr'],
            'announcements' => ['message_fr', 'body_fr', 'link_label_fr'],
            'games' => ['title_fr', 'cancellation_note_fr', 'notes_fr'],
            'game_templates' => ['title_fr', 'notes_fr'],
            'distributors' => ['hours_fr', 'notes_fr'],
            'winners' => ['prize_label_fr', 'story_fr'],
            'contact_messages' => ['locale'],
        ];

        foreach ($columns as $table => $drop) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($drop));
        }
    }
};
