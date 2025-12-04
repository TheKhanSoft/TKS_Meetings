<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="{{ \App\Models\Setting::get('site_description', 'Academics Management System') }}" />

<title>{{ $title ?? \App\Models\Setting::get('site_name', config('app.name')) }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
	:root {
		--primary-color: {{ \App\Models\Setting::get('primary_color', '#4f46e5') }};
	}
</style>

<script>
	(() => {
		const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
        // Use server-side default theme if no local preference
		const defaultTheme = '{{ \App\Models\Setting::get('default_theme', 'system') }}';
		const preference = localStorage.getItem('mary-theme-preference')?.replaceAll('"', '') ?? defaultTheme

		const resolveTheme = (pref) => pref === 'system'
			? (mediaQuery.matches ? 'dark' : 'light')
			: pref

		const applyTheme = (pref) => {
			const theme = resolveTheme(pref)
			const themeClass = theme

			localStorage.setItem('mary-theme', JSON.stringify(theme))
			localStorage.setItem('mary-class', JSON.stringify(themeClass))

			document.documentElement.setAttribute('data-theme', theme)
			document.documentElement.setAttribute('class', themeClass)
		}

		applyTheme(preference)

		mediaQuery.addEventListener('change', () => {
			if ((localStorage.getItem('mary-theme-preference') ?? defaultTheme).replaceAll('"', '') === 'system') {
				applyTheme('system')
			}
		})
	})()
</script>
