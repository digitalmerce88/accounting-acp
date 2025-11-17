<?php

namespace App\Domain\Documents;

use App\Models\ApprovalLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public static function submit(Model $model, int $businessId, int $userId, ?string $comment = null): void
    {
        DB::transaction(function () use ($model, $businessId, $userId, $comment) {
            $model->approval_status = 'submitted';
            $model->submitted_by = $userId;
            $model->submitted_at = now();
            $model->save();
            ApprovalLog::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'action' => 'submitted',
                'comment' => $comment,
            ]);
        });
    }

    public static function approve(Model $model, int $businessId, int $userId, ?string $comment = null): void
    {
        DB::transaction(function () use ($model, $businessId, $userId, $comment) {
            $model->approval_status = 'approved';
            $model->approved_by = $userId;
            $model->approved_at = now();
            $model->save();
            ApprovalLog::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'action' => 'approved',
                'comment' => $comment,
            ]);
        });
    }

    public static function lock(Model $model, int $businessId, int $userId, ?string $comment = null): void
    {
        DB::transaction(function () use ($model, $businessId, $userId, $comment) {
            $model->approval_status = 'locked';
            $model->locked_by = $userId;
            $model->locked_at = now();
            $model->save();
            ApprovalLog::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'action' => 'locked',
                'comment' => $comment,
            ]);
        });
    }

    public static function unlock(Model $model, int $businessId, int $userId, ?string $comment = null): void
    {
        DB::transaction(function () use ($model, $businessId, $userId, $comment) {
            $model->approval_status = 'approved';
            $model->save();
            ApprovalLog::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'action' => 'unlocked',
                'comment' => $comment,
            ]);
        });
    }
}
