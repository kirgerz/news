<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::rename('articles', 'texts');

        Schema::table('texts', function (Blueprint $table) {
            $table->renameColumn('title', 'heading');
        });

        Schema::table('texts', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('category_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('texts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['category_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['category_id']);
        });

        Schema::table('texts', function (Blueprint $table) {
            $table->renameColumn('heading', 'title');
        });

        Schema::rename('texts', 'articles');

        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
        });
    }
};
