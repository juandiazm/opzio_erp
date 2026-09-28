<?php

namespace App\Services\AiDevelopment;

use App\Models\ai_development_execution;
use App\Models\github_connection;
use RuntimeException;
use Symfony\Component\Process\Process;

class local_git_service
{
    public function prepare(ai_development_execution $execution, github_connection $connection, string $owner, string $repository): array
    {
        $this->validateRepositoryPart($owner, 'owner');
        $this->validateRepositoryPart($repository, 'repository');
        $baseBranch = trim((string) $execution->base_branch);
        $featureBranch = trim((string) $execution->feature_branch);
        if ($baseBranch === '' || $featureBranch === '') {
            throw new RuntimeException('La ejecucion no tiene ramas validas configuradas.');
        }

        $root = rtrim((string) config('ai_development.workspace_root'), DIRECTORY_SEPARATOR);
        $workspace = $root.DIRECTORY_SEPARATOR.$execution->id;
        if (! is_dir($workspace) && ! mkdir($workspace, 0775, true) && ! is_dir($workspace)) {
            throw new RuntimeException('No fue posible crear el workspace aislado de la ejecucion.');
        }
        $gitDirectory = $workspace.DIRECTORY_SEPARATOR.'.git';
        $remote = 'https://github.com/'.$owner.'/'.$repository.'.git';
        if (! is_dir($gitDirectory)) {
            $this->run(['git', 'clone', '--no-single-branch', $remote, $workspace], dirname($workspace), $connection);
        } else {
            $this->run(['git', 'fetch', '--prune', 'origin'], $workspace, $connection);
        }

        $localBranch = trim($this->run(['git', 'branch', '--list', $featureBranch], $workspace, $connection));
        $remoteBranch = trim($this->run(['git', 'branch', '--remotes', '--list', 'origin/'.$featureBranch], $workspace, $connection));
        if ($localBranch !== '') {
            $this->run(['git', 'checkout', $featureBranch], $workspace, $connection);
        } elseif ($remoteBranch !== '') {
            $this->run(['git', 'checkout', '-B', $featureBranch, 'origin/'.$featureBranch], $workspace, $connection);
        } else {
            $this->run(['git', 'checkout', '-B', $featureBranch, 'origin/'.$baseBranch], $workspace, $connection);
        }
        $execution->update(['workspace_path' => $workspace]);

        return ['workspace' => $workspace, 'remote' => $remote, 'branch' => $featureBranch];
    }

    public function updateFromBase(ai_development_execution $execution, github_connection $connection): void
    {
        $workspace = $this->workspace($execution);
        $this->run(['git', 'fetch', '--prune', 'origin'], $workspace, $connection);
        $this->run(['git', 'merge', '--no-edit', 'origin/'.$execution->base_branch], $workspace, $connection);
    }

    public function commitAndPush(ai_development_execution $execution, github_connection $connection, string $message): array
    {
        $workspace = $this->workspace($execution);
        $this->run(['git', 'diff', '--check'], $workspace, $connection);
        $changedFiles = trim($this->run(['git', 'diff', '--name-only'], $workspace, $connection));
        $stagedFiles = trim($this->run(['git', 'ls-files', '--others', '--modified', '--exclude-standard'], $workspace, $connection));
        $files = collect(preg_split('/\R/', $changedFiles."\n".$stagedFiles) ?: [])->filter()->unique()->values();
        $protected = $files->filter(fn (string $file): bool => in_array(strtolower(basename($file)), ['qa.yml', 'main.yml'], true));
        if ($protected->isNotEmpty()) {
            throw new RuntimeException('El agente intento modificar un workflow protegido: '.$protected->implode(', '));
        }
        if ($files->isEmpty()) {
            throw new RuntimeException('El agente no dejo cambios para integrar.');
        }

        $this->run(['git', 'add', '--all'], $workspace, $connection);
        $this->run(['git', '-c', 'user.name=Opzio AI', '-c', 'user.email=ai@opzio.co', 'commit', '-m', mb_substr($message, 0, 180)], $workspace, $connection);
        $this->run(['git', 'push', '--set-upstream', 'origin', $execution->feature_branch], $workspace, $connection);

        return [
            'files' => $files->all(),
            'commit' => $this->headSha($execution, $connection),
        ];
    }

    public function headSha(ai_development_execution $execution, github_connection $connection): string
    {
        return trim($this->run(['git', 'rev-parse', 'HEAD'], $this->workspace($execution), $connection));
    }

    public function deleteWorkspace(ai_development_execution $execution): void
    {
        $workspace = $execution->workspace_path;
        if (! is_string($workspace) || $workspace === '' || ! is_dir($workspace)) {
            return;
        }
        $root = realpath((string) config('ai_development.workspace_root'));
        $realWorkspace = realpath($workspace);
        if ($root === false || $realWorkspace === false || ! str_starts_with($realWorkspace, $root.DIRECTORY_SEPARATOR)) {
            return;
        }
        $this->removeDirectory($realWorkspace);
    }

    private function workspace(ai_development_execution $execution): string
    {
        $workspace = trim((string) $execution->workspace_path);
        if ($workspace === '' || ! is_dir($workspace)) {
            throw new RuntimeException('El workspace de la ejecucion no esta disponible.');
        }

        return $workspace;
    }

    private function run(array $arguments, string $cwd, github_connection $connection): string
    {
        $token = trim((string) $connection->credential('token'));
        $askPass = $this->askPassFile($cwd);
        $process = new Process($arguments, $cwd, [
            'GIT_ASKPASS' => $askPass,
            'GIT_TERMINAL_PROMPT' => '0',
            'OPZIO_GIT_TOKEN' => $token,
        ], null, 600);
        $process->run();
        @unlink($askPass);
        if (! $process->isSuccessful()) {
            $output = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException('Git: '.mb_substr(preg_replace('/https?:\/\/[^\s]+/i', '[remote]', $output) ?? $output, 0, 1500));
        }

        return $process->getOutput();
    }

    private function askPassFile(string $cwd): string
    {
        $path = $cwd.DIRECTORY_SEPARATOR.'.opzio-git-askpass.cmd';
        file_put_contents($path, "@echo off\r\necho %* | findstr /I \"Username\" >nul\r\nif not errorlevel 1 (echo x-access-token) else (echo %OPZIO_GIT_TOKEN%)\r\n");

        return $path;
    }

    private function validateRepositoryPart(string $value, string $label): void
    {
        if (! preg_match('/^[A-Za-z0-9_.-]+$/', $value)) {
            throw new RuntimeException('El '.$label.' GitHub no tiene un formato permitido.');
        }
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }
            $path = $directory.DIRECTORY_SEPARATOR.$item;
            is_dir($path) && ! is_link($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($directory);
    }
}