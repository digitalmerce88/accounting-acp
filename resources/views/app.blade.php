<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Expose initial Inertia page JSON to window.__INERTIA__ early so client code can read shared props reliably --}}
        @if(isset($page))
                <script>window.__INERTIA__ = {!! json_encode($page) !!};</script>
                <?php
                // Debug: persist the exact page JSON that Blade is injecting so we can inspect
                // server-side what Inertia is sending to the client. REMOVE in production.
                try {
                    $logPath = storage_path('logs/inertia_page_debug.log');
                    file_put_contents($logPath, json_encode($page) . PHP_EOL, FILE_APPEND | LOCK_EX);
                } catch (\Throwable $e) {
                    // ignore write errors to avoid breaking the response
                }
                ?>
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
