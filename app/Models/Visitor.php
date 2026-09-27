<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Page view log (`os_visitor`). The Yii app stopped writing it (its
 * Controller::statistics() call is commented out); the admin screen still
 * lists, prunes and deletes rows.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $server_time
 */
#[Table('visitor', timestamps: false)]
class Visitor extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'user_id' => 'User',
            'user_name' => 'User Name',
            'page_title' => 'Page Title',
            'page_link' => 'Page Link',
            'server_time' => 'Server Time',
            'browser' => 'Browser',
            'visitor_ip' => 'Visitor Ip',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
