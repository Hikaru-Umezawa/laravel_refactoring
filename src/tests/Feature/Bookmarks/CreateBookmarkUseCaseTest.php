<?php

namespace Tests\Feature\Bookmarks;

use App\Bookmark\UseCase\CreateBookmarkUseCase;
use App\Lib\LinkPreview\LinkPreviewInterface;
use App\Lib\LinkPreview\MockLinkPreview;
use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateBookmarkUseCaseTest extends TestCase
{
  private CreateBookmarkUseCase $useCase;

  protected function setUp(): void
  {
    parent::setUp();

    // モックを使用
    $this->app->bind(LinkPreviewInterface::class, MockLinkPreview::class);
    $this->useCase = $this->app->make(CreateBookmarkUseCase::class);
  }

  public function testSaveCorrectData()
  {
    $url = 'https://notfound.example.com/';
    $category = BookmarkCategory::query()->first()->id;
    $comment = 'テスト用のコメント';

    $testUser = User::query()->first();
    Auth::loginUsingId($testUser->id);

    $this->useCase->handle($url, $category, $comment);

    Auth::logout();

    $this->assertDatabaseHas('bookmarks', [
      'url' => $url,
      'category_id' => $category,
      'user_id' => $testUser->id,
      'comment' => $comment,
      'page_title' => 'モックのタイトル',
      'page_description' => 'モックのdescription',
      'page_thumbnail_url' => 'https://i.gyazo.com/634f77ea66b5e522e7afb9f1d1dd75cb.png',
    ]);
  }

  public function testWhenFetchMetaFailed()
  {
    $url = 'https://notfound.example.com/';
    $category = BookmarkCategory::query()->first()->id;
    $comment = 'テスト用のコメント';

    $mock = \Mockery::mock(LinkPreviewInterface::class);

    // 作ったモックがgetメソッドを実行したら必ず例外を投げる
    $mock->shouldReceive('get')
      ->withArgs([$url])
      ->andThrow(new \Exception('URLからメタ情報の取得に失敗'))
      ->once();

    $this->app->instance(
      LinkPreviewInterface::class,
      $mock
    );

    // 例外が投げられることのテスト
    $this->expectException(ValidationException::class);
    $this->expectExceptionObject(ValidationException::withMessages([
      'url' => 'URLが存在しない等の理由で読み込めませんでした。変更して再度投稿してください'
    ]));

    // 実際の処置を実行
    $this->useCase = $this->app->make(CreateBookmarkUseCase::class);
    $this->useCase->handle($url, $category, $comment);
  }
}
