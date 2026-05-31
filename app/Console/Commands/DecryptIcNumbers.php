<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DecryptIcNumbers extends Command
{
    protected $signature = 'ic:decrypt';
    protected $description = 'Decrypt ic_number fields across all tables that used the encrypted cast';

    public function handle()
    {
        $tables = [
            'residents'           => 'ic_number',
            'visitors'            => 'ic_number',
            'guards'              => 'ic_number',
            'delivery_personnels' => 'ic_number',
        ];

        foreach ($tables as $table => $column) {
            $this->info("Processing table: {$table}");

            $rows = DB::table($table)->get(['id', $column]);
            $fixed = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $raw = $row->{$column};

                if (empty($raw)) {
                    $skipped++;
                    continue;
                }

                // Try to decrypt — if it fails, the value is already plain text
                try {
                    $decrypted = Crypt::decryptString($raw);
                    DB::table($table)->where('id', $row->id)->update([$column => $decrypted]);
                    $fixed++;
                } catch (\Exception $e) {
                    // Already plain text — leave it as-is
                    $skipped++;
                }
            }

            $this->line("  ✔ {$fixed} decrypted, {$skipped} already plain text / empty");
        }

        $this->info('Done! All ic_number values are now stored as plain text.');
    }
}
