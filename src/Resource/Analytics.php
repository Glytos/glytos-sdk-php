<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Analytics: rolled-up usage and spend for your organization.
 */
final class Analytics extends AbstractResource
{
    /**
     * Overview metrics for the last `$days` days (1-90, default 14).
     *
     * @return array<mixed>
     */
    public function overview(?int $days = null): array
    {
        return (array) $this->client->request(
            'GET',
            '/analytics/overview',
            null,
            ['days' => $days],
        );
    }
}
