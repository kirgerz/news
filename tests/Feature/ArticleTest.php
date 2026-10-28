<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_forms_or_save_articles(): void
    {
        $article = Article::factory()->create();

        $this->get('/journalist')->assertRedirect('/login');
        $this->get('/journalist/'.$article->id.'/edit')->assertRedirect('/login');
        $this->post('/articles', [])->assertRedirect('/login');
        $this->put('/articles/'.$article->id, [])->assertRedirect('/login');
        $this->assertDatabaseCount('texts', 1);
    }

    public function test_article_is_saved_with_logged_in_user_as_author_and_appears_in_both_feeds(): void
    {
        $author = User::factory()->journalist()->create(['login' => 'student']);
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Программирование']);
        $this->actingAs($author)->post('/articles', [
            'heading' => 'Моя первая статья',
            'category_id' => $category->id,
            'text' => 'Текст новой статьи.',
            'user_id' => $otherUser->id,
        ])->assertRedirect('/');

        $this->assertDatabaseHas('texts', [
            'heading' => 'Моя первая статья',
            'category_id' => $category->id,
            'user_id' => $author->id,
            'text' => 'Текст новой статьи.',
        ]);
        $this->get('/')->assertOk()->assertSee('Моя первая статья')->assertSee('student');
        $this->get('/category')->assertOk()->assertSee('Моя первая статья')->assertSee('Программирование');
        $this->get('/journalist')->assertOk()->assertSee('Мои статьи')->assertSee('Моя первая статья');
    }

    public function test_author_can_edit_article_and_change_category(): void
    {
        $article = Article::factory()->create();
        $newCategory = Category::factory()->create();
        $this->actingAs($article->user)->get('/journalist/'.$article->id.'/edit')->assertOk()->assertSee($article->heading);
        $this->put('/articles/'.$article->id, [
            'heading' => 'Исправленный заголовок',
            'category_id' => $newCategory->id,
            'text' => 'Исправленный текст.',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('texts', [
            'id' => $article->id,
            'heading' => 'Исправленный заголовок',
            'category_id' => $newCategory->id,
            'text' => 'Исправленный текст.',
            'user_id' => $article->user_id,
        ]);
        $this->get('/category/'.$newCategory->id)->assertSee('Исправленный заголовок');
        $this->get('/category/'.$article->category_id)->assertDontSee('Исправленный заголовок');
    }

    public function test_other_users_cannot_edit_article_even_with_direct_request(): void
    {
        $article = Article::factory()->create();
        $this->actingAs(User::factory()->journalist()->create());
        $this->get('/journalist/'.$article->id.'/edit')->assertForbidden();
        $this->put('/articles/'.$article->id, [
            'heading' => 'Чужие изменения',
            'category_id' => $article->category_id,
            'text' => 'Изменения.',
        ])->assertForbidden();

        $this->assertDatabaseHas('texts', ['id' => $article->id, 'heading' => $article->heading]);
    }

    public function test_invalid_article_is_not_saved_and_input_is_preserved(): void
    {
        $this->actingAs(User::factory()->journalist()->create())->from('/journalist')->post('/articles', [
            'heading' => 'Незаконченная статья',
            'category_id' => 999999,
            'text' => '',
        ])->assertRedirect('/journalist')
            ->assertSessionHasErrors(['category_id', 'text'])
            ->assertSessionHasInput('heading', 'Незаконченная статья');

        $this->assertDatabaseCount('texts', 0);
    }

    public function test_category_only_shows_its_own_articles(): void
    {
        $category = Category::factory()->create(['name' => 'Программирование']);
        Article::factory()->create(['category_id' => $category->id, 'heading' => 'Новость программирования']);
        Article::factory()->create(['heading' => 'Новость другой категории']);

        $this->get('/category')->assertOk()->assertSee('Новость программирования')->assertDontSee('Новость другой категории');
        $this->get('/category/999999')->assertNotFound();
    }

    public function test_news_are_ordered_newest_first_and_paginated(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        foreach (range(1, 6) as $day) {
            Article::factory()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'heading' => 'Статья номер '.$day,
                'created_at' => now()->startOfDay()->subDays(7 - $day),
            ]);
        }

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Статья номер 6', 'Статья номер 5', 'Статья номер 4', 'Статья номер 3', 'Статья номер 2'])
            ->assertDontSee('Статья номер 1')
            ->assertSee('Далее');
        $this->get('/?page=2')->assertOk()->assertSee('Статья номер 1')->assertDontSee('Статья номер 6');
        $this->get('/category/'.$category->id.'?page=2')->assertOk()->assertSee('Статья номер 1')->assertDontSee('Статья номер 6');
    }

    public function test_article_html_is_displayed_as_text(): void
    {
        Article::factory()->create(['text' => '<script>alert(1)</script>']);
        $this->get('/')->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_admin_preview_uses_database_articles_and_users(): void
    {
        $article = Article::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()->assertSee($article->heading)->assertSee($article->user->login);
    }
}
