<?php

use Livewire\Volt\Component;
use App\Models\HelpCategory;
use App\Models\HelpArticle;
use Illuminate\Support\Str;
use App\Models\Setting;

new class extends Component {
    public $search = '';
    public $selectedCategory = null;

    public function with()
    {
        $categories = HelpCategory::where('is_active', true)
            ->orderBy('order')
            ->get();

        $articlesQuery = HelpArticle::where('is_published', true)
            ->with('category');

        if ($this->selectedCategory) {
            $articlesQuery->where('help_category_id', $this->selectedCategory);
        }

        if ($this->search) {
            $articlesQuery->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        $articles = $articlesQuery->orderBy('order')->get();

        // Group articles by category if no specific category is selected and no search is active
        $groupedArticles = null;
        if (!$this->selectedCategory && !$this->search) {
            $groupedArticles = $articles->groupBy('help_category_id');
        }

        $totalArticles = HelpArticle::where('is_published', true)->count();

        return [
            'categories' => $categories,
            'articles' => $articles,
            'groupedArticles' => $groupedArticles,
            'totalArticles' => $totalArticles,
        ];
    }

    public function selectCategory($id)
    {
        $this->selectedCategory = $id === $this->selectedCategory ? null : $id;
    }

    public function replaceAppName($text)
    {
        return str_replace('{{ app_name }}', Setting::get('site_name', config('app.name')), $text);
    }
}; ?>

<div class="p-4 md:p-8 max-w-7xl mx-auto">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-black mb-4">How can we help you?</h1>
        <div class="max-w-xl mx-auto relative">
            <x-mary-input 
                icon="o-magnifying-glass" 
                placeholder="Search over {{ $totalArticles }} articles..." 
                wire:model.live.debounce="search" 
                class="shadow-lg border-0 bg-base-100 h-12 pl-12"
            />
        </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-8">
        
        {{-- Sidebar Categories --}}
        <div class="lg:col-span-1 space-y-2">
            <div class="font-bold text-sm uppercase text-gray-400 mb-2 px-2">Categories</div>
            <button 
                wire:click="selectCategory(null)" 
                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ is_null($selectedCategory) ? 'bg-primary text-primary-content font-bold' : 'hover:bg-base-200' }}"
            >
                All Articles
            </button>
            @foreach($categories as $category)
                <button 
                    wire:click="selectCategory({{ $category->id }})" 
                    class="w-full text-left px-4 py-2 rounded-lg transition-colors flex justify-between items-center {{ $selectedCategory === $category->id ? 'bg-primary text-primary-content font-bold' : 'hover:bg-base-200' }}"
                >
                    <span>{{ $category->name }}</span>
                    @if($selectedCategory === $category->id)
                        <x-mary-icon name="o-chevron-right" class="w-4 h-4" />
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Articles Grid --}}
        <div class="lg:col-span-3">
            @if($articles->count() > 0)
                @if($groupedArticles)
                    <div class="space-y-10">
                        @foreach($categories as $category)
                            @if(isset($groupedArticles[$category->id]))
                                <div>
                                    <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                                        <span class="bg-primary/10 text-primary w-8 h-8 rounded-lg flex items-center justify-center">
                                            <x-mary-icon name="o-folder" class="w-4 h-4" />
                                        </span>
                                        {{ $category->name }}
                                    </h2>
                                    <div class="grid md:grid-cols-2 gap-4">
                                        @foreach($groupedArticles[$category->id] as $article)
                                            <a href="{{ route('help.article', $article->slug) }}" wire:navigate class="block group">
                                                <div class="bg-base-100 p-4 rounded-xl border border-base-200 shadow-sm hover:shadow-md hover:border-primary/30 transition-all h-full flex flex-col">
                                                    <h3 class="font-bold text-base mb-1 group-hover:text-primary transition-colors">{{ $this->replaceAppName($article->title) }}</h3>
                                                    <p class="text-xs text-gray-500 line-clamp-2 flex-1">
                                                        {{ Str::limit(strip_tags($this->replaceAppName($article->content)), 80) }}
                                                    </p>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach($articles as $article)
                            <a href="{{ route('help.article', $article->slug) }}" wire:navigate class="block group">
                                <div class="bg-base-100 p-6 rounded-xl border border-base-200 shadow-sm hover:shadow-md hover:border-primary/30 transition-all h-full flex flex-col">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="bg-primary/10 text-primary p-2 rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                                            <x-mary-icon name="o-document-text" class="w-5 h-5" />
                                        </div>
                                        <x-mary-icon name="o-arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-primary transition-colors" />
                                    </div>
                                    <h3 class="font-bold text-lg mb-2 group-hover:text-primary transition-colors">{{ $this->replaceAppName($article->title) }}</h3>
                                    <p class="text-sm text-gray-500 line-clamp-2 flex-1">
                                        {{ Str::limit(strip_tags($this->replaceAppName($article->content)), 100) }}
                                    </p>
                                    <div class="mt-4 pt-4 border-t border-base-100 text-xs text-gray-400 flex justify-between items-center">
                                        <span>{{ $article->category->name ?? 'General' }}</span>
                                        <span>Read more &rarr;</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="text-center py-16 bg-base-100 rounded-xl border border-dashed border-base-300">
                    <x-mary-icon name="o-face-frown" class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-bold text-gray-600">No articles found</h3>
                    <p class="text-gray-400">Try adjusting your search or category filter.</p>
                </div>
            @endif
        </div>
    </div>
</div>
