<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // Since we are using Livewire/Volt, this controller might be less used for views, 
    // but good for API or standard resource actions if needed.
    // However, the prompt asked for a Controller.
    // I will implement standard methods that could be used by API or if we weren't using Volt for everything.
    // But wait, if we use Volt, the logic often lives in the Volt component or the Volt component calls the Service directly.
    // The prompt said: "4. a Controller".
    // I will make it a standard resource controller but maybe the Volt views will use the Service directly.
    // Or the Volt views will be the "Controller" in the Livewire sense.
    // Let's stick to the request: "a Controller".

    public function index()
    {
        // If we are using Volt for the view, we might just return the view here.
        return view('livewire.users.index');
    }

    // Other methods might not be needed if Volt handles the UI interactions (create/edit modals).
    // But I'll add them for completeness as requested.
    
    public function store(UserRequest $request)
    {
        $this->userService->createUser($request->validated());
        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function update(UserRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->userService->deleteUser($user);
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
