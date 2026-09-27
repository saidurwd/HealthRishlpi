<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An action of an `os_acl_controller` (`os_acl_action`). Opening a group's
 * access page creates the matching `os_acl` rows.
 *
 * @property int $id
 * @property int $controller_id
 * @property string $title
 * @property string $action Yii action id ("update")
 */
#[Table('acl_action', timestamps: false)]
#[Fillable(['controller_id', 'title', 'action'])]
class AclAction extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'controller_id' => 'Controller', 'title' => 'Action Title', 'action' => 'Action'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'controller_id' => ['required', 'integer'],
            'action' => ['required', 'max:150'],
            'title' => ['required', 'max:150'],
        ];
    }

    /** @return BelongsTo<AclController, $this> */
    public function controller0(): BelongsTo
    {
        return $this->belongsTo(AclController::class, 'controller_id');
    }
}
