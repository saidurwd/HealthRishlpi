<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Support\Grid;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * User accounts. Passwords are stored as unsalted SHA1 (see User); the
 * update form has no password field, "edit" changes it.
 */
class UserController extends Controller
{
    private const PAGE = ['route' => 'user', 'plural' => 'Users', 'singular' => 'User'];

    public function admin(): View
    {
        $grid = Grid::for(User::query()->with('group0'))
            ->compare('id')
            ->compare('full_name', partial: true)
            ->compare('username', partial: true)
            ->compare('email', partial: true)
            ->compare('password', partial: true)
            ->compare('register_date', partial: true)
            ->compare('lastvisit', partial: true)
            ->compare('activation', partial: true)
            ->compare('group_id')
            ->compare('department')
            ->compare('status')
            ->defaultOrder('full_name')
            ->paginate(config('legacy.pageSize20'));

        return view('user.admin', [
            'grid' => $grid,
            'page' => self::PAGE,
            'groups' => Role::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function view(int $id): View
    {
        return view('user.view', ['record' => $this->find($id)]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $record = new User(['status' => 1]);

        if ($request->isMethod('post')) {
            $input = $this->validated($request);
            $record->forceFill($input);
            $record->password = User::hashPassword($input['password']);
            $record->register_date = now()->format('Y-m-d G:i:s');
            $record->activation = md5(microtime());
            $record->photo = $request->hasFile('photo') ? $this->storePhoto($request->file('photo')) : '';
            $record->save();

            return redirect()->route('user.admin')->with('success', 'User was saved successfully');
        }

        return $this->form('create', $record);
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $record = $this->find($id);

        if ($request->isMethod('post')) {
            $record->forceFill($this->validated($request, $record));

            if ($request->hasFile('photo')) {
                $this->deletePhoto((string) $record->getOriginal('photo'));
                $record->photo = $this->storePhoto($request->file('photo'));
            }

            $record->save();

            return redirect()->route('user.admin')->with('success', 'User was saved successfully');
        }

        return $this->form('update', $record);
    }

    /**
     * Change password.
     */
    public function edit(Request $request, int $id): View|RedirectResponse
    {
        $record = $this->find($id);

        if ($request->isMethod('post')) {
            $input = $request->validate(['password' => ['required', 'max:100']], [], ['password' => User::label('password')]);
            $record->password = User::hashPassword($input['password']);
            $record->save();

            return redirect()->route('user.admin')->with('success', 'Password was changed successfully');
        }

        return view('user.edit', ['record' => $record, 'page' => self::PAGE]);
    }

    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        $record = $this->find($id);

        try {
            $record->delete();
        } catch (QueryException $e) {
            return $this->deleteFailed($e);
        }

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('user.admin')));
    }

    /**
     * Validated form fields, without the uploaded photo.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $record = null): array
    {
        $rules = User::rules($record);
        $input = $request->validate($rules, [], User::labelsFor(array_keys($rules)));
        unset($input['photo']);

        return $input;
    }

    private function form(string $action, User $record): View
    {
        return view('crud.'.$action, [
            'record' => $record,
            'page' => self::PAGE,
            'form' => 'user._form',
            'multipart' => true,
            'groups' => Role::query()->orderBy('id')->pluck('name', 'id'),
            'departments' => Department::query()->pluck('title', 'id'),
        ]);
    }

    private function find(int $id): User
    {
        return User::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }

    /**
     * Save the upload as uploads/user/<time>_<lowercased name, spaces as _>
     * plus a thumbnail scaled to fit 400x100 in uploads/user/thumb, as the
     * Yii app did. Returns the file name for `os_user.photo`.
     */
    private function storePhoto(UploadedFile $file): string
    {
        $name = time().'_'.str_replace(' ', '_', strtolower($file->getClientOriginalName()));
        $file->move(public_path('uploads/user'), $name);

        $source = public_path('uploads/user/'.$name);
        $image = imagecreatefromstring((string) file_get_contents($source));

        if ($image !== false) {
            $scale = min(400 / imagesx($image), 100 / imagesy($image));
            $thumb = imagescale($image, max(1, (int) round(imagesx($image) * $scale)), max(1, (int) round(imagesy($image) * $scale)));
            $target = public_path('uploads/user/thumb/'.$name);

            match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
                'png' => imagepng($thumb, $target),
                'gif' => imagegif($thumb, $target),
                'webp' => imagewebp($thumb, $target),
                default => imagejpeg($thumb, $target, 100),
            };
        }

        return $name;
    }

    /**
     * Remove a replaced photo. The shared default avatar is never removed
     * (Yii would have deleted it too if a user's photo pointed at it).
     */
    private function deletePhoto(string $name): void
    {
        if ($name === '' || $name === 'male.png') {
            return;
        }

        foreach (['uploads/user/', 'uploads/user/thumb/'] as $dir) {
            if (is_file(public_path($dir.$name))) {
                unlink(public_path($dir.$name));
            }
        }
    }
}
