<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\LoggerInterface;
use App\Core\Request;
use App\Domain\EventAction;
use App\Domain\EventTarget;
use App\Repository\EventRepositoryInterface;

/** Single entry point for recording user activity. */
final class ActivityLogger
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly Auth $auth,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param ?int $userId Defaults to the currently signed-in user; pass explicitly when the
     *                     session doesn't know the user yet (registration) or is about to end (logout).
     */
    public function log(Request $request, EventAction $action, ?EventTarget $target = null, ?int $userId = null): void
    {
        $userId ??= $this->auth->user()?->id;

        try {
            $this->events->insert($userId, $action, $target, $request->ip(), $request->userAgent());
        } catch (\Throwable $e) {
            // Analytics must never break the user-facing flow.
            $this->logger->error('Failed to record event', ['message' => $e->getMessage()]);
        }
    }
}
