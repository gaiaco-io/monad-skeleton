<?php

/**
 * @var string $csrf_token
 * @var list<array{label: string, ok: bool, detail: string}> $checks
 */

use Monad\Clarity\Middlewares\MetaTag;

$layout = 'Layouts/main';

MetaTag::set(['title' => 'Welcome', 'description' => 'A Monad Framework application.']);
?>

<div class="py-12 sm:py-16">
    <h1 class="font-display text-3xl font-semibold sm:text-4xl">Welcome to Monad</h1>
    <p class="mt-3 max-w-2xl text-lg text-ink-muted">
        Necessitate only the necessary. Monad is a non-opinionated PHP framework: explicit
        control, a small closed API surface, and nothing running that you didn't call.
    </p>

    <!-- ========== LIVE STATUS ========== -->
    <div class="mt-10 rounded-sm border border-border bg-surface-raised p-4 sm:p-6">
        <h2 class="font-display text-base font-semibold">This install, right now</h2>
        <p class="mt-1 text-sm text-ink-muted">
            Not placeholder copy — every line below was checked while rendering this page
            (<code class="font-mono text-xs">App\Services\AppStatus</code>, the same checks
            <code class="font-mono text-xs">php mitosis health</code> runs).
        </p>

        <dl class="mt-4 divide-y divide-border border-t border-border font-mono text-sm">
            <?php foreach ($checks as $check): ?>
                <div class="flex items-center justify-between gap-4 py-2">
                    <dt class="flex items-center gap-2 text-ink">
                        <span
                            class="inline-block size-2 rounded-full <?= $check['ok'] ? 'bg-signal-ok' : 'bg-signal-error' ?>"
                            aria-hidden="true"
                        ></span>
                        <?= htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8') ?>
                    </dt>
                    <dd class="<?= $check['ok'] ? 'text-ink-muted' : 'text-signal-error' ?>">
                        <?= htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8') ?>
                    </dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>

    <!-- ========== WHAT TO TOUCH NEXT ========== -->
    <div class="mt-10">
        <h2 class="font-display text-base font-semibold">What to touch next</h2>
        <ul class="mt-4 space-y-3 text-sm">
            <li>
                <a class="text-ink underline" href="/users">See the example Users page &rarr;</a>
                <span class="text-ink-muted">— a real CRUD flow: <code class="font-mono text-xs">app/Controllers/UserController.php</code>, <code class="font-mono text-xs">app/Services/Registration.php</code>, and the CSRF-protected form behind it.</span>
            </li>
            <li>
                <code class="font-mono text-xs text-ink">app/routes/web.php</code>
                <span class="text-ink-muted">— register your own routes here.</span>
            </li>
            <li>
                <code class="font-mono text-xs text-ink">CLAUDE.md</code>
                <span class="text-ink-muted">— this project's own conventions; read it before adding a service or middleware.</span>
            </li>
            <li>
                <code class="font-mono text-xs text-ink">php mitosis health</code>
                <span class="text-ink-muted">— the same checks shown above, from the command line.</span>
            </li>
        </ul>
    </div>
</div>
