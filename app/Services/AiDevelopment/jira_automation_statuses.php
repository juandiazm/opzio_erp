<?php

namespace App\Services\AiDevelopment;

use Illuminate\Support\Str;

final class jira_automation_statuses
{
    public const PENDING = 'Pending';
    public const TO_DO = 'To Do';
    public const IN_PROGRESS = 'In Progress';
    public const DEPLOYED = 'Deployed';
    public const DEPLOY = 'Deploy';
    public const QA = 'QA';
    public const QUALITY = 'Quality';
    public const DONE = 'Done';

    public static function all(): array
    {
        return [self::TO_DO, self::IN_PROGRESS, self::DEPLOY, self::QUALITY, self::DONE];
    }

    public static function isPending(?string $status): bool
    {
        $normalized = self::normalize($status);

        return in_array($normalized, ['pending', 'to do', 'todo', 'por hacer', 'tareas por hacer', 'open', 'abierto'], true);
    }

    public static function isDevelopmentActive(?string $status): bool
    {
        return in_array(self::normalize($status), ['in progress', 'en curso', 'en progreso'], true);
    }

    public static function isQualityReview(?string $status): bool
    {
        return in_array(self::normalize($status), self::transitionAliases(self::QA), true);
    }

    public static function isDone(?string $status, ?string $statusCategory = null): bool
    {
        $aliases = self::transitionAliases(self::DONE);

        return in_array(self::normalize($status), $aliases, true)
            || in_array(self::normalize($statusCategory), $aliases, true);
    }

    public static function transitionAliases(string $status): array
    {
        return match ($status) {
            self::TO_DO => ['to do', 'todo', 'pending', 'por hacer', 'tareas por hacer', 'open', 'abierto'],
            self::IN_PROGRESS => ['in progress', 'en curso', 'en progreso'],
            self::DEPLOY, self::DEPLOYED => ['deploy', 'deployed', 'desplegado', 'desplegada'],
            self::QUALITY, self::QA => ['quality', 'qa', 'calidad', 'quality review'],
            self::DONE => [
                'done', 'finalizada', 'finalizado', 'completada', 'completado', 'closed', 'cerrada', 'cerrado',
                'listo', 'lista', 'listo para ejecutar', 'lista para ejecutar', 'ready', 'ready to execute',
                'ready for execution', 'completed', 'complete', 'finished', 'resolved', 'resuelto', 'resuelta',
            ],
            default => [self::normalize($status)],
        };
    }

    public static function normalize(?string $value): string
    {
        $normalized = Str::ascii(strtolower(trim((string) $value)));

        return preg_replace('/[-_]+/', ' ', $normalized) ?: $normalized;
    }
}