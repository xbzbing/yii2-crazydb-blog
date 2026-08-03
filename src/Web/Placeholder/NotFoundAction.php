<?php

declare(strict_types=1);

namespace App\Web\Placeholder;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\NotFoundException;

/**
 * Placeholder handler for routes whose controllers are ported in later phases.
 * Returns 404 so the URL structure stays locked without exposing a 500.
 */
final class NotFoundAction
{
    public function __invoke(): ResponseInterface
    {
        throw new NotFoundException();
    }
}
