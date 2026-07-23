<?php

/**
 * @var list<array{id: string, email: string, full_name: ?string, role: string}> $users
 */

use Gaia\Clarity\Middlewares\MetaTag;

$layout = 'Layouts/main';

MetaTag::set(['title' => 'Users']);
?>

<div class="py-10 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Users</h1>
        <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700" href="/users/create">
            New user
        </a>
    </div>

    <?php if ($users === []): ?>
        <p class="text-white/70">No users yet.</p>
    <?php else: ?>
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="py-2 pr-4">Email</th>
                    <th class="py-2 pr-4">Name</th>
                    <th class="py-2">Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="border-b border-white/5">
                        <td class="py-2 pr-4"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2 pr-4"><?= htmlspecialchars($user['full_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
