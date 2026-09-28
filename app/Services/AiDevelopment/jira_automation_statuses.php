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

    public static function isPending(?string $status): bool
    {
        $normalized = strtolower(trim((string) $status));
        $normalized = str_replace(['-', '_'], ' ', $normalized);

        return in_array($normalized, ['pending', 'to do', 'todo', 'por hacer', 'tareas por hacer', 'open', 'abierto'], true);
    }
}