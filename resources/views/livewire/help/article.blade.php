<?php

use Livewire\Volt\Component;
use App\Models\HelpArticle;
use Illuminate\Support\Facades\Session;
use App\Models\Setting;

new class extends Component {
    public HelpArticle $article;
    public $hasVoted = false;

    public function mount(HelpArticle $article)
    {
        $this->article = $article;
        
        if (!$this->article->is_published) {
            abort(404);
        }
        $this->article->increment('view_count');

        $this->hasVoted = Session::has('voted_article_' . $this->article->id);
    }

    public function vote(bool $helpful)
    {
        if ($this->hasVoted) {
            return;
        }

        if ($helpful) {
            $this->article->increment('helpful_count');
        } else {
            $this->article->increment('not_helpful_count');
        }

        Session::put('voted_article_' . $this->article->id, true);
        $this->hasVoted = true;
        
        // Refresh the model to get updated counts
        $this->article->refresh();
    }

    public function replaceAppName($text)
    {
        return str_replace('{{ app_name }}', Setting::get('site_name', config('app.name')), $text);
    }
}; ?>

<div class="max-w-4xl mx-auto p-4 md:p-8">
    <div class="mb-6">
        <a href="{{ route('help.index') }}" wire:navigate class="btn btn-ghost btn-sm gap-2 pl-0 hover:bg-transparent hover:text-primary">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" /> Back to Help Center
        </a>
    </div>

    <article class="bg-base-100 rounded-2xl shadow-sm border border-base-200 overflow-hidden">
        <div class="p-8 md:p-12 border-b border-base-100">
            <div class="flex items-center gap-2 text-sm text-primary font-bold uppercase tracking-wider mb-4">
                <span class="bg-primary/10 px-3 py-1 rounded-full">
                    {{ $this->article->category->name ?? 'General' }}
                </span>
            </div>
            <h1 class="text-3xl md:text-5xl font-black text-base-content mb-6 leading-tight">
                {{ $this->replaceAppName($this->article->title) }}
            </h1>
            <div class="flex items-center gap-4 text-sm text-gray-400">
                <div class="flex items-center gap-1">
                    <x-mary-icon name="o-calendar" class="w-4 h-4" />
                    Updated {{ $this->article->updated_at->format('M d, Y') }}
                </div>
                <div class="flex items-center gap-1">
                    <x-mary-icon name="o-eye" class="w-4 h-4" />
                    {{ $this->article->view_count }} views
                </div>
            </div>
        </div>

        <div class="p-8 md:p-12 prose prose-lg max-w-none prose-headings:font-bold prose-a:text-primary">
            {!! Str::markdown($this->replaceAppName($this->article->content)) !!}
        </div>

        <div class="bg-base-50 p-8 text-center border-t border-base-200">
            @if($hasVoted)
                <div class="text-center animate-fade-in">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-600 mb-3">
                        <x-mary-icon name="o-check" class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-1">Thank you for your feedback!</h3>
                    <p class="text-gray-500 text-sm">
                        {{ $article->helpful_count }} found this helpful • {{ $article->not_helpful_count }} did not
                    </p>
                </div>
            @else
                <p class="text-gray-600 font-medium mb-4">Was this article helpful?</p>
                <div class="flex justify-center gap-4">
                    <button wire:click="vote(true)" class="btn btn-outline btn-sm gap-2 hover:btn-success hover:text-white">
                        <x-mary-icon name="o-hand-thumb-up" class="w-4 h-4" /> Yes
                    </button>
                    <button wire:click="vote(false)" class="btn btn-outline btn-sm gap-2 hover:btn-error hover:text-white">
                        <x-mary-icon name="o-hand-thumb-down" class="w-4 h-4" /> No
                    </button>
                </div>
            @endif
        </div>
    </article>
</div>
