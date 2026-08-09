<?php

/**
 * @var string $content Rendered by View::render() — the child view's output.
 * @var string $csrf_token Shared once in public/index.php for every request.
 */

use Monad\Clarity\Middlewares\Csrf;
use Monad\Clarity\Middlewares\MetaTag;

$csrf_token ??= '';
$docsUrl = getenv('DOCS_URL') ?: 'https://github.com/gaiaco-io/monad-skeleton';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="csrf-field" content="<?= htmlspecialchars(Csrf::FIELD_NAME, ENT_QUOTES, 'UTF-8'); ?>">
    <?= MetaTag::render(); ?>
    <link rel="preload" href="/assets/fonts/ibm-plex-sans-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/fraunces-latin-600-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>

<body class="flex min-h-screen flex-col bg-surface font-sans text-ink">
    <!-- ========== HEADER ========== -->
    <header class="w-full border-b border-border">
        <nav aria-label="Primary" class="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-6">
            <a class="font-display text-lg font-semibold text-ink" href="/">monad</a>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                <a class="text-ink hover:underline" href="/users">Users example</a>
                <a class="text-ink hover:underline" href="<?= htmlspecialchars($docsUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Documentation</a>
                <a class="flex items-center text-ink hover:underline" href="https://github.com/gaiaco-io/monad-skeleton" target="_blank" rel="noopener noreferrer" aria-label="monad/skeleton on GitHub">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.1.79-.25.79-.56 0-.28-.01-1.02-.02-2-3.2.7-3.88-1.54-3.88-1.54-.52-1.33-1.28-1.69-1.28-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.03 1.75 2.68 1.25 3.33.96.1-.75.4-1.25.73-1.54-2.56-.29-5.25-1.28-5.25-5.7 0-1.26.45-2.29 1.18-3.09-.12-.29-.51-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.79 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.24 2.76.12 3.05.74.8 1.18 1.83 1.18 3.09 0 4.43-2.7 5.4-5.27 5.69.42.36.78 1.07.78 2.15 0 1.56-.01 2.81-.01 3.19 0 .31.2.67.8.56A10.51 10.51 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5Z" />
                    </svg>
                </a>
            </div>
        </nav>
    </header>
    <!-- ========== END HEADER ========== -->

    <!-- ========== MAIN CONTENT ========== -->
    <main class="mx-auto w-full max-w-5xl grow px-4 sm:px-6">
        <?= $content ?? '' ?>
    </main>
    <!-- ========== END MAIN CONTENT ========== -->

    <!-- ========== FOOTER ========== -->
    <footer class="mt-auto w-full border-t border-border">
        <div class="mx-auto max-w-5xl px-4 py-6 text-center text-sm text-ink-muted sm:px-6">
            &copy; <?= date('Y'); ?> Monad. All rights reserved.
        </div>
    </footer>
    <!-- ========== END FOOTER ========== -->
</body>

</html>
