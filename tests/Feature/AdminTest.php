<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_call_any_admin_action(): void
    {
        $article = Article::factory()->create();
        $target = User::factory()->create();
        $adminRole = Role::where('name', 'Администратор')->firstOrFail();

        foreach ([User::factory()->create(), User::factory()->journalist()->create()] as $user) {
            $this->actingAs($user);
            $this->delete('/admin/articles/'.$article->id)->assertForbidden();
            $this->patch('/admin/articles/'.$article->id.'/block', ['is_blocked' => 1])->assertForbidden();
            $this->patch('/admin/users/role', ['user_id' => $target->id, 'role_id' => $adminRole->id])->assertForbidden();
        }

        $this->assertDatabaseHas('texts', ['id' => $article->id, 'is_blocked' => false]);
        $this->assertSame('Читатель', $target->fresh()->role->name);
    }

    public function test_guest_cannot_call_admin_actions(): void
    {
        $article = Article::factory()->create();
        $this->delete('/admin/articles/'.$article->id)->assertRedirect('/login');
        $this->patch('/admin/articles/'.$article->id.'/block', ['is_blocked' => 1])->assertRedirect('/login');
        $this->patch('/admin/users/role', [])->assertRedirect('/login');
    }

    public function test_admin_can_edit_another_authors_article_without_changing_author(): void
    {
        $article = Article::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/journalist/'.$article->id.'/edit')->assertOk();
        $this->put('/articles/'.$article->id, [
            'heading' => 'Исправлено администратором',
            'category_id' => $article->category_id,
            'text' => 'Исправленный текст',
            'user_id' => $admin->id,
        ])->assertRedirect('/');

        $this->assertDatabaseHas('texts', ['id' => $article->id, 'heading' => 'Исправлено администратором', 'user_id' => $article->user_id]);
    }

    public function test_admin_can_delete_an_article(): void
    {
        $article = Article::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->delete('/admin/articles/'.$article->id)->assertRedirect('/admin');
        $this->assertDatabaseMissing('texts', ['id' => $article->id]);
        $this->assertDatabaseHas('users', ['id' => $article->user_id]);
    }

    public function test_blocking_hides_article_from_public_feeds_but_keeps_it_in_admin_and_author_views(): void
    {
        $article = Article::factory()->create(['heading' => 'Скрытая статья']);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch('/admin/articles/'.$article->id.'/block', ['is_blocked' => 1])->assertRedirect('/admin');
        $this->assertTrue($article->fresh()->is_blocked);
        $this->get('/')->assertDontSee('Скрытая статья');
        $this->get('/category/'.$article->category_id)->assertDontSee('Скрытая статья');
        $this->get('/admin')->assertSee('Скрытая статья')->assertSee('Заблокирована');
        $this->actingAs($article->user)->get('/journalist')->assertSee('Скрытая статья');
    }

    public function test_journalist_cannot_unblock_article_by_editing_it(): void
    {
        $article = Article::factory()->create(['is_blocked' => true]);
        $this->actingAs($article->user)->put('/articles/'.$article->id, [
            'heading' => 'Новый заголовок',
            'category_id' => $article->category_id,
            'text' => 'Новый текст',
            'is_blocked' => false,
        ])->assertRedirect('/');
        $this->assertTrue($article->fresh()->is_blocked);
    }

    public function test_unblocking_returns_article_to_public_feeds(): void
    {
        $article = Article::factory()->create(['heading' => 'Снова доступна', 'is_blocked' => true]);
        $this->actingAs(User::factory()->admin()->create())->patch('/admin/articles/'.$article->id.'/block', ['is_blocked' => 0])->assertRedirect('/admin');
        $this->get('/')->assertSee('Снова доступна');
        $this->get('/category/'.$article->category_id)->assertSee('Снова доступна');
    }

    public function test_admin_can_change_role_and_access_changes_on_next_request(): void
    {
        $admin = User::factory()->admin()->create();
        $reader = User::factory()->create();
        $journalistRole = Role::where('name', 'Журналист')->firstOrFail();
        $this->actingAs($admin)->patch('/admin/users/role', ['user_id' => $reader->id, 'role_id' => $journalistRole->id])->assertRedirect('/admin');
        $this->assertSame('Журналист', $reader->fresh()->role->name);
        $this->actingAs($reader->fresh())->get('/journalist')->assertOk();
        $this->get('/admin')->assertForbidden();

        $this->actingAs($admin)->patch('/admin/users/role', ['user_id' => $reader->id, 'role_id' => Role::where('name', 'Читатель')->firstOrFail()->id])->assertRedirect('/admin');
        $this->actingAs($reader->fresh())->get('/journalist')->assertForbidden();
    }

    public function test_last_admin_cannot_remove_their_own_admin_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->from('/admin')->patch('/admin/users/role', [
            'user_id' => $admin->id,
            'role_id' => Role::where('name', 'Читатель')->firstOrFail()->id,
        ])->assertRedirect('/admin')->assertSessionHasErrors('role_id');
        $this->assertSame('Администратор', $admin->fresh()->role->name);
    }

    public function test_admin_can_demote_self_if_another_admin_remains(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        $this->actingAs($admin)->patch('/admin/users/role', [
            'user_id' => $admin->id,
            'role_id' => Role::where('name', 'Читатель')->firstOrFail()->id,
        ])->assertRedirect('/');
        $this->actingAs($admin->fresh())->get('/admin')->assertForbidden();
    }

    public function test_admin_forms_reject_invalid_role_and_block_values(): void
    {
        $article = Article::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->patch('/admin/users/role', ['user_id' => $article->user_id, 'role_id' => 999999])->assertSessionHasErrors('role_id');
        $this->patch('/admin/articles/'.$article->id.'/block', ['is_blocked' => 'invalid'])->assertSessionHasErrors('is_blocked');
        $this->assertFalse($article->fresh()->is_blocked);
    }
}
