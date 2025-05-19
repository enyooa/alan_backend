<?php

namespace App\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use App\Models\AuditLog;

class AuditEventSubscriber
{
    /**
     * Модели, изменения которых пишем в audit_logs.
     * Добавляйте / убирайте классы по необходимости.
     */
    protected array $auditedModels = [
        \App\Models\Order::class,
        \App\Models\OrderItem::class,

        \App\Models\Document::class,
        \App\Models\DocumentItem::class,
        \App\Models\Warehouse::class,
        \App\Models\WarehouseItem::class,
        \App\Models\ProductCard::class,
        \App\Models\ProductSubCard::class,
        \App\Models\FinancialOrder::class,
        \App\Models\FinancialElement::class,
        \App\Models\Expense::class,
        \App\Models\Unit_measurement::class,
        \App\Models\Provider::class,


    ];

    /* ------------------------------------------------------------------ */
    /* 1. РЕГИСТРАЦИЯ СЛУШАТЕЛЕЙ                                         */
    /* ------------------------------------------------------------------ */
    public function subscribe(Dispatcher $events): void
    {
        // Слушаем ВСЕ created/updated/deleted — фильтруем позже
        $events->listen('eloquent.created: *',  [self::class, 'onCreated']);
        $events->listen('eloquent.updated: *',  [self::class, 'onUpdated']);
        $events->listen('eloquent.deleted: *',  [self::class, 'onDeleted']);
    }

    /* ------------------------------------------------------------------ */
    /* 2. ХЕЛПЕРЫ                                                         */
    /* ------------------------------------------------------------------ */

    /** Проверяем, надо ли писать лог именно для этой модели */
    protected function shouldAudit(object $model): bool
    {
        return
            ! ($model instanceof AuditLog) &&                 // не логируем сами себя
            in_array(get_class($model), $this->auditedModels, true);
    }

    /** Сохранить запись в audit_logs */
    protected function write(string $evt, object $model, array $old = [], array $new = []): void
    {
        if (! $this->shouldAudit($model)) {
            return;
        }

        AuditLog::withoutEvents(function () use ($evt, $model, $old, $new) {
            AuditLog::create([
                'id'             => (string) Str::uuid(),              // PK-UUID
                'user_id'        => Auth::id(),
                'event'          => $evt,                               // created / updated / deleted
                'auditable_type' => get_class($model),
                'auditable_id'   => (string) $model->getKey(),

                // сериализуем массивы, чтобы bindings были строками
                'old_values'     => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                'new_values'     => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,

                'url'            => Request::fullUrl(),
                'ip_address'     => Request::ip(),
            ]);
        });
    }

    /* ------------------------------------------------------------------ */
    /* 3. ОБРАБОТЧИКИ СОБЫТИЙ                                             */
    /* ------------------------------------------------------------------ */

    public function onCreated(string $eventName, array $data): void
    {
        [$model] = $data;
        $this->write('created', $model, [], $model->getAttributes());
    }

    public function onUpdated(string $eventName, array $data): void
    {
        [$model] = $data;
        $this->write('updated', $model, $model->getOriginal(), $model->getChanges());
    }

    public function onDeleted(string $eventName, array $data): void
    {
        [$model] = $data;
        $this->write('deleted', $model, $model->getOriginal(), []);
    }
}
