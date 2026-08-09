<?php

/**
 * @var string $csrf_token
 * @var list<string> $errors
 */

use Monad\Clarity\Middlewares\Csrf;
use Monad\Clarity\Middlewares\MetaTag;

$layout = 'Layouts/main';
$errors ??= [];

MetaTag::set(['title' => 'New user']);
?>

<div class="mx-auto max-w-md py-10">
    <h1 class="font-display text-2xl font-semibold mb-6">New user</h1>

    <?php if ($errors !== []): ?>
        <ul class="mb-4 space-y-1 text-sm text-signal-error">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/users" class="flex flex-col gap-4">
        <input type="hidden" name="<?= htmlspecialchars(Csrf::FIELD_NAME, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

        <label class="flex flex-col gap-1">
            <span class="text-sm text-ink-muted">Full name</span>
            <input type="text" name="full_name" class="rounded-sm border border-border bg-surface-raised px-3 py-2 text-ink">
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-sm text-ink-muted">Email</span>
            <input type="email" name="email" required class="rounded-sm border border-border bg-surface-raised px-3 py-2 text-ink">
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-sm text-ink-muted">Password</span>
            <input type="password" name="password" required class="rounded-sm border border-border bg-surface-raised px-3 py-2 text-ink">
            <span class="text-xs text-ink-muted">At least 10 characters. See <code class="font-mono">App\Services\PasswordPolicy</code>.</span>
        </label>

        <button type="submit" class="inline-flex items-center justify-center gap-x-2 rounded-sm border border-transparent bg-ink px-3 py-2 text-sm font-medium text-surface hover:opacity-90">
            Create
        </button>
    </form>
</div>
