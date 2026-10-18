<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TABLE = 'users';

    public function up(): void
    {
        $this->assertAccountTable();
        DB::transaction(function (): void {
            $rows = $this->candidateUsers()->orderBy('id')->lockForUpdate()->get(['id', 'email']);
            $changes = [];
            $seenEmails = [];
            foreach ($rows as $row) {
                $newEmail = preg_replace('/@ridder\.com\.co$/i', '@opzio.co', (string) $row->email);
                if (!is_string($newEmail)) {
                    throw new RuntimeException('Could not derive the replacement email address.');
                }
                $normalizedEmail = strtolower($newEmail);
                if (isset($seenEmails[$normalizedEmail]) ||
                    DB::table(self::TABLE)->whereRaw('LOWER(email) = ?', [$normalizedEmail])->where('id', '<>', $row->id)->exists()) {
                    throw new RuntimeException('A replacement email already exists; no accounts were changed.');
                }
                $seenEmails[$normalizedEmail] = true;
                $changes[] = ['id' => $row->id, 'old_email' => (string) $row->email, 'new_email' => $newEmail];
            }
            foreach ($changes as $change) {
                $values = ['email' => $change['new_email']];
                foreach (['otp', 'otp_expdate', 'reset_password', 'reset_password_date', 'temp_password', 'temp_password_date', 'temporary_password_expires_at'] as $column) {
                    if (Schema::hasColumn(self::TABLE, $column)) {
                        $values[$column] = null;
                    }
                }
                if (Schema::hasColumn(self::TABLE, 'remember_token')) {
                    $values['remember_token'] = Str::random(60);
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
