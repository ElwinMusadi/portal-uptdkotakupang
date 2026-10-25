<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations: the announcement bar moves from Pengaturan Situs
     * ("situs") to its own document "berita", part of Berita & Pengumuman
     * (D-082). A saved bar keeps its text and its time, so the portal goes on
     * showing it; the site document keeps its time too. A "berita" document
     * already there is not replaced.
     */
    public function up(): void
    {
        $site = DB::table('settings')->where('name', 'situs')->first();
        $value = $site === null ? null : json_decode($site->value, true);
        if (! is_array($value) || ! array_key_exists('bilahPengumuman', $value)) {
            return;
        }

        if (! DB::table('settings')->where('name', 'berita')->exists()) {
            DB::table('settings')->insert([
                'name' => 'berita',
                'value' => json_encode($value['bilahPengumuman']),
                'created_at' => $site->updated_at,
                'updated_at' => $site->updated_at,
            ]);
        }

        unset($value['bilahPengumuman']);
        DB::table('settings')->where('name', 'situs')->update(['value' => json_encode($value)]);
    }

    /**
     * Reverse the migrations: the bar goes back into a saved site document.
     */
    public function down(): void
    {
        $bar = DB::table('settings')->where('name', 'berita')->first();
        $site = DB::table('settings')->where('name', 'situs')->first();
        if ($bar === null || $site === null) {
            return;
        }

        $value = json_decode($site->value, true);
        $value['bilahPengumuman'] = json_decode($bar->value, true);
        DB::table('settings')->where('name', 'situs')->update(['value' => json_encode($value)]);
        DB::table('settings')->where('name', 'berita')->delete();
    }
};
