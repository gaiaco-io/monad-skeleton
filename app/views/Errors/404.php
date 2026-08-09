<?php

use Monad\Clarity\Middlewares\MetaTag;

$layout = 'Layouts/main';

MetaTag::set(['title' => 'Not found']);
?>

<div class="py-20 text-center">
    <h1 class="font-display text-7xl font-semibold sm:text-9xl">404</h1>
    <p class="mt-3 text-ink-muted">Oops, something went wrong.</p>
    <p class="text-ink-muted">Sorry, we couldn't find your page.</p>
    <div class="mt-5 flex flex-col items-center justify-center gap-2 sm:flex-row sm:gap-3">
        <a class="inline-flex w-full items-center justify-center gap-x-2 rounded-sm border border-transparent bg-ink px-4 py-3 text-sm font-medium text-surface hover:opacity-90 sm:w-auto" href="/">
            <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
            Back to Home
        </a>
    </div>
</div>
