<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('roles')]
    public function test_each_role_has_the_required_section_access(string $role, int $journalistStatus, int $adminStatus): void
    {
        Category::factory()->create(['name' => 'Программирование']);
        $user = User::factory()->create(['role_id' => Role::where('name', $role)->firstOrFail()->id]);
        $this->actingAs($user);

        $this->get('/')->assertOk();
        $this->get('/category')->assertOk();
        $this->get('/journalist')->assertStatus($journalistStatus);
        $this->get('/admin')->assertStatus($adminStatus);
    }

    public static function roles(): array
    {
        return [
            'reader' => ['Читатель', 403, 403],
            'journalist' => ['Журналист', 200, 403],
            'admin' => ['Администратор', 200, 200],
        ];
    }

    public function test_guest_can_read_but_is_redirected_from_protected_sections(): void
    {
        Category::factory()->create(['name' => 'Программирование']);
        $this->get('/')->assertOk();
        $this->get('/category')->assertOk();
        $this->get('/journalist')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_reader_cannot_create_or_edit_even_their_own_old_article(): void
    {
        $reader = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $reader->id]);
        $this->actingAs($reader);
        $data = ['heading' => 'Нельзя сохранить', 'category_id' => $article->category_id, 'text' => 'Текст'];

        $this->post('/articles', $data)->assertForbidden();
        $this->get('/journalist/'.$article->id.'/edit')->assertForbidden();
        $this->put('/articles/'.$article->id, $data)->assertForbidden();
        $this->assertDatabaseCount('texts', 1);
        $this->assertDatabaseHas('texts', ['id' => $article->id, 'heading' => $article->heading]);
    }

    public function test_navigation_only_shows_sections_available_to_the_role(): void
    {
        $journalistLink = 'href="'.route('journalist').'"';
        $adminLink = 'href="'.route('admin').'"';
        $this->get('/')->assertDontSee($journalistLink, false)->assertDontSee($adminLink, false);
        $this->actingAs(User::factory()->create())->get('/')->assertDontSee($journalistLink, false)->assertDontSee($adminLink, false);
        $this->actingAs(User::factory()->journalist()->create())->get('/')->assertSee($journalistLink, false)->assertDontSee($adminLink, false);
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertSee($journalistLink, false)->assertSee($adminLink, false);
    }
}
