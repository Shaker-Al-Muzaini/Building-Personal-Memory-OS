# 🚀 دليل البدء السريع - Clean Architecture

## الملفات المهمة

- 📖 **ARCHITECTURE.md** - شرح مفصل لكل طبقة
- 🎨 **ARCHITECTURE_DIAGRAM.txt** - رسم توضيحي للبنية
- 📍 **هذا الملف** - دليل سريع

---

## أين تضع الملفات الجديدة؟

### 1️⃣ أضفت Entity جديد؟
```
app/Domain/Entities/YourEntity.php
```
جرب هذا المثال:
```php
<?php
namespace App\Domain\Entities;

class YourEntity
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        // ... خصائصك
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            userId: $data['user_id'],
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
        ];
    }
}
```

---

### 2️⃣ أنشأت Repository Interface جديد؟
```
app/Domain/Repositories/Contracts/YourRepositoryInterface.php
```
مثال:
```php
<?php
namespace App\Domain\Repositories\Contracts;

use App\Domain\Entities\YourEntity;

interface YourRepositoryInterface
{
    public function getAllByUserId(int $userId): array;
    public function getById(int $id): ?YourEntity;
    public function create(YourEntity $entity): YourEntity;
    public function update(YourEntity $entity): YourEntity;
    public function delete(int $id): bool;
}
```

---

### 3️⃣ طبّقت Repository مع Database؟
```
app/Infrastructure/Repositories/EloquentYourRepository.php
```
مثال:
```php
<?php
namespace App\Infrastructure\Repositories;

use App\Domain\Entities\YourEntity;
use App\Domain\Repositories\Contracts\YourRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentYourRepository implements YourRepositoryInterface
{
    public function getAllByUserId(int $userId): array
    {
        $results = DB::table('your_table')
            ->where('user_id', $userId)
            ->get();

        return array_map(
            fn($data) => YourEntity::fromArray((array)$data),
            $results->toArray()
        );
    }

    public function create(YourEntity $entity): YourEntity
    {
        $id = DB::table('your_table')->insertGetId([
            'user_id' => $entity->userId,
            'name' => $entity->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entity->id = $id;
        return $entity;
    }

    // ... بقية المتوديات
}
```

---

### 4️⃣ أنشأت Use Case جديد؟
```
app/Application/UseCases/YourFeature/CreateYourEntityUseCase.php
```
مثال:
```php
<?php
namespace App\Application\UseCases\YourFeature;

use App\Domain\Entities\YourEntity;
use App\Domain\Repositories\Contracts\YourRepositoryInterface;

class CreateYourEntityUseCase
{
    public function __construct(
        private YourRepositoryInterface $repository
    ) {}

    public function execute(int $userId, string $name): YourEntity
    {
        $entity = new YourEntity(
            id: 0,
            userId: $userId,
            name: $name,
        );

        return $this->repository->create($entity);
    }
}
```

---

### 5️⃣ أنشأت Controller جديد؟
```
app/Presentation/Controllers/YourController.php
```
مثال:
```php
<?php
namespace App\Presentation\Controllers;

use App\Application\UseCases\YourFeature\CreateYourEntityUseCase;
use App\Application\UseCases\YourFeature\GetYourEntitiesUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class YourController extends Controller
{
    public function __construct(
        private GetYourEntitiesUseCase $getUseCase,
        private CreateYourEntityUseCase $createUseCase,
    ) {}

    public function index(Request $request)
    {
        $entities = $this->getUseCase->execute($request->user()->id);

        return Inertia::render('YourPage', [
            'entities' => array_map(
                fn($e) => $e->toArray(),
                $entities
            )
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        $entity = $this->createUseCase->execute(
            $request->user()->id,
            $request->input('name')
        );

        return back();
    }
}
```

---

### 6️⃣ ربط التبعيات (Service Provider)؟
```
app/Providers/RepositoryServiceProvider.php
```

أضف سطرين في دالتي `register()`:
```php
// ربط Interface مع Implementation
$this->app->bind(
    YourRepositoryInterface::class,
    EloquentYourRepository::class
);
```

---

## الخطوات الكاملة لإضافة Feature جديد

### مثال: إضافة Feature للملاحظات (Notes)

#### Step 1: أنشئ Entity
```bash
# app/Domain/Entities/Note.php
```

#### Step 2: أنشئ Repository Interface
```bash
# app/Domain/Repositories/Contracts/NoteRepositoryInterface.php
```

#### Step 3: تطبيق Repository
```bash
# app/Infrastructure/Repositories/EloquentNoteRepository.php
```

#### Step 4: أنشئ Use Cases
```bash
# app/Application/UseCases/Notes/CreateNoteUseCase.php
# app/Application/UseCases/Notes/GetNotesUseCase.php
# app/Application/UseCases/Notes/UpdateNoteUseCase.php
# app/Application/UseCases/Notes/DeleteNoteUseCase.php
```

#### Step 5: أنشئ Controller
```bash
# app/Presentation/Controllers/NoteController.php
```

#### Step 6: سجل في Service Provider
```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(NoteRepositoryInterface::class, EloquentNoteRepository::class);
```

#### Step 7: أضف الـ Routes
```php
// routes/web.php
Route::resource('notes', NoteController::class);
```

---

## اختبار البنية الجديدة

### اختبار Entity
```php
$idea = Idea::fromArray([
    'id' => 1,
    'user_id' => 1,
    'content' => 'فكرة جديدة',
    'ai_analysis' => 'التحليل',
    'status' => 'draft',
    'category' => 'عام',
]);

$array = $idea->toArray();
```

### اختبار Repository
```php
// في الـ Service Provider أو Container
$repo = app(IdeaRepositoryInterface::class);
$ideas = $repo->getAllByUserId(1);
```

### اختبار Use Case
```php
$createUseCase = app(CreateIdeaUseCase::class);
$idea = $createUseCase->execute(1, 'فكرتي الجديدة');
```

---

## الفرق بين الهيكلة القديمة والجديدة

### ❌ القديمة (مختلطة)
```php
// في Controller
public function store(Request $request)
{
    DB::table('ideas')->insert([...]);
    Http::post('api...', [...]); // AI call
    return response();
}
```
**المشاكل**:
- منطق معقد في Controller
- صعب الاختبار
- صعب إعادة الاستخدام

### ✅ الجديدة (نظيفة)
```php
// في Controller
public function store(Request $request)
{
    $idea = $this->createIdeaUseCase->execute(
        $request->user()->id,
        $request->input('content')
    );
    return back();
}

// في Use Case
public function execute(int $userId, string $content): Idea
{
    $analysis = $this->aiService->analyzeIdea($userId, $content);
    $idea = new Idea(...);
    return $this->ideaRepository->create($idea);
}

// في Repository
public function create(Idea $idea): Idea
{
    $id = DB::table('ideas')->insertGetId([...]);
    return $idea;
}
```

**الفوائد**:
✅ منطق منظم ومرتب
✅ سهل الاختبار
✅ قابل لإعادة الاستخدام
✅ الصيانة أسهل

---

## نصائح مهمة

1. **الـ Entities لا تحتوي على منطق معقد** - فقط بيانات
2. **الـ Repositories لا تحتوي على منطق الأعمال** - فقط قراءة/كتابة DB
3. **الـ Use Cases تحتوي على كل المنطق** - الترتيب والتسلسل
4. **الـ Controllers تكون قصيرة وبسيطة** - فقط استدعاء Use Case
5. **الـ Domain لا يعتمد على أي حاجة خارجية** - pure business logic

---

## الأسئلة الشائعة

**س: كيف أختبر الـ Use Case؟**
جواب:
```php
public function test_create_idea_use_case()
{
    $mockRepo = Mockery::mock(IdeaRepositoryInterface::class);
    $mockAI = Mockery::mock(AIService::class);
    
    $useCase = new CreateIdeaUseCase($mockRepo, $mockAI);
    
    $mockAI->shouldReceive('analyzeIdea')
        ->andReturn(['analysis' => 'test', 'category' => 'test']);
    
    $mockRepo->shouldReceive('create')
        ->andReturn(new Idea(...));
    
    $result = $useCase->execute(1, 'test content');
    
    $this->assertNotNull($result);
}
```

**س: كيف أستخدم Use Case من CLI Command؟**
جواب:
```php
// commands/CreateIdeaCommand.php
public function handle()
{
    $useCase = app(CreateIdeaUseCase::class);
    $idea = $useCase->execute(
        $userId,
        'فكرة من CLI'
    );
    
    $this->info('تم الإنشاء بنجاح');
}
```

---

**مرحباً بك في عالم Clean Architecture! 🎉**

