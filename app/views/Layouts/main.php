<?php

use Gaia\Clarity\Services\View;
use Gaia\Clarity\Services\Route;
use Gaia\Clarity\Services\SeoService;

$csrf_token = View::get('csrf_token') ?? '';
$csrf_field = View::get('csrf_field') ?? '_csrf';

/**
 * Main Entry Point for all Views
 * Loaded when no route matches the app
 */
?>

<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="csrf-field" content="<?= htmlspecialchars($csrf_field, ENT_QUOTES, 'UTF-8'); ?>">
    <?= SeoService::render(); ?>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/app.js"></script>
</head>

<body class="bg-slate-700 text-white">
    <div class="flex flex-col min-h-screen py-2">
        <div class="max-w-3xl flex flex-col mx-auto size-full">
            <!-- ========== HEADER ========== -->
            <header class="mb-auto flex flex-wrap sm:justify-start sm:flex-nowrap z-50 w-full text-sm py-4">
                <nav class="w-full px-4 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <a class="flex-none text-xl font-semibold text-red-500 focus:outline-hidden focus:opacity-80" href="#" aria-label="Brand">&lt;monad /&gt;</a>
                        <div class="sm:hidden">
                            <button type="button" class="hs-collapse-toggle relative size-9 flex justify-center items-center border border-white/10 font-medium text-sm text-gray-200 rounded-lg hover:bg-white/10 focus:outline-hidden focus:bg-white/10" id="hs-navbar-cover-page-collapse" aria-expanded="false" aria-controls="hs-navbar-cover-page" aria-label="Toggle navigation" data-hs-collapse="#hs-navbar-cover-page">
                                <svg class="hs-collapse-open:hidden shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="3" x2="21" y1="6" y2="6" />
                                    <line x1="3" x2="21" y1="12" y2="12" />
                                    <line x1="3" x2="21" y1="18" y2="18" />
                                </svg>
                                <svg class="hs-collapse-open:block hidden shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg>
                                <span class="sr-only">Toggle navigation</span>
                            </button>
                        </div>
                    </div>
                    <div id="hs-navbar-cover-page" class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow sm:block" aria-labelledby="hs-navbar-cover-page-collapse">
                        <div class="flex flex-col gap-5 mt-5 sm:flex-row sm:items-center sm:justify-center sm:mt-0 sm:ps-5">
                            <a class="font-medium hover:text-slate-300 focus:outline-hidden focus:text-sky-900" href="#">
                                Monad Home
                            </a>
                            <a class="font-medium hover:text-slate-300 focus:outline-hidden focus:text-sky-900" href="#">Documentation</a>
                        </div>
                    </div>
                </nav>
            </header>
            <!-- ========== END HEADER ========== -->

            <!-- ========== MAIN CONTENT ========== -->
            <main class="grow max-w-340 mx-auto px-4 w-full">
                <div class="text-center py-10 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-4xl my-6 font-bold">Welcome to Monad</h1>
                    <p class="text-xl font-normal leading-relaxed my-4">Necessitate only the necessary</p>
                    <p class="text-xl font-thin leading-relaxed my-4">Monad is a modern and lightweight framework for building applications in PHP. Deep scaffolding is shun away in Monad.</p>
                    <p class="my-2">To get started, replace the <span class="font-mono font-thin">&lt;main&gt;</span> tag in <span class="font-mono font-thin">public/index.php</span> with the following:</p>
                    <pre class="bg-slate-800 p-4 rounded-lg text-left">
                        &lt;?php View::render(); ?&gt;
                    </pre>
                </div>
            </main>
            <!-- ========== END MAIN CONTENT ========== -->

            <!-- ========== FOOTER ========== -->
            <footer class="mt-auto text-center py-5">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <p class="text-sm text-white/70">&copy; <?= date('Y'); ?> Monad. All rights reserved.</p>
                </div>
            </footer>
            <!-- ========== END FOOTER ========== -->
        </div>
    </div>
</body>

</html>