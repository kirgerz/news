<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable()->unique();
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(['login' => 'user'.$user->id]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'name' => $user->name ?? $user->login,
                'email' => $user->email ?? 'user'.$user->id.'@example.invalid',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->dropColumn('login');
        });
    }
};
