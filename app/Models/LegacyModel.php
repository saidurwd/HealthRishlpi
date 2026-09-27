<?php

namespace App\Models;

use App\Models\Concerns\HasAttributeLabels;
use App\Models\Concerns\TypecastsLikeYii;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Base for models over the Yii app's tables.
 *
 * Relations keep the Yii relation names (country0, unit0, ...) because the
 * foreign key columns already use the plain names (country, unit, ...).
 *
 * Every create, change and delete made through a model is written to the
 * activity log (who, when, old and new values); set
 * `protected static $recordEvents = []` on models not worth logging.
 *
 * @property int $id every legacy table has an auto-increment id
 */
abstract class LegacyModel extends Model
{
    // Options of the Active/Inactive `status` enum most lookup tables have
    public const STATUSES = ['Active' => 'Active', 'Inactive' => 'Inactive'];

    use HasAttributeLabels, HasFactory, LogsActivity, TypecastsLikeYii;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('data')->logAll()->logOnlyDirty()->dontLogEmptyChanges();
    }

    /**
     * Validation rules for a create (null) or an update of $model. Every
     * attribute a form may set must appear here; fields Yii only marked
     * "safe" get ['nullable'].
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?self $model = null): array
    {
        return [];
    }
}
