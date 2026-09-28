<?php

namespace App\Services\AiDevelopment;

final class jira_automation_statuses
{
    public const PENDING = 'Pending';
    public const IN_PROGRESS = 'In Progress';
    public const DEPLOYED = 'Deployed';
    public const QA = 'QA';
    public const DONE = 'Done';

    public static function all(): array
    {
        return [self::PENDING, self::IN_PROGRESS, self::DEPLOYED, self::QA, self::DONE];
    }
}