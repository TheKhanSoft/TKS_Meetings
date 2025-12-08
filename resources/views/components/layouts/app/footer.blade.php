<footer class="mt-10 border-t border-base-content/10 pt-6 text-center text-sm text-base-content/50 pb-6">
    {{ \App\Models\Setting::get('footer_text') ?? 'Copyright © ' . date('Y') . ' ' . \App\Models\Setting::get('site_name', config('app.name')) }}
</footer>