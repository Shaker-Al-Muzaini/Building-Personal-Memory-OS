# 🧪 Testing Guide - Clean Architecture

## فوائد الاختبار في هذه البنية

✅ **سهولة الاختبار** - كل طبقة منفصلة
✅ **Mocking بسيط** - يمكن mock Repositories والخدمات
✅ **اختبارات مستقلة** - لا تحتاج database كاملة
✅ **سرعة** - الاختبارات أسرع بكثير

---

## اختبار Use Cases

### مثال: اختبار CreateIdeaUseCase

```php
// tests/Feature/CreateIdeaUseCaseTest.php

<?php

namespace Tests\Feature;

use App\Application\UseCases\Ideas\CreateIdeaUseCase;
use App\Domain\Entities\Idea;
use App\Domain\Repositories\Contracts\IdeaRepositoryInterface;
use App\Infrastructure\Services\AIService;
use Mockery;
use Tests\TestCase;

class CreateIdeaUseCaseTest extends TestCase
{
    public function test_create_idea_with_ai_analysis()
    {
        // Arrange - تحضير البيانات والـ Mocks
        $mockIdeaRepo = Mockery::mock(IdeaRepositoryInterface::class);
        $mockAIService = Mockery::mock(AIService::class);

        // توقع من AIService أن ترجع تحليل
        $mockAIService->shouldReceive('analyzeIdea')
            ->with(1, 'فكرتي الجديدة')
            ->andReturn([
                'analysis' => 'هذه فكرة رائعة',
                'category' => 'عام'
            ]);

        // توقع من Repository أن يحفظ الـ Idea
        $mockIdeaRepo->shouldReceive('create')
            ->andReturnUsing(function (Idea $idea) {
                $idea->id = 1; // محاكاة إنشاء ID
                return $idea;
            });

        // Act - تنفيذ
        $useCase = new CreateIdeaUseCase($mockIdeaRepo, $mockAIService);
        $result = $useCase->execute(1, 'فكرتي الجديدة');

        // Assert - التحقق
        $this->assertInstanceOf(Idea::class, $result);
        $this->assertEquals('فكرتي الجديدة', $result->content);
        $this->assertEquals('هذه فكرة رائعة', $result->aiAnalysis);
        $this->assertEquals('عام', $result->category);
    }

    public function test_create_idea_validates_content()
    {
        // اختبر أن الفكرة الفارغة تفشل
        $this->assertTrue(true); // أضف فحص هنا
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

---

## اختبار Repositories

### مثال: اختبار EloquentIdeaRepository

```php
// tests/Feature/IdeaRepositoryTest.php

<?php

namespace Tests\Feature;

use App\Domain\Entities\Idea;
use App\Infrastructure\Repositories\EloquentIdeaRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentIdeaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentIdeaRepository();
    }

    public function test_create_idea()
    {
        // Arrange
        $idea = new Idea(
            id: 0,
            userId: 1,
            content: 'فكرة جديدة',
            aiAnalysis: 'تحليل',
            status: 'draft',
            category: 'عام'
        );

        // Act
        $created = $this->repository->create($idea);

        // Assert
        $this->assertNotNull($created->id);
        $this->assertDatabaseHas('ideas', [
            'user_id' => 1,
            'content' => 'فكرة جديدة',
        ]);
    }

    public function test_get_all_by_user_id()
    {
        // Arrange - إنشاء أفكار
        \Illuminate\Support\Facades\DB::table('ideas')->insert([
            ['user_id' => 1, 'content' => 'فكرة 1', 'status' => 'draft', 'category' => 'عام', 'created_at' => now()],
            ['user_id' => 1, 'content' => 'فكرة 2', 'status' => 'draft', 'category' => 'عام', 'created_at' => now()],
            ['user_id' => 2, 'content' => 'فكرة 3', 'status' => 'draft', 'category' => 'عام', 'created_at' => now()],
        ]);

        // Act
        $ideas = $this->repository->getAllByUserId(1);

        // Assert
        $this->assertCount(2, $ideas);
        $this->assertEquals('فكرة 1', $ideas[0]->content);
    }

    public function test_update_status()
    {
        // Arrange
        \Illuminate\Support\Facades\DB::table('ideas')->insert([
            ['user_id' => 1, 'content' => 'فكرة', 'status' => 'draft', 'category' => 'عام', 'created_at' => now()],
        ]);

        $idea = $this->repository->getById(1);

        // Act
        $this->repository->updateStatus(1, 'developing');

        // Assert
        $updated = $this->repository->getById(1);
        $this->assertEquals('developing', $updated->status);
    }

    public function test_delete_idea()
    {
        // Arrange
        \Illuminate\Support\Facades\DB::table('ideas')->insert([
            ['user_id' => 1, 'content' => 'فكرة', 'status' => 'draft', 'category' => 'عام', 'created_at' => now()],
        ]);

        // Act
        $result = $this->repository->delete(1);

        // Assert
        $this->assertTrue($result);
        $this->assertNull($this->repository->getById(1));
    }
}
```

---

## اختبار Controllers

### مثال: اختبار IdeaController

```php
// tests/Feature/IdeaControllerTest.php

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_get_ideas_index()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/ideas');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Ideas')
                ->has('ideas', 0)
        );
    }

    public function test_create_idea()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/ideas', [
            'content' => 'فكرتي الجديدة',
        ]);

        // Assert
        $response->assertStatus(302); // redirect
        $this->assertDatabaseHas('ideas', [
            'user_id' => $user->id,
            'content' => 'فكرتي الجديدة',
        ]);
    }

    public function test_create_idea_requires_content()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/ideas', [
            'content' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors('content');
    }

    public function test_update_idea_status()
    {
        // Arrange
        $user = User::factory()->create();
        \Illuminate\Support\Facades\DB::table('ideas')->insert([
            [
                'user_id' => $user->id,
                'content' => 'فكرة',
                'status' => 'draft',
                'category' => 'عام',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->actingAs($user)->patch('/ideas/1/status', [
            'status' => 'developing',
        ]);

        // Assert
        $response->assertStatus(302);
        $this->assertDatabaseHas('ideas', [
            'id' => 1,
            'status' => 'developing',
        ]);
    }

    public function test_anonymous_user_cannot_access_ideas()
    {
        // Act & Assert
        $this->get('/ideas')->assertStatus(302); // redirect to login
    }
}
```

---

## تشغيل الاختبارات

### تشغيل جميع الاختبارات
```bash
php artisan test
```

### تشغيل اختبار معين
```bash
php artisan test tests/Feature/IdeaControllerTest.php
```

### تشغيل method معين فقط
```bash
php artisan test tests/Feature/IdeaControllerTest.php::test_get_ideas_index
```

### مع التفاصيل
```bash
php artisan test --verbose
```

### مع التغطية (Coverage)
```bash
php artisan test --coverage
```

---

## Unit Tests vs Feature Tests

### Unit Tests (اختبار الوحدات)
- **الموقع**: `tests/Unit/`
- **الدرجة**: منفردة
- **الاستخدام**: اختبار الـ Logic المعقد

```php
// tests/Unit/CreateIdeaUseCaseTest.php
// اختبر Use Case منفردة بـ mocks
```

### Feature Tests (اختبار الميزات)
- **الموقع**: `tests/Feature/`
- **الدرجة**: متكاملة (مع database)
- **الاستخدام**: اختبار العمليات الكاملة

```php
// tests/Feature/IdeaControllerTest.php
// اختبر من HTTP request لـ Database
```

---

## Best Practices للاختبار

1. **استخدم RefreshDatabase** للاختبارات التي تحتاج DB
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestClass extends TestCase
{
    use RefreshDatabase;
}
```

2. **استخدم Factories** لإنشاء بيانات الاختبار
```php
$user = User::factory()->create();
$idea = \Illuminate\Support\Facades\DB::table('ideas')->insert([...]);
```

3. **استخدم Mockery** للخدمات الخارجية
```php
$mock = Mockery::mock(AIService::class);
$mock->shouldReceive('analyze')->andReturn('result');
```

4. **اختبر الحالات السعيدة والفاشلة**
```php
public function test_success() { ... }
public function test_validation_failure() { ... }
public function test_unauthorized_access() { ... }
```

5. **استخدم meaningful messages**
```php
$this->assertTrue($result, 'الفكرة يجب أن تُحفظ بنجاح');
```

---

## مثال: Integration Test شامل

```php
// tests/Feature/IdeaCreationFlowTest.php

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaCreationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_idea_creation_flow()
    {
        // 1. أنشئ مستخدم
        $user = User::factory()->create();

        // 2. تحقق أن المستخدم يستطيع الوصول لصفحة الأفكار
        $response = $this->actingAs($user)->get('/ideas');
        $response->assertStatus(200);

        // 3. أنشئ فكرة جديدة
        $response = $this->actingAs($user)->post('/ideas', [
            'content' => 'بناء تطبيق للعقل الموازي',
        ]);
        $response->assertStatus(302);

        // 4. التحقق أن الفكرة تم حفظها في DB
        $this->assertDatabaseHas('ideas', [
            'user_id' => $user->id,
            'content' => 'بناء تطبيق للعقل الموازي',
            'status' => 'draft',
        ]);

        // 5. اجلب الفكرة وتحقق
        $response = $this->actingAs($user)->get('/ideas');
        $response->assertInertia(
            fn($page) => $page
                ->component('Ideas')
                ->has('ideas.0', fn($page) => $page
                    ->where('status', 'draft')
                    ->where('content', 'بناء تطبيق للعقل الموازي')
                )
        );

        // 6. حدث حالة الفكرة
        $response = $this->actingAs($user)->patch('/ideas/1/status', [
            'status' => 'developing',
        ]);
        $response->assertStatus(302);

        // 7. التحقق من التحديث
        $this->assertDatabaseHas('ideas', [
            'id' => 1,
            'status' => 'developing',
        ]);
    }
}
```

---

**Happy Testing! 🧪**

