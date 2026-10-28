<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $readerId = DB::table('roles')->where('name', 'Читатель')->value('id');
        $journalistId = DB::table('roles')->where('name', 'Журналист')->value('id');

        Schema::table('users', function (Blueprint $table) use ($readerId) {
            $table->foreignId('role_id')->default($readerId)->index()->constrained('roles')->restrictOnDelete();
        });

        DB::table('users')->whereIn('id', DB::table('articles')->select('user_id'))->update(['role_id' => $journalistId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
