<?php

use Livewire\Volt\Component;
use App\Models\HelpCategory;
use App\Models\HelpArticle;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use Toast;

    public $categories;
    public $articles;
    
    // Category Form
    public $showCategoryModal = false;
    public $category_id;
    public $category_name;
    public $category_description;
    public $category_order = 0;
    public $category_is_active = true;

    // Article Form
    public $showArticleModal = false;
    public $article_id;
    public $article_title;
    public $article_content;
    public $article_category_id;
    public $article_is_published = true;
    public $article_order = 0;

    // Delete
    public $showDeleteModal = false;
    public $deleteType; // 'category' or 'article'
    public $deleteId;

    public function mount()
    {
        $this->authorize('viewAny', HelpCategory::class);
        $this->authorize('viewAny', HelpArticle::class);
        $this->loadData();
    }

    public function loadData()
    {
        $this->categories = HelpCategory::orderBy('order')->get();
        $this->articles = HelpArticle::with('category')->orderBy('order')->get();
    }

    // --- Category Management ---

    public function createCategory()
    {
        $this->authorize('create', HelpCategory::class);
        $this->reset(['category_id', 'category_name', 'category_description', 'category_order', 'category_is_active']);
        $this->category_is_active = true;
        $this->showCategoryModal = true;
    }

    public function editCategory(HelpCategory $category)
    {
        $this->authorize('update', $category);
        $this->category_id = $category->id;
        $this->category_name = $category->name;
        $this->category_description = $category->description;
        $this->category_order = $category->order;
        $this->category_is_active = $category->is_active;
        $this->showCategoryModal = true;
    }

    public function saveCategory()
    {
        $validated = $this->validate([
            'category_name' => 'required|string|max:255',
            'category_description' => 'nullable|string',
            'category_order' => 'integer',
            'category_is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->category_name,
            'slug' => Str::slug($this->category_name),
            'description' => $this->category_description,
            'order' => $this->category_order,
            'is_active' => $this->category_is_active,
        ];

        if ($this->category_id) {
            $category = HelpCategory::find($this->category_id);
            $this->authorize('update', $category);
            $category->update($data);
            $this->success('Category updated.');
        } else {
            $this->authorize('create', HelpCategory::class);
            HelpCategory::create($data);
            $this->success('Category created.');
        }
        
        $this->showCategoryModal = false;
        $this->loadData();
    }

    // --- Article Management ---

    public function createArticle()
    {
        $this->authorize('create', HelpArticle::class);
        $this->reset(['article_id', 'article_title', 'article_content', 'article_category_id', 'article_is_published', 'article_order']);
        $this->article_is_published = true;
        $this->article_category_id = $this->categories->first()->id ?? null;
        $this->showArticleModal = true;
    }

    public function editArticle(HelpArticle $article)
    {
        $this->authorize('update', $article);
        $this->article_id = $article->id;
        $this->article_title = $article->title;
        $this->article_content = $article->content;
        $this->article_category_id = $article->help_category_id;
        $this->article_is_published = $article->is_published;
        $this->article_order = $article->order;
        $this->showArticleModal = true;
    }

    public function saveArticle()
    {
        $this->validate([
            'article_title' => 'required|string|max:255',
            'article_content' => 'required|string',
            'article_category_id' => 'required|exists:help_categories,id',
            'article_is_published' => 'boolean',
            'article_order' => 'integer',
        ]);

        $data = [
            'title' => $this->article_title,
            'slug' => Str::slug($this->article_title),
            'content' => $this->article_content,
            'help_category_id' => $this->article_category_id,
            'is_published' => $this->article_is_published,
            'order' => $this->article_order,
        ];

        if ($this->article_id) {
            $article = HelpArticle::find($this->article_id);
            $this->authorize('update', $article);
            $article->update($data);
            $this->success('Article updated.');
        } else {
            $this->authorize('create', HelpArticle::class);
            HelpArticle::create($data);
            $this->success('Article created.');
        }
        $this->showArticleModal = false;
        $this->loadData();
    }

    public function confirmDelete($type, $id)
    {
        if ($type === 'category') {
            $this->authorize('delete', HelpCategory::find($id));
        } else {
            $this->authorize('delete', HelpArticle::find($id));
        }
        $this->deleteType = $type;
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->deleteType === 'category') {
            $category = HelpCategory::find($this->deleteId);
            $this->authorize('delete', $category);
            $category->delete();
            $this->success('Category deleted.');
        } else {
            $article = HelpArticle::find($this->deleteId);
            $this->authorize('delete', $article);
            $article->delete();
            $this->success('Article deleted.');
        }
        $this->showDeleteModal = false;
        $this->loadData();
    }

    public function togglePublished($id)
    {
        $article = HelpArticle::find($id);
        $this->authorize('update', $article);
        $article->update(['is_published' => !$article->is_published]);
        $this->success('Article status updated.');
        $this->loadData();
    }
}; ?>

<div>
    <x-mary-header title="Help Center Management" separator>
        <x-slot:actions>
            @can('create help categories')
                <x-mary-button label="New Category" icon="o-folder-plus" wire:click="createCategory" class="btn-outline" />
            @endcan
            @can('create help articles')
                <x-mary-button label="New Article" icon="o-document-plus" wire:click="createArticle" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <div class="grid lg:grid-cols-3 gap-8">
        
        {{-- Categories List --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="font-bold text-lg flex items-center gap-2">
                <x-mary-icon name="o-folder" class="w-5 h-5" /> Categories
            </div>
            
            @foreach($categories as $category)
                <div class="bg-base-100 p-4 rounded-lg shadow-sm border border-base-200 flex justify-between items-center group">
                    <div>
                        <div class="font-bold">{{ $category->name }}</div>
                        <div class="text-xs text-gray-500">{{ $category->articles->count() }} articles</div>
                    </div>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        @can('edit help categories')
                            <x-mary-button icon="o-pencil" wire:click="editCategory({{ $category->id }})" class="btn-xs btn-ghost" />
                        @endcan
                        @can('delete help categories')
                            <x-mary-button icon="o-trash" wire:click="confirmDelete('category', {{ $category->id }})" class="btn-xs btn-ghost text-error" />
                        @endcan
                    </div>
                </div>
            @endforeach

            @if($categories->isEmpty())
                <div class="text-center p-4 text-gray-400 text-sm italic">No categories yet.</div>
            @endif
        </div>

        {{-- Articles List --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="font-bold text-lg flex items-center gap-2">
                <x-mary-icon name="o-document-text" class="w-5 h-5" /> Articles
            </div>

            <div class="bg-base-100 rounded-lg shadow-sm border border-base-200 overflow-hidden">
                @if($articles->count() > 0)
                    <table class="table w-full">
                        <thead>
                            <tr class="bg-base-200/50">
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($articles as $article)
                                <tr class="hover:bg-base-50">
                                    <td class="font-medium">{{ $article->title }}</td>
                                    <td>
                                        <x-mary-badge :value="$article->category->name ?? 'Uncategorized'" class="badge-ghost badge-sm" />
                                    </td>
                                    <td>
                                        @can('edit help articles')
                                            <button wire:click="togglePublished({{ $article->id }})" class="btn btn-xs {{ $article->is_published ? 'btn-success text-white' : 'btn-warning' }}">
                                                {{ $article->is_published ? 'Published' : 'Draft' }}
                                            </button>
                                        @else
                                            <span class="badge {{ $article->is_published ? 'badge-success text-white' : 'badge-warning' }}">
                                                {{ $article->is_published ? 'Published' : 'Draft' }}
                                            </span>
                                        @endcan
                                    </td>
                                    <td class="text-right">
                                        @can('edit help articles')
                                            <x-mary-button icon="o-pencil" wire:click="editArticle({{ $article->id }})" class="btn-sm btn-ghost" />
                                        @endcan
                                        @can('delete help articles')
                                            <x-mary-button icon="o-trash" wire:click="confirmDelete('article', {{ $article->id }})" class="btn-sm btn-ghost text-error" />
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center p-8 text-gray-400">No articles found.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- CATEGORY MODAL --}}
    <x-mary-modal wire:model="showCategoryModal" title="{{ $category_id ? 'Edit Category' : 'New Category' }}">
        <x-mary-form wire:submit="saveCategory">
            <x-mary-input label="Name" wire:model="category_name" />
            <x-mary-textarea label="Description" wire:model="category_description" />
            <div class="grid grid-cols-2 gap-4">
                <x-mary-input label="Order" wire:model="category_order" type="number" />
                <x-mary-toggle label="Active" wire:model="category_is_active" class="mt-4" />
            </div>
            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showCategoryModal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="saveCategory" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- ARTICLE MODAL --}}
    <x-mary-modal wire:model="showArticleModal" title="{{ $article_id ? 'Edit Article' : 'New Article' }}" class="backdrop-blur-md" box-class="w-11/12 max-w-4xl">
        <x-mary-form wire:submit="saveArticle">
            <x-mary-input label="Title" wire:model="article_title" />
            <x-mary-select label="Category" wire:model="article_category_id" :options="$categories" option-label="name" option-value="id" />
            
            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text">Content</span>
                </label>
                <textarea wire:model="article_content" class="textarea textarea-bordered h-64 font-mono text-sm" placeholder="Use Markdown or HTML..."></textarea>
                <label class="label">
                    <span class="label-text-alt text-gray-400">Supports basic HTML and Markdown</span>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-mary-input label="Order" wire:model="article_order" type="number" />
                <x-mary-toggle label="Published" wire:model="article_is_published" class="mt-4" />
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showArticleModal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="saveArticle" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- DELETE MODAL --}}
    <x-mary-modal wire:model="showDeleteModal" title="Confirm Delete">
        <div class="text-center p-4">
            Are you sure you want to delete this {{ $deleteType }}?
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
