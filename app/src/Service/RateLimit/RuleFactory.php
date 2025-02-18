<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

class RuleFactory
{
    public function getRule(string $userId = '', string $clientIp = ''): RuleInterface
    {
        if ($userId === '') {
            return (new GuestRule())->with($clientIp);
        }

        return (new UserRule())->with($userId, $clientIp);
    }
}
