<?php

declare(strict_types=1);

namespace App\Middlewares;

use Monad\Clarity\Middlewares\Logger as BaseLogger;

/**
 * Thin extension of Clarity's PSR-3 Logger (CrossRepoContracts.md §5) for the app-level
 * error channel (storage/logs/error/app.log — ReleaseNotes §14.2.11.1). This is the
 * instance handed to Mediator::configure() in config/bootstrap.php.
 *
 * Logger isn't a request-pipeline middleware — it's filed under Middlewares because
 * that's where Clarity ships it — so this class is constructed directly wherever
 * logging is needed, not registered via Route. The db (storage/logs/error/db.log,
 * §14.2.11.2) and event/timeline (storage/logs/event/timeline.log, §14.2.11.3) channels
 * are separate concerns: construct `new \Monad\Clarity\Middlewares\Logger('db',
 * PATH['error_log'] . 'db.log')` / `new \Monad\Clarity\Middlewares\Logger('event',
 * PATH['event_log'] . 'timeline.log')` directly wherever that logging happens, rather
 * than overloading this one class with three unrelated channels.
 *
 * @package App\Middlewares
 */
final class Logger extends BaseLogger
{
    public function __construct()
    {
        parent::__construct(channel: 'app', path: PATH['error_log'] . 'app.log');
    }
}
