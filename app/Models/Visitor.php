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
 * @property string|null $user_name
 * @property string|null $page_title
 * @property string|null $page_link
 * @property string|null $browser
 * @property string|null $visitor_ip
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
