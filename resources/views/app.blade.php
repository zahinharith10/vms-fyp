<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="/Logo.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Reverb Configurations for Dynamic WebSockets (Only active locally or if explicitly enabled on production) -->
        @if(config('app.env') === 'local' || env('REVERB_ENABLED', false))
            <meta name="reverb-key" content="{{ env('REVERB_APP_KEY') }}">
            <meta name="reverb-host" content="{{ env('REVERB_HOST') }}">
            <meta name="reverb-port" content="{{ env('REVERB_PORT') }}">
            <meta name="reverb-scheme" content="{{ env('REVERB_SCHEME', 'http') }}">
        @endif

        <!-- Google reCAPTCHA v3 -->
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>

        <!-- Scripts -->
        <script>
            // Define URL paths that should ALWAYS be light (login, register, reset, etc.)
            var authPaths = ['/login', '/register', '/forgot-password', '/reset-password', '/verify'];
            var currentPath = window.location.pathname;
            var isAuthPage = authPaths.some(function(p) { return currentPath.includes(p); });

            // Also treat the root welcome/landing page as always light
            var isWelcomePage = (currentPath === '/' || currentPath === '');

            if (!isAuthPage && !isWelcomePage) {
                // Only apply dark mode inside authenticated/dashboard pages
                if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } else {
                // Always force light on login/auth/welcome pages
                document.documentElement.classList.remove('dark');
            }
        </script>
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
