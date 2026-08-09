<?php

/**
 * @var list<array{id: string, email: string, full_name: ?string, role: string}> $users
 */

use Monad\Clarity\Middlewares\MetaTag;

$layout = 'Layouts/main';

MetaTag::set(['title' => 'Users']);
?>

<div class="py-10">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold">Users</h1>
        <a class="inline-flex items-center gap-x-2 rounded-sm border border-transparent bg-ink px-3 py-2 text-sm font-medium text-surface hover:opacity-90" href="/users/create">
            New user
        </a>
    </div>

    <?php if ($users === []): ?>
        <p class="text-ink-muted">No users yet.</p>
    <?php else: ?>
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-border">
                    <th class="py-2 pr-4 font-medium text-ink-muted">Email</th>
                    <th class="py-2 pr-4 font-medium text-ink-muted">Name</th>
                    <th class="py-2 font-medium text-ink-muted">Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="border-b border-border">
                        <td class="py-2 pr-4"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2 pr-4"><?= htmlspecialchars($user['full_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
