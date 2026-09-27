<?php

namespace App\Models;

use App\Models\Concerns\HasAttributeLabels;
use App\Models\Concerns\TypecastsLikeYii;
use App\Rules\YiiEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Validation\Rule;
use Spatie\Permission\Traits\HasRoles;

/**
 * Application user (`os_user`).
 *
 * Passwords are unsalted SHA1, exactly as the Yii app stores them, so both
 * apps can log in against the same rows until cutover.
 *
 * `os_user` has no remember_token column, so the "remember me" token is
 * derived instead of stored (see getRememberToken()).
 *
 * `group_id` is the user's group; its role (same id) is kept in step on
 * every save, so permission checks ($user->can('patient.admin')) follow it.
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
 * @property string|null $register_date
 * @property string|null $lastvisit
 * @property string|null $activation
 * @property string|null $picture
 */
#[Table('user', timestamps: false)]
#[Fillable(['full_name', 'username', 'email', 'password', 'register_date', 'lastvisit', 'activation', 'group_id', 'department', 'status', 'photo'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasAttributeLabels, HasFactory, HasRoles, TypecastsLikeYii;

    // Values of os_user.status that block login (UserIdentity::ERROR_STATUS_*)
    public const STATUS_NOT_ACTIVE = 2;

    public const STATUS_BANNED = 3;

    public const STATUS_EXPIRED = 4;

    // Options of the tinyint `status` column on the user form
    public const ACTIVE_STATUSES = ['0' => 'Inactive', '1' => 'Active'];

    // Group 1 is the super user group (the Yii app's `User::get_reference_id() == 1` checks); it passes every permission check
    public const SUPER_GROUP = 1;

    protected static function booted(): void
    {
        static::saved(function (self $user) {
            if ($user->wasRecentlyCreated || $user->wasChanged('group_id')) {
                $user->syncRoles(Role::query()->whereKey($user->group_id)->get());
            }
        });
    }

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

    /**
     * Rules of the create/update form. The password is only on the create
     * form (it has its own "change password" form), and `photo` is the
     * uploaded file, which Yii did not validate.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?self $model = null): array
    {
        return array_merge([
            'full_name' => ['required', 'max:150'],
            'username' => ['required', 'max:100', Rule::unique(self::class)->ignore($model?->getKey())],
            'email' => ['required', 'max:100', Rule::unique(self::class)->ignore($model?->getKey()), new YiiEmail],
        ], $model === null ? ['password' => ['required', 'max:100']] : [], [
            'group_id' => ['integer'],
            'department' => ['integer'],
            'status' => ['integer'],
            'photo' => ['nullable', 'image'],
        ]);
    }

    /** @return BelongsTo<Role, $this> */
    public function group0(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'group_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department0(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department');
    }

    /** @return BelongsTo<UserStatus, $this> */
    public function status0(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class, 'status');
    }

    /**
     * Yii's Yii::app()->user->name: what the user typed to sign in (username
     * or email), used in prescription and invoice numbers. A session
     * restored by "remember me" falls back to the username.
     */
    public static function loginName(): string
    {
        return (string) session('login_name', auth()->user()?->username);
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
