<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HelpArticle;

class HelpArticlePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view help articles');
    }

    public function view(User $user, HelpArticle $helpArticle): bool
    {
        return $user->can('view help articles');
    }

    public function create(User $user): bool
    {
        return $user->can('create help articles');
    }

    public function update(User $user, HelpArticle $helpArticle): bool
    {
        return $user->can('edit help articles');
    }

    public function delete(User $user, HelpArticle $helpArticle): bool
    {
        return $user->can('delete help articles');
    }

    public function restore(User $user, HelpArticle $helpArticle): bool
    {
        return $user->can('restore help articles');
    }

    public function forceDelete(User $user, HelpArticle $helpArticle): bool
    {
        return $user->can('force_delete help articles');
    }

    public function export(User $user): bool
    {
        return $user->can('export help articles');
    }

    public function import(User $user): bool
    {
        return $user->can('import help articles');
    }
}
