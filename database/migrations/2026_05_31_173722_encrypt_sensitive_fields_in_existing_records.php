<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypt a single field value.
     * - If the value is null or empty, return null.
     * - If already encrypted (valid Laravel payload), leave as-is.
     * - Otherwise encrypt the plaintext.
     */
    private function encryptIfPlaintext(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            // Try to decrypt; if it succeeds the value is already encrypted
            Crypt::decryptString($value);
            return $value; // already encrypted — no change needed
        } catch (\Exception $e) {
            // Plaintext — encrypt it now
            return Crypt::encryptString($value);
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ── residents ──────────────────────────────────────────────
        DB::table('residents')->orderBy('id')->each(function ($row) {
            DB::table('residents')->where('id', $row->id)->update([
                'ic_number' => $this->encryptIfPlaintext($row->ic_number),
            ]);
        });

        // ── guards ─────────────────────────────────────────────────
        DB::table('guards')->orderBy('id')->each(function ($row) {
            DB::table('guards')->where('id', $row->id)->update([
                'ic_number' => $this->encryptIfPlaintext($row->ic_number),
            ]);
        });

        // ── visitors ───────────────────────────────────────────────
        DB::table('visitors')->orderBy('id')->each(function ($row) {
            DB::table('visitors')->where('id', $row->id)->update([
                'ic_number'       => $this->encryptIfPlaintext($row->ic_number),
                'face_descriptor' => $this->encryptIfPlaintext($row->face_descriptor),
            ]);
        });

        // ── delivery_personnel ─────────────────────────────────────
        DB::table('delivery_personnels')->orderBy('id')->each(function ($row) {
            DB::table('delivery_personnels')->where('id', $row->id)->update([
                'ic_number'       => $this->encryptIfPlaintext($row->ic_number),
                'face_descriptor' => $this->encryptIfPlaintext($row->face_descriptor),
            ]);
        });
    }

    /**
     * Reverse the migrations — decrypt back to plaintext.
     * (Useful during development; remove in production if data must stay encrypted.)
     */
    public function down(): void
    {
        $decrypt = function (?string $value): ?string {
            if ($value === null || $value === '') {
                return $value;
            }
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value; // already plaintext
            }
        };

        DB::table('residents')->orderBy('id')->each(function ($row) use ($decrypt) {
            DB::table('residents')->where('id', $row->id)->update([
                'ic_number' => $decrypt($row->ic_number),
            ]);
        });

        DB::table('guards')->orderBy('id')->each(function ($row) use ($decrypt) {
            DB::table('guards')->where('id', $row->id)->update([
                'ic_number' => $decrypt($row->ic_number),
            ]);
        });

        DB::table('visitors')->orderBy('id')->each(function ($row) use ($decrypt) {
            DB::table('visitors')->where('id', $row->id)->update([
                'ic_number'       => $decrypt($row->ic_number),
                'face_descriptor' => $decrypt($row->face_descriptor),
            ]);
        });

        DB::table('delivery_personnels')->orderBy('id')->each(function ($row) use ($decrypt) {
            DB::table('delivery_personnels')->where('id', $row->id)->update([
                'ic_number'       => $decrypt($row->ic_number),
                'face_descriptor' => $decrypt($row->face_descriptor),
            ]);
        });
    }
};
