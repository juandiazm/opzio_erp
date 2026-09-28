<?php

namespace App\Services\AiDevelopment;

use Illuminate\Support\Str;

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
        $normalized = self::normalize($status);

        return in_array($normalized, ['pending', 'to do', 'todo', 'por hacer', 'tareas por hacer', 'open', 'abierto'], true);
    }

    public static function transitionAliases(string $status): array
    {
        return match ($status) {
            self::IN_PROGRESS => ['in progress', 'en curso', 'en progreso'],
            self::DEPLOYED => ['deployed', 'deploy', 'desplegado', 'desplegada'],
            self::QA => ['qa', 'quality', 'calidad', 'quality review'],
            self::DONE => ['done', 'finalizada', 'finalizado', 'completada', 'completado', 'closed', 'cerrada', 'cerrado'],
            default => [self::normalize($status)],
        };
    }

    public static function normalize(?string $value): string
    {
        $normalized = Str::ascii(strtolower(trim((string) $value)));

        return preg_replace('/[-_]+/', ' ', $normalized) ?: $normalized;
    }
}