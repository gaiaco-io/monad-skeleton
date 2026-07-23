<?php

/**
 * @var string $csrf_token
 * @var ?string $error
 */

use Gaia\Clarity\Middlewares\Csrf;
use Gaia\Clarity\Middlewares\MetaTag;

$layout = 'Layouts/main';
$error ??= null;

MetaTag::set(['title' => 'New user']);
?>

<div class="py-10 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <h1 class="text-2xl font-bold mb-6">New user</h1>

    <?php if ($error !== null): ?>
        <p class="mb-4 text-red-400"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="/users" class="flex flex-col gap-4">
        <input type="hidden" name="<?= htmlspecialchars(Csrf::FIELD_NAME, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

        <label class="flex flex-col gap-1">
            <span class="text-sm">Full name</span>
            <input type="text" name="full_name" class="rounded-lg bg-slate-800 border border-white/10 px-3 py-2">
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-sm">Email</span>
            <input type="email" name="email" required class="rounded-lg bg-slate-800 border border-white/10 px-3 py-2">
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-sm">Password</span>
            <input type="password" name="password" required class="rounded-lg bg-slate-800 border border-white/10 px-3 py-2">
        </label>

        <button type="submit" class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
            Create
        </button>
    </form>
</div>
