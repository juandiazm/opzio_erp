<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PROJECT = 'opzio_erp';
    private const TABLE = 'users';

    public function up(): void
    {
        $this->assertAccountTable();
        $reportPath = $this->credentialReportPath();
        $report = fopen($reportPath, 'c+');
        if ($report === false) {
            throw new RuntimeException('Could not open the external credential report.');
        }
        if (DIRECTORY_SEPARATOR !== '\\' && !chmod($reportPath, 0600)) {
            fclose($report);
            throw new RuntimeException('Could not restrict access to the external credential report.');
        }
        if (!flock($report, LOCK_EX)) {
            fclose($report);
            throw new RuntimeException('Could not lock the external credential report.');
        }
        if (fseek($report, 0, SEEK_END) !== 0 || ($reportOffset = ftell($report)) === false) {
            flock($report, LOCK_UN);
            fclose($report);
            throw new RuntimeException('Could not seek the external credential report.');
        }

        try {
            DB::transaction(function () use ($report, $reportOffset): void {
                $rows = $this->candidateUsers()->orderBy('id')->lockForUpdate()->get(['id', 'email']);
                $changes = [];
                $seenEmails = [];
                foreach ($rows as $row) {
                    $oldEmail = (string) $row->email;
                    $newEmail = preg_replace('/@ridder\.com\.co$/i', '@opzio.co', $oldEmail);
                    if (!is_string($newEmail)) {
                        throw new RuntimeException('Could not derive the replacement email address.');
                    }
                    $normalizedEmail = strtolower($newEmail);
                    if (isset($seenEmails[$normalizedEmail]) ||
                        DB::table(self::TABLE)->whereRaw('LOWER(email) = ?', [$normalizedEmail])->where('id', '<>', $row->id)->exists()) {
                        throw new RuntimeException('A replacement email already exists; no accounts were changed.');
                    }
                    $seenEmails[$normalizedEmail] = true;
                    $changes[] = [
                        'id' => $row->id,
                        'old_email' => $oldEmail,
                        'new_email' => $newEmail,
                        'password' => Str::random(32),
                    ];
                }
                $this->appendCredentialReport($report, $reportOffset === 0, $changes);
                foreach ($changes as $change) {
                    $values = ['email' => $change['new_email'], 'password' => Hash::make($change['password'])];
                    foreach (['otp', 'otp_expdate', 'reset_password', 'reset_password_date', 'temp_password', 'temp_password_date'] as $column) {
                        if (Schema::hasColumn(self::TABLE, $column)) {
                            $values[$column] = null;
                        }
                    }
                    if (Schema::hasColumn(self::TABLE, 'remember_token')) {
                        $values['remember_token'] = Str::random(60);
                    }
                    if (Schema::hasColumn(self::TABLE, 'must_change_password')) {
                        $values['must_change_password'] = true;
                    }
                    if (Schema::hasColumn(self::TABLE, 'temporary_password_expires_at')) {
                        $values['temporary_password_expires_at'] = now()->addDay();
                    }
                    if (Schema::hasColumn(self::TABLE, 'updated_at')) {
                        $values['updated_at'] = now();
                    }
                    if (DB::table(self::TABLE)->where('id', $change['id'])->update($values) !== 1) {
                        throw new RuntimeException('An administrator row changed during the migration.');
                    }
                }
                $this->clearPasswordResetTokens($changes);
            });
        } catch (\Throwable $exception) {
            if (!ftruncate($report, $reportOffset) || !fflush($report)) {
                throw new RuntimeException('The migration failed and its pending credential report could not be removed. Inspect the external report before retrying.', 0, $exception);
            }
            throw $exception;
        } finally {
            flock($report, LOCK_UN);
            fclose($report);
        }
    }

    protected function candidateUsers()
    {
        return DB::table(self::TABLE)->whereRaw('LOWER(email) LIKE ?', ['%@ridder.com.co']);
    }

    private function assertAccountTable(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new RuntimeException('The administrator table does not exist.');
        }
        foreach (['id', 'email', 'password'] as $column) {
            if (!Schema::hasColumn(self::TABLE, $column)) {
                throw new RuntimeException('The administrator table is missing a required column.');
            }
        }
    }

    private function credentialReportPath(): string
    {
        $path = getenv('ADMIN_CREDENTIAL_REPORT_PATH');
        if (!is_string($path) || trim($path) === '' || strpos($path, "\0") !== false) {
            throw new RuntimeException('Set ADMIN_CREDENTIAL_REPORT_PATH to an absolute path outside all Git worktrees.');
        }
        $path = trim($path);
        $isWindowsDrivePath = strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/');
        $isUncPath = substr($path, 0, 2) === '\\\\';
        $isAbsolutePath = DIRECTORY_SEPARATOR === '\\' ? ($isWindowsDrivePath || $isUncPath) : str_starts_with($path, '/');
        if (!$isAbsolutePath) {
            throw new RuntimeException('ADMIN_CREDENTIAL_REPORT_PATH must be absolute.');
        }
        $directory = realpath(dirname($path));
        if ($directory === false || !is_writable($directory)) {
            throw new RuntimeException('The external credential report directory must already exist and be writable.');
        }
        $path = $directory . DIRECTORY_SEPARATOR . basename($path);
        if (is_dir($path) || is_link($path) || (file_exists($path) && !is_file($path))) {
            throw new RuntimeException('The credential report path must be a regular file, not a directory or link.');
        }
        $cursor = $directory;
        while (true) {
            if (file_exists($cursor . DIRECTORY_SEPARATOR . '.git')) {
                throw new RuntimeException('The credential report must be outside every Git worktree.');
            }
            $parent = dirname($cursor);
            if ($parent === $cursor) {
                break;
            }
            $cursor = $parent;
        }
        return $path;
    }

    private function appendCredentialReport($report, bool $isNewFile, array $changes): void
    {
        $contents = $isNewFile ? "# Informe confidencial de rotacion de credenciales\n\nNo versionar ni compartir por canales publicos.\n\n" : "\n";
        $contents .= '## ' . self::PROJECT . ' - ' . now()->toDateTimeString() . "\n\n";
        if ($changes === []) {
            $contents .= "No se encontraron administradores con el dominio @ridder.com.co.\n";
        } else {
            $contents .= "| ID | Usuario (correo anterior) | Correo nuevo | Contrasena asignada |\n|---:|---|---|---|\n";
            foreach ($changes as $change) {
                $contents .= '| ' . $change['id'] . ' | ' . $this->reportCell($change['old_email']) . ' | ' . $this->reportCell($change['new_email']) . ' | `' . $change['password'] . "` |\n";
            }
        }
        $this->writeReport($report, $contents);
    }

    private function reportCell(string $value): string
    {
        return str_replace(["|", "\r", "\n"], ["\\|", ' ', ' '], $value);
    }

    private function writeReport($report, string $contents): void
    {
        while ($contents !== '') {
            $written = fwrite($report, $contents);
            if ($written === false || $written === 0) {
                throw new RuntimeException('Could not write the external credential report.');
            }
            $contents = substr($contents, $written);
        }
        if (!fflush($report)) {
            throw new RuntimeException('Could not flush the external credential report.');
        }
    }

    private function clearPasswordResetTokens(array $changes): void
    {
        if ($changes === []) {
            return;
        }
        $emails = [];
        foreach ($changes as $change) {
            $emails[] = $change['old_email'];
            $emails[] = $change['new_email'];
        }
        foreach (['password_reset_tokens', 'password_resets'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'email')) {
                DB::table($table)->whereIn('email', array_unique($emails))->delete();
            }
        }
    }

    public function down(): void
    {
        throw new LogicException('Credential rotation is irreversible; restore access with a separately approved forward migration.');
    }
};
