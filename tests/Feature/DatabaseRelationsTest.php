<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_follow_all_relationships_in_both_directions(): void
    {
        $role = Role::where('name', 'Журналист')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);
        $category = Category::factory()->create();
        $article = Article::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $this->assertTrue($user->role->is($role));
        $this->assertTrue($role->users->contains($user));
        $this->assertTrue($article->user->is($user));
        $this->assertTrue($user->articles->contains($article));
        $this->assertTrue($article->category->is($category));
        $this->assertTrue($category->articles->contains($article));
        $this->assertDatabaseHas('texts', ['id' => $article->id, 'heading' => $article->heading]);
        $this->assertFalse(Schema::hasTable('articles'));
    }

    public function test_registration_assigns_reader_role_and_ignores_submitted_role(): void
    {
        User::factory()->admin()->create();
        $admin = Role::where('name', 'Администратор')->firstOrFail();
        $this->post('/register', [
            'login' => 'new_reader',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $admin->id,
        ])->assertRedirect('/');

        $user = User::where('login', 'new_reader')->firstOrFail();
        $this->assertSame('Читатель', $user->role->name);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->expectException(QueryException::class);
        $user->role->delete();
    }

    public function test_category_with_texts_cannot_be_deleted(): void
    {
        $article = Article::factory()->create();
        $this->expectException(QueryException::class);
        $article->category->delete();
    }

    public function test_deleting_author_cascades_only_their_texts(): void
    {
        $article = Article::factory()->create();
        $otherArticle = Article::factory()->create();
        $article->user->delete();

        $this->assertDatabaseMissing('texts', ['id' => $article->id]);
        $this->assertDatabaseHas('texts', ['id' => $otherArticle->id]);
        $this->assertDatabaseHas('categories', ['id' => $article->category_id]);
    }

    public function test_unused_role_and_category_can_be_deleted(): void
    {
        $role = Role::factory()->create();
        $category = Category::factory()->create();
        $role->delete();
        $category->delete();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[DataProvider('invalidForeignKeys')]
    public function test_database_rejects_missing_or_nonexistent_parent(string $table, string $column, ?int $value): void
    {
        $article = Article::factory()->create();
        $id = $table === 'users' ? $article->user_id : $article->id;

        $this->expectException(QueryException::class);
        DB::table($table)->where('id', $id)->update([$column => $value]);
    }

    public static function invalidForeignKeys(): array
    {
        return [
            'user requires role' => ['users', 'role_id', null],
            'role must exist' => ['users', 'role_id', 999999],
            'text requires author' => ['texts', 'user_id', null],
            'author must exist' => ['texts', 'user_id', 999999],
            'text requires category' => ['texts', 'category_id', null],
            'category must exist' => ['texts', 'category_id', 999999],
        ];
    }

    public function test_database_enforces_unique_login(): void
    {
        User::factory()->create(['login' => 'same_login']);
        $this->expectException(QueryException::class);
        User::factory()->create(['login' => 'same_login']);
    }

    public function test_relationship_columns_are_indexed_and_login_has_unique_index(): void
    {
        foreach (['users' => ['role_id'], 'texts' => ['user_id', 'category_id']] as $table => $columns) {
            $indexes = Schema::getIndexes($table);
            foreach ($columns as $column) {
                $this->assertTrue(collect($indexes)->contains(fn (array $index): bool => $index['columns'] === [$column]));
            }
        }

        $this->assertTrue(collect(Schema::getIndexes('users'))->contains(
            fn (array $index): bool => $index['columns'] === ['login'] && $index['unique']
        ));
    }

    public function test_upgrade_preserves_records_and_assigns_journalist_role_to_existing_author(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('проверка отката и повторного обновления выполняется на PostgreSQL');
        }

        $article = Article::factory()->create(['heading' => 'Существующая статья']);
        $before = $article->refresh()->getAttributes();
        $usersBefore = User::count();
        $rolesMigration = require database_path('migrations/2026_10_25_184334_create_roles_table.php');
        $usersMigration = require database_path('migrations/2026_10_25_184335_add_role_id_to_users_table.php');
        $textsMigration = require database_path('migrations/2026_10_25_184336_rename_articles_to_texts.php');

        $textsMigration->down();
        $usersMigration->down();
        $rolesMigration->down();
        $this->assertSame('Существующая статья', DB::table('articles')->where('id', $article->id)->value('title'));

        $rolesMigration->up();
        $usersMigration->up();
        $textsMigration->up();

        $this->assertEquals($before, $article->fresh()->getAttributes());
        $this->assertSame($usersBefore, User::count());
        $this->assertSame('Журналист', $article->user->role->name);
    }

    public function test_seeding_twice_does_not_duplicate_roles_categories_or_texts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('texts', 3);
        $this->assertSame('Журналист', User::where('login', 'redactor')->firstOrFail()->role->name);
    }
}
