<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Repositories\Contracts\IdeaRepositoryInterface;
use App\Domain\Repositories\Contracts\DecisionRepositoryInterface;
use App\Domain\Repositories\Contracts\TransactionRepositoryInterface;
use App\Domain\Repositories\Contracts\PersonRepositoryInterface;
use App\Domain\Repositories\Contracts\UserRepositoryInterface;
use App\Infrastructure\Repositories\EloquentIdeaRepository;
use App\Infrastructure\Repositories\EloquentDecisionRepository;
use App\Infrastructure\Repositories\EloquentTransactionRepository;
use App\Infrastructure\Repositories\EloquentPersonRepository;
use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Application\Services\AIServiceClient;
use App\Infrastructure\Services\AIService;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ربط Repository Interfaces مع تطبيقاتها
        $this->app->bind(IdeaRepositoryInterface::class, EloquentIdeaRepository::class);
        $this->app->bind(DecisionRepositoryInterface::class, EloquentDecisionRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, EloquentTransactionRepository::class);
        $this->app->bind(PersonRepositoryInterface::class, EloquentPersonRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

        // ربط خدمات الـ AI
        $this->app->singleton(AIServiceClient::class, function ($app) {
            return new AIServiceClient(config('services.groq.key'));
        });

        $this->app->singleton(AIService::class, function ($app) {
            return new AIService(
                $app->make(AIServiceClient::class),
                $app->make(PersonRepositoryInterface::class),
                $app->make(DecisionRepositoryInterface::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}

