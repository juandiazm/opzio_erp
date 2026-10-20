<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'users';

    public function up(): void
    {
        $this->assertAccountTable();

        DB::transaction(function (): void {
            $rows = $this->candidateUsers()
                ->orderBy(self::TABLE . '.id')
                ->lockForUpdate()
                ->get([self::TABLE . '.id', self::TABLE . '.email']);
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
                $changes[] = ['id' => $row->id, 'new_email' => $newEmail];
            }

            foreach ($changes as $change) {
                if (DB::table(self::TABLE)->where('id', $change['id'])->update(['email' => $change['new_email']]) !== 1) {
                    throw new RuntimeException('An administrator email changed during the migration.');
                }
            }
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
        foreach (['id', 'email'] as $column) {
            if (!Schema::hasColumn(self::TABLE, $column)) {
                throw new RuntimeException('The administrator table is missing a required column.');
            }
        }
    }

    public function down(): void
    {
        throw new LogicException('Email-domain migration is irreversible; restore the original addresses with a separately approved migration.');
    }
};
