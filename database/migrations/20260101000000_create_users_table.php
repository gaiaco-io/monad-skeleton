<?php

declare(strict_types=1);

use Gaia\Clarity\Services\Schema;
use Gaia\Clarity\Services\Schema\Blueprint;

/**
 * Application-owned users table (not one of Clarity's two setup-owned tables —
 * sessions/caches, CrossRepoContracts.md §8 — this is the app's own concern). Column
 * shape matches what Middlewares\Authentication's findByCredential/findById resolvers
 * are expected to return: {id, passwordHash, locked, emailVerifiedAt}.
 */
return new class {
    public function up(): void
    {
        Schema::createTable('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('password_hash', 255);
            $table->string('full_name', 255, nullable: true);
            $table->string('role', 50, default: 'member');
            $table->boolean('locked', default: false);
            $table->datetime('email_verified_at', nullable: true);
            $table->datetime('created_at', default: Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at', default: Schema::raw('CURRENT_TIMESTAMP'), autoUpdate: true);
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropTable('users');
    }
};
