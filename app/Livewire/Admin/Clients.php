<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Hash;

class Clients extends Component
{
    use WithPagination;

    public $search = '';
    public $name, $email, $password, $role = 'client', $theme = 'default';
    public $editingUserId = null;
    public $showModal = false;

    protected ThemeService $themeService;

    public function boot(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function rules()
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . ($this->editingUserId ?? 'NULL'),
            'role'     => 'required|in:admin,client',
            'theme'    => 'required|in:default,gold',
            'password' => $this->editingUserId ? 'nullable|min:6' : 'required|min:6',
        ];
    }

    public function openModal($userId = null)
    {
        $this->reset(['name', 'email', 'password', 'role', 'theme', 'editingUserId']);
        $this->theme = 'default';
        $this->editingUserId = $userId;

        if ($userId) {
            $user = User::findOrFail($userId);
            $this->name  = $user->name;
            $this->email = $user->email;
            $this->role  = $user->role;
            $this->theme = $user->theme ?? 'default';
        }

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
            'theme' => $this->theme,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            User::findOrFail($this->editingUserId)->update($data);
        } else {
            $data['tenant_id'] = auth()->user()->tenant_id;
            User::create($data);
        }

        $this->showModal = false;
        $this->reset(['name', 'email', 'password', 'role', 'theme', 'editingUserId']);
        $this->theme = 'default';
    }

    public function delete($userId)
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'Вы не можете удалить самого себя.');
            return;
        }

        User::findOrFail($userId)->delete();
        $this->resetPage();
    }

    public function render()
    {
        $users = User::where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $layout = $this->themeService->getView('layouts.app');

        return view($this->themeService->getView('components.admin.clients'), [
            'users' => $users,
        ])->layout($layout, ['header' => 'Клиенты']);
    }
}
