<?php

namespace App\Models;

use App\Models\Concerns\HasAttributeLabels;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Application user (`os_user`).
 *
 * Passwords are unsalted SHA1, exactly as the Yii app stores them, so both
 * apps can log in against the same rows until cutover.
 *
 * `os_user` has no remember_token column, so the "remember me" token is
 * derived instead of stored (see getRememberToken()).
 *
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string $email
 * @property string $password
 * @property int|null $group_id
 * @property int|null $department
 * @property int|null $status
 * @property string|null $photo
 */
#[Table('user', timestamps: false)]
#[Fillable(['full_name', 'username', 'email', 'password', 'register_date', 'lastvisit', 'activation', 'group_id', 'department', 'status', 'photo'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    use HasAttributeLabels;

    // Values of os_user.status that block login (UserIdentity::ERROR_STATUS_*)
    public const STATUS_NOT_ACTIVE = 2;

    public const STATUS_BANNED = 3;

    public const STATUS_EXPIRED = 4;

    // Group 1 is the super user group (the Yii app's `User::get_reference_id() == 1` checks)
    public const SUPER_GROUP = 1;

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'full_name' => 'Name',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'register_date' => 'Register Date',
            'lastvisit' => 'Last Visit',
            'activation' => 'Activation',
            'group_id' => 'Group',
            'department' => 'Department',
            'status' => 'Status',
            'picture' => 'Picture',
        ];
    }

    public static function hashPassword(string $plain): string
    {
        return sha1($plain);
    }

    public function passwordMatches(string $plain): bool
    {
        return hash_equals((string) $this->password, self::hashPassword($plain));
    }

    /**
     * The "remember me" token: an HMAC of the user id and password hash, keyed
     * with APP_KEY. Like the Yii app's auto-login cookie it needs no database
     * column; changing the password or APP_KEY invalidates remembered logins.
     */
    public function getRememberToken(): string
    {
        return hash_hmac('sha256', $this->getAuthIdentifier().'|'.$this->password, (string) config('app.key'));
    }

    /**
     * The token is derived, so there is nothing to store (Laravel calls this
     * on login and logout to rotate a stored token).
     */
    public function setRememberToken($value): void {}

    public function isSuper(): bool
    {
        return (int) $this->group_id === self::SUPER_GROUP;
    }

    /**
     * URL of the user's thumbnail, falling back to the default avatar
     * (the Yii app's User::get_profile_picture()).
     */
    public function photoUrl(): string
    {
        $photo = (string) $this->photo;

        if ($photo !== '' && is_file(public_path('uploads/user/thumb/'.$photo))) {
            return asset('uploads/user/thumb/'.$photo);
        }

        return asset('uploads/user/thumb/male.png');
    }
}
