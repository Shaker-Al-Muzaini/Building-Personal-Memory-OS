# 🏗️ Clean Architecture - دليل البنية الجديدة

## نظرة عامة

تم إعادة هيكلة المشروع بناءً على **Clean Architecture Pattern**، وهي معمارية احترافية تفصل بين المسؤولياتالمختلفة للتطبيق.

## الطبقات الخمس

### 1️⃣ **Presentation Layer** (`app/Presentation/`)
- **المسؤولية**: واجهة التطبيق - التعامل مع الطلبات والاستجابات
- **المحتوى**:
  - `Controllers/` - معالجات الطلبات HTTP
  - `Requests/` - Form Requests و Validations
  - `Resources/` - API Resources و Data Formatting
  - `Middleware/` - وسيطات الطلب

**مثال**: Controller يستقبل HTTP Request ويستدعي Use Case

```php
public function store(Request $request)
{
    $idea = $this->createIdeaUseCase->execute(
        $request->user()->id,
        $request->input('content')
    );
    return back();
}
```

---

### 2️⃣ **Application Layer** (`app/Application/`)
- **المسؤولية**: منطق الأعمال - تنسيق العمليات المعقدة
- **المحتوى**:
  - `UseCases/` - حالات تطبيق مختلفة
  - `Services/` - خدمات عامة (AI, Email, etc.)

**مثال**: Use Case تتعامل مع تحليل الفكرة بالـ AI

```php
// CreateIdeaUseCase.php
$analysis = $this->aiService->analyzeIdea($userId, $content);
$idea = new Idea(...);
return $this->ideaRepository->create($idea);
```

**Use Cases المتوفرة**:
- `Ideas/CreateIdeaUseCase` - إنشاء فكرة جديدة
- `Ideas/GetIdeasUseCase` - جلب الأفكار
- `Decisions/CreateDecisionUseCase` - إنشاء قرار
- `Money/GetBudgetSummaryUseCase` - ملخص الميزانية
- `People/CreatePersonUseCase` - إضافة شخص

---

### 3️⃣ **Domain Layer** (`app/Domain/`)
- **المسؤولية**: قوانين العمل الأساسية - الكيانات والعقود
- **المحتوى**:
  - `Entities/` - كلاسات الكيانات (Idea, User, Decision, etc.)
  - `Repositories/Contracts/` - واجهات Repository (العقود)
  - `ValueObjects/` - قيم غير قابلة للتغيير (اختياري)

**مثال**: Entity Idea

```php
// Domain/Entities/Idea.php
class Idea
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $content,
        public ?string $aiAnalysis,
        public string $status,
        public string $category,
    ) {}
}
```

**Repository Interfaces**:
- `IdeaRepositoryInterface` - عقد عمليات الأفكار
- `DecisionRepositoryInterface` - عقد عمليات القرارات
- `TransactionRepositoryInterface` - عقد العمليات المالية
- `PersonRepositoryInterface` - عقد إدارة الأشخاص

---

### 4️⃣ **Infrastructure Layer** (`app/Infrastructure/`)
- **المسؤولية**: التفاعل مع العالم الخارجي - قاعدة البيانات والخدمات الخارجية
- **المحتوى**:
  - `Repositories/` - تطبيق Repository Interfaces
  - `Services/` - خدمات خارجية (API integrations, etc.)

**مثال**: تطبيق Repository

```php
// Infrastructure/Repositories/EloquentIdeaRepository.php
class EloquentIdeaRepository implements IdeaRepositoryInterface
{
    public function create(Idea $idea): Idea
    {
        $id = DB::table('ideas')->insertGetId([...]);
        return $idea;
    }
}
```

**Repositories المتوفرة**:
- `EloquentIdeaRepository` - عمليات الأفكار على DB
- `EloquentDecisionRepository` - عمليات القرارات
- `EloquentTransactionRepository` - العمليات المالية
- `EloquentPersonRepository` - إدارة الأشخاص

---

### 5️⃣ **Database Layer** (`database/`)
- **المسؤولية**: قاعدة البيانات - MySQL/SQL
- **المحتوى**:
  - `migrations/` - تحديثات البنية
  - `seeds/` - بيانات أولية

---

## تدفق الطلب (Request Flow)

```
HTTP Request
    ↓
┌─────────────────────────────────┐
│  Presentation Layer             │
│  (IdeaController@store)         │
└─────────────┬───────────────────┘
              ↓
┌─────────────────────────────────┐
│  Application Layer              │
│  (CreateIdeaUseCase)            │
│  - تحليل Idea بـ AI             │
│  - إنشاء Idea Entity            │
└─────────────┬───────────────────┘
              ↓
┌─────────────────────────────────┐
│  Domain Layer                   │
│  (Idea Entity)                  │
│  - إنشاء Object جديد            │
└─────────────┬───────────────────┘
              ↓
┌─────────────────────────────────┐
│  Infrastructure Layer           │
│  (EloquentIdeaRepository)       │
│  - حفظ في قاعدة البيانات       │
└─────────────┬───────────────────┘
              ↓
┌─────────────────────────────────┐
│  Database Layer                 │
│  (ideas table - MySQL)          │
└─────────────────────────────────┘
```

---

## مثال عملي: إنشاء فكرة جديدة

### 1. الطلب يصل للـ Controller

```php
// app/Presentation/Controllers/IdeaController.php
public function store(Request $request)
{
    $request->validate(['content' => 'required|string']);
    
    $idea = $this->createIdeaUseCase->execute(
        $request->user()->id,
        $request->input('content')
    );
    
    return back();
}
```

### 2. Use Case تنفذ المنطق

```php
// app/Application/UseCases/Ideas/CreateIdeaUseCase.php
public function execute(int $userId, string $content): Idea
{
    // تحليل بـ AI
    $analysis = $this->aiService->analyzeIdea($userId, $content);
    
    // إنشاء كيان
    $idea = new Idea(
        id: 0,
        userId: $userId,
        content: $content,
        aiAnalysis: $analysis['analysis'],
        status: 'draft',
        category: $analysis['category']
    );
    
    // حفظ
    return $this->ideaRepository->create($idea);
}
```

### 3. Repository يتعامل مع DB

```php
// app/Infrastructure/Repositories/EloquentIdeaRepository.php
public function create(Idea $idea): Idea
{
    $id = DB::table('ideas')->insertGetId([
        'user_id' => $idea->userId,
        'content' => $idea->content,
        'ai_analysis' => $idea->aiAnalysis,
        'status' => $idea->status,
        'category' => $idea->category,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $idea->id = $id;
    return $idea;
}
```

---

## الفوائد

✅ **الفصل الواضح بين المسؤولياتـ** - كل طبقة لديها مسؤولية واحدة فقط
✅ **سهولة الاختبار** - يمكن اختبار كل طبقة بشكل مستقل
✅ **إعادة الاستخدام** - Use Cases يمكن استخدامها في API و Web Controllers
✅ **سهولة الصيانة** - الكود منظم وقابل للتطور
✅ **استقلالية الإطار** - اذا أردت تغيير من Laravel لـ Symfony مثلاً، الـ Domain و Application لا تتغير

---

## كيفية الإضافة

### إذا أردت إضافة Feature جديد (مثل Tasks):

#### 1. أنشئ Entity

```php
// app/Domain/Entities/Task.php
class Task { ... }
```

#### 2. أنشئ Repository Interface

```php
// app/Domain/Repositories/Contracts/TaskRepositoryInterface.php
interface TaskRepositoryInterface { ... }
```

#### 3. أنشئ Repository Implementation

```php
// app/Infrastructure/Repositories/EloquentTaskRepository.php
class EloquentTaskRepository implements TaskRepositoryInterface { ... }
```

#### 4. أنشئ Use Cases

```php
// app/Application/UseCases/Tasks/CreateTaskUseCase.php
// app/Application/UseCases/Tasks/GetTasksUseCase.php
```

#### 5. أنشئ Controller

```php
// app/Presentation/Controllers/TaskController.php
```

#### 6. سجل في Service Provider

```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(TaskRepositoryInterface::class, EloquentTaskRepository::class);
```

---

**مرحباً بك في عالم Clean Architecture! 🎉**

