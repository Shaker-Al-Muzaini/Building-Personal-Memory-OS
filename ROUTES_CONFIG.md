# 🛣️ Routes Configuration - Clean Architecture

## كيفية تنظيم الـ Routes

بعد إعادة الهيكلة، يجب تحديث الـ Routes للإشارة إلى الـ Controllers الجديدة.

---

## مثال: routes/web.php

```php
<?php

use App\Presentation\Controllers\DashboardController;
use App\Presentation\Controllers\IdeaController;
use App\Presentation\Controllers\DecisionController;
use App\Presentation\Controllers\PeopleController;
use App\Presentation\Controllers\MoneyController;
use App\Presentation\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ideas
    Route::prefix('ideas')->group(function () {
        Route::get('/', [IdeaController::class, 'index'])->name('ideas.index');
        Route::post('/', [IdeaController::class, 'store'])->name('ideas.store');
        Route::patch('/{id}/status', [IdeaController::class, 'updateStatus'])->name('ideas.updateStatus');
        Route::delete('/{id}', [IdeaController::class, 'destroy'])->name('ideas.destroy');
    });

    // Decisions
    Route::prefix('decisions')->group(function () {
        Route::get('/', [DecisionController::class, 'index'])->name('decisions.index');
        Route::post('/', [DecisionController::class, 'store'])->name('decisions.store');
    });

    // People
    Route::prefix('people')->group(function () {
        Route::get('/', [PeopleController::class, 'index'])->name('people.index');
        Route::post('/', [PeopleController::class, 'store'])->name('people.store');
    });

    // Money/Budget
    Route::prefix('money')->group(function () {
        Route::get('/', [MoneyController::class, 'index'])->name('money.index');
        Route::post('/transactions', [MoneyController::class, 'storeTransaction'])->name('money.storeTransaction');
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
```

---

## مثال: routes/api.php

إذا كنت تريد API فقط:

```php
<?php

use App\Presentation\Controllers\IdeaController;
use App\Presentation\Controllers\DecisionController;
use App\Presentation\Controllers\PeopleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Ideas
    Route::apiResource('ideas', IdeaController::class);
    Route::patch('ideas/{id}/status', [IdeaController::class, 'updateStatus']);

    // Decisions
    Route::apiResource('decisions', DecisionController::class);

    // People
    Route::apiResource('people', PeopleController::class);
});
```

---

## Namespace التحديث

### قديم:
```php
use App\Http\Controllers\IdeaController;
```

### جديد:
```php
use App\Presentation\Controllers\IdeaController;
```

---

## إذا كان لديك Middleware

### أمثلة على Middleware الجديدة

```php
// app/Presentation/Middleware/CheckIdea.php

<?php

namespace App\Presentation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIdea
{
    public function handle(Request $request, Closure $next): Response
    {
        // تحقق من أن الفكرة تابعة للمستخدم الحالي
        $idea = $request->route('idea');
        
        if ($idea && $idea->user_id !== $request->user()->id) {
            abort(403);
        }

        return $next($request);
    }
}
```

### تطبيق Middleware
```php
Route::prefix('ideas')->middleware('check_idea')->group(function () {
    Route::patch('/{id}/status', [IdeaController::class, 'updateStatus']);
    Route::delete('/{id}', [IdeaController::class, 'destroy']);
});
```

---

## الخطوات لتحديث الـ Routes

1. **افتح routes/web.php أو routes/api.php**

2. **غيّر use statements**:
```diff
- use App\Http\Controllers\IdeaController;
+ use App\Presentation\Controllers\IdeaController;
```

3. **استخدم الـ routes بنفس الطريقة السابقة**

4. **اختبر الـ routes**:
```bash
php artisan route:list
```

---

## Form Requests

### استخدام Form Requests الجديدة

```php
// app/Presentation/Requests/StoreIdeaRequest.php

<?php

namespace App\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'محتوى الفكرة مطلوب',
            'content.max' => 'محتوى الفكرة لا يجب أن يتجاوز 2000 حرف',
        ];
    }
}
```

### استخدامها في الـ Controller

```php
use App\Presentation\Requests\StoreIdeaRequest;

public function store(StoreIdeaRequest $request)
{
    // $request محقق بالفعل من الـ Form Request
    $idea = $this->createIdeaUseCase->execute(
        $request->user()->id,
        $request->input('content')
    );

    return back();
}
```

---

## Resources (API Responses)

### إنشاء Resource

```php
// app/Presentation/Resources/IdeaResource.php

<?php

namespace App\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdeaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'category' => $this->category,
            'status' => $this->status,
            'ai_analysis' => $this->aiAnalysis,
            'created_at' => $this->createdAt?->toIso8601String(),
        ];
    }
}
```

### استخدامها في الـ Controller

```php
use App\Presentation\Resources\IdeaResource;

public function index(Request $request)
{
    $ideas = $this->getIdeasUseCase->execute($request->user()->id);
    
    return IdeaResource::collection($ideas);
}
```

---

## ملاحظات مهمة

1. **تأكد من تحديث جميع الـ imports** في الـ routes
2. **اختبر كل Route بعد التحديث**
3. **استخدم route:list للتحقق**:
   ```bash
   php artisan route:list
   ```

4. **إذا كان يمكنك، استخدم route:clear**:
   ```bash
   php artisan route:clear
   php artisan config:cache
   ```

---

**تم تحديث البنية بنجاح! 🎉**

