<?php

declare(strict_types=1);

// Loaded by Monad\Clarity\Services\Console before every `php mitosis` dispatch — so
// anything registered here is available to every command, and anything that throws here
// breaks every command, not just the one being run. Keep it to registration.
//
// Two kinds of registration live in this file.
//
//
// 1. Custom commands — `php mitosis <name>`
//
// use Monad\Clarity\Services\Console;
//
// Console::register('reports:nightly', fn (array $arguments): int => MyReport::run());
//
// The handler is a callable, or a class-string instantiated with no constructor arguments
// and invoked as `(new $handler())($arguments)`.
//
//
// 2. Scheduled jobs — run by `php mitosis schedule:run` (Clarity 1.5.0+)
//
// The schedule lives here, in code, rather than in a crontab: jobs travel with a deploy,
// are visible to code review, and are identical on every node. The system cron gets one
// line for the life of the application, on every node that should be eligible to run jobs
// — three nodes give three chances a due job runs, and no chance it runs three times:
//
//     * * * * * cd /path/to/app && php mitosis schedule:run
//
// Written without `> /dev/null 2>&1` on purpose. A tick where nothing was due prints
// nothing at all, so silence is the healthy signal and any output is worth the operator's
// attention — the reflexive redirect would throw the failures away along with the quiet.
//
// Run `php mitosis schedule:install` once per database context before the first tick. The
// table it creates is opt-in, so an application that schedules nothing never gets it.
//
// use Monad\Clarity\Services\Scheduler;
// use Monad\Clarity\Services\Scheduler\JobLedger;
// use Monad\Clarity\Services\Session;
//
// Scheduler::job('sessions:prune', '15 3 * * *', fn () => Session::purgeExpired());
// Scheduler::job('invoices:chase', '*/10 * * * *', $billing->chaseOverdue(...));
// Scheduler::job('reports:build', '0 4 * * MON', $report(...), staleAfterMinutes: 240);
//
// Scheduler::job(
//     'scheduler:prune',
//     '@daily',
//     fn () => (new JobLedger())->prune(new DateTimeImmutable('-30 days'))
// );
//
// `staleAfterMinutes` is how long a run of that job may take before a later tick concludes
// its process died and reaps it. It is per job, not scheduler-wide: set it to suit a
// ten-second sweep and a four-hour report gets reaped while it is still working.
//
// Expressions are read on the application's own clock — the `TIMEZONE` value in `.env`,
// which `config/bootstrap.php` hands to `date_default_timezone_set()`. That is not
// necessarily the timezone the system cron itself runs in, and every node in a cluster
// must agree on it, or they are running two different schedules.
