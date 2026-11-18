<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The questionnaires for taxpayers and operators (D-095, D-096, D-097). Their questions
 * live in one file that the portal component reads and that the Apps Script
 * copies to build the Google Forms; google-form.json holds each form's address
 * and entry numbers once the script has run.
 */
class QuestionnaireTest extends TestCase
{
    private const KINDS = ['operator', 'wajib-pajak'];

    /**
     * @return array{skala: list<string>, kuesioner: array<string, array{judul: string, untuk: string, ringkas: string, pembuka: string, sebelum: array{teks: string, fitur: list<array{nama: string, alamat: string, keterangan: string}>, konfirmasi: string}, pertanyaan: list<array{kunci: string, jenis: string, teks: string, aspek?: string, wajib?: bool, petunjuk?: string, pilihan?: list<string>, min?: int, maks?: int, satuan?: string, kelompok?: list<int>}>}>}
     */
    private function questions(): array
    {
        return json_decode((string) file_get_contents(resource_path('js/portal/kuesioner/pertanyaan.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    private function statements(string $kind): array
    {
        return array_merge(...array_values($this->statementsByAspect($kind)));
    }

    /**
     * @return array<string, list<string>>
     */
    private function statementsByAspect(string $kind): array
    {
        $aspects = [];
        foreach ($this->questions()['kuesioner'][$kind]['pertanyaan'] as $question) {
            if ($question['jenis'] === 'skala') {
                $aspects[$question['aspek'] ?? ''][] = $question['teks'];
            }
        }

        return $aspects;
    }

    public function test_asks_the_operators_fifteen_statements_in_five_aspects(): void
    {
        $this->assertSame([
            'Portal untuk warga' => [
                'Menu website mudah dipahami.',
                'Informasi layanan dan persyaratan lengkap.',
                'Informasi mudah dibaca di telepon pintar.',
            ],
            'Kemudahan dashboard' => [
                'Dashboard mudah digunakan untuk memperbarui konten.',
                'Saya dapat menemukan menu dashboard yang saya perlukan tanpa bantuan.',
                'Petunjuk dan pesan kesalahan di dashboard membantu saya mengisi data dengan benar.',
            ],
            'Fungsi dan kinerja' => [
                'Fitur dashboard yang saya coba berjalan sesuai fungsinya.',
                'Perubahan yang disimpan di dashboard tampil dengan benar di portal.',
                'Dashboard terbuka dan menyimpan perubahan dengan cepat.',
            ],
            'Alur kerja dan keamanan' => [
                'Alur pemeriksaan dan persetujuan konten jelas.',
                'Hak akses akun saya sesuai dengan tugas saya.',
                'Saya merasa akun dan data di dashboard terlindungi dengan baik.',
            ],
            'Dukungan dan kepuasan' => [
                'Panduan Singkat Operator membantu saya.',
                'PINTU membantu pekerjaan saya dalam menyampaikan informasi kepada wajib pajak.',
                'Secara keseluruhan, saya puas menggunakan PINTU.',
            ],
        ], $this->statementsByAspect('operator'));
    }

    public function test_asks_the_taxpayers_fifteen_statements_in_five_aspects(): void
    {
        $this->assertSame([
            'Kemudahan penggunaan' => [
                'Menu dan susunan halaman PINTU mudah dipahami.',
                'Saya mudah menemukan informasi persyaratan layanan di PINTU.',
                'Saya dapat menggunakan PINTU tanpa bantuan orang lain.',
            ],
            'Kualitas informasi' => [
                'Informasi di PINTU jelas dan mudah dipahami.',
                'Informasi persyaratan, biaya, dan jadwal layanan di PINTU lengkap.',
                'Informasi di PINTU sesuai dengan ketentuan yang berlaku di loket SAMSAT.',
            ],
            'Fungsi' => [
                'Fitur PINTU yang saya coba berjalan sesuai fungsinya.',
                'Hasil Cek Biaya Pajak membantu saya memperkirakan biaya yang perlu disiapkan.',
                'Tautan, tombol, dan unduhan di PINTU berfungsi tanpa kesalahan.',
            ],
            'Tampilan dan kinerja' => [
                'PINTU nyaman dibuka di telepon pintar.',
                'Halaman PINTU terbuka dengan cepat.',
                'Tampilan PINTU rapi dan tulisannya mudah dibaca.',
            ],
            'Manfaat dan kepuasan' => [
                'Informasi PINTU membantu saya menyiapkan berkas sebelum datang.',
                'Saya akan membuka PINTU lagi sebelum mengurus pajak kendaraan.',
                'Secara keseluruhan, saya puas dengan PINTU.',
            ],
        ], $this->statementsByAspect('wajib-pajak'));
    }

    public function test_keeps_the_statements_of_the_report_instruments_word_for_word(): void
    {
        // Output Kegiatan 5, Tabel 7: the form the operators filled in after the trial
        $trial = [
            'Menu website mudah dipahami.',
            'Informasi layanan dan persyaratan lengkap.',
            'Informasi mudah dibaca di telepon pintar.',
            'Dashboard mudah digunakan untuk memperbarui konten.',
            'Alur pemeriksaan dan persetujuan konten jelas.',
            'Panduan Singkat Operator membantu saya.',
        ];
        // Output Kegiatan 7, Tabel 3: the taxpayers' evaluation questionnaire
        $evaluation = [
            'Saya mudah menemukan informasi persyaratan layanan di PINTU.',
            'Informasi di PINTU jelas dan mudah dipahami.',
            'PINTU nyaman dibuka di telepon pintar.',
            'Informasi PINTU membantu saya menyiapkan berkas sebelum datang.',
            'Saya akan membuka PINTU lagi sebelum mengurus pajak kendaraan.',
        ];

        $this->assertSame($trial, array_values(array_intersect($this->statements('operator'), $trial)));
        $this->assertSame($evaluation, array_values(array_intersect($this->statements('wajib-pajak'), $evaluation)));
    }

    public function test_rates_on_a_five_point_scale_from_strongly_disagree_to_strongly_agree(): void
    {
        $this->assertSame(['Sangat tidak setuju', 'Tidak setuju', 'Netral', 'Setuju', 'Sangat setuju'], $this->questions()['skala']);
    }

    public function test_keeps_every_question_well_formed(): void
    {
        $questionnaires = $this->questions()['kuesioner'];
        $this->assertSame(self::KINDS, array_keys($questionnaires));

        foreach ($questionnaires as $kind => $questionnaire) {
            foreach (['judul', 'untuk', 'ringkas', 'pembuka'] as $field) {
                $this->assertNotSame('', trim($questionnaire[$field]), "{$kind}.{$field}");
            }

            $keys = array_column($questionnaire['pertanyaan'], 'kunci');
            $this->assertSame(array_values(array_unique($keys)), $keys, "{$kind}: kunci ganda");

            foreach ($questionnaire['pertanyaan'] as $question) {
                $label = "{$kind}.{$question['kunci']}";
                $this->assertMatchesRegularExpression('/^[a-z][a-z0-9]*$/', $question['kunci']);
                $this->assertContains($question['jenis'], ['skala', 'pilihan', 'pilihan-ganda', 'isian', 'teks', 'angka'], $label);
                $this->assertSame([], array_diff(array_keys($question), ['kunci', 'jenis', 'teks', 'aspek', 'wajib', 'petunjuk', 'pilihan', 'min', 'maks', 'satuan', 'kelompok']), $label);
                $this->assertNotSame('', trim($question['teks']));

                if ($question['jenis'] === 'angka') {
                    // Whole numbers within bounds; the age groups of the summary sheet lie inside them
                    $this->assertIsInt($question['min'] ?? null, $label);
                    $this->assertIsInt($question['maks'] ?? null, $label);
                    $this->assertLessThan($question['maks'], $question['min'], $label);
                    $this->assertLessThanOrEqual(999, $question['maks'], $label);
                    $groups = $question['kelompok'] ?? [];
                    $sorted = $groups;
                    sort($sorted);
                    $this->assertSame(array_values(array_unique($sorted)), $groups, "{$label}: kelompok berurutan");
                    foreach ($groups as $bound) {
                        $this->assertIsInt($bound, $label);
                        $this->assertGreaterThan($question['min'], $bound, $label);
                        $this->assertLessThanOrEqual($question['maks'], $bound, $label);
                    }
                } else {
                    foreach (['min', 'maks', 'satuan', 'kelompok'] as $field) {
                        $this->assertArrayNotHasKey($field, $question, $label);
                    }
                }

                if ($question['jenis'] === 'skala') {
                    // Every statement belongs to an aspect, and is required anyway
                    $this->assertNotSame('', trim($question['aspek'] ?? ''), $label);
                    $this->assertArrayNotHasKey('wajib', $question, $label);
                } else {
                    $this->assertArrayNotHasKey('aspek', $question, $label);
                    $this->assertContains($question['wajib'] ?? true, [true], $label);
                }

                if (in_array($question['jenis'], ['pilihan', 'pilihan-ganda'], true)) {
                    $choices = $question['pilihan'] ?? [];
                    $this->assertGreaterThanOrEqual(2, count($choices), $label);
                    $this->assertSame(array_values(array_unique($choices)), $choices, "{$label}: pilihan ganda");
                    $this->assertChoicesCountable($question['jenis'], $choices, $label);
                } else {
                    $this->assertArrayNotHasKey('pilihan', $question, $label);
                }
            }
        }
    }

    /**
     * The summary sheet counts each choice with COUNTIF: exactly for a single
     * choice, and with *wildcards* for several choices that Google writes as
     * "A, B, C" in one cell.
     *
     * @param  list<string>  $choices
     */
    private function assertChoicesCountable(string $type, array $choices, string $label): void
    {
        foreach ($choices as $choice) {
            $this->assertDoesNotMatchRegularExpression('/[*?~,"]|^[<>=]/', $choice, "{$label}: {$choice}");

            if ($type === 'pilihan-ganda') {
                foreach ($choices as $other) {
                    if ($other !== $choice) {
                        $this->assertStringNotContainsStringIgnoringCase($choice, $other, "{$label}: {$choice} di dalam {$other}");
                    }
                }
            }
        }
    }

    public function test_asks_only_the_taxpayers_their_name_age_gender_and_education_first(): void
    {
        $questionnaires = $this->questions()['kuesioner'];
        $profile = array_slice($questionnaires['wajib-pajak']['pertanyaan'], 0, 4);

        $this->assertSame(['nama', 'umur', 'kelamin', 'pendidikan'], array_column($profile, 'kunci'));
        $this->assertSame(['teks', 'angka', 'pilihan', 'pilihan'], array_column($profile, 'jenis'));
        $this->assertSame([true, true, true, true], array_map(fn (array $question): bool => $question['wajib'] ?? false, $profile));
        $this->assertSame([17, 100, 'tahun'], [$profile[1]['min'], $profile[1]['maks'], $profile[1]['satuan']]);
        $this->assertSame(['Laki-laki', 'Perempuan'], $profile[2]['pilihan']);
        $this->assertSame([
            'Tidak/belum tamat SD',
            'SD/sederajat',
            'SMP/sederajat',
            'SMA/SMK/sederajat',
            'Diploma (D1–D3)',
            'Sarjana (D4/S1)',
            'Pascasarjana (S2/S3)',
        ], $profile[3]['pilihan']);

        // The operators stay anonymous
        $this->assertSame([], array_intersect(['nama', 'umur', 'kelamin', 'pendidikan'], array_column($questionnaires['operator']['pertanyaan'], 'kunci')));
    }

    public function test_promises_anonymity_only_where_no_name_is_asked(): void
    {
        foreach ($this->questions()['kuesioner'] as $kind => $questionnaire) {
            $named = in_array('nama', array_column($questionnaire['pertanyaan'], 'kunci'), true);
            $texts = implode(' ', [$questionnaire['pembuka'], ...array_column($questionnaire['pertanyaan'], 'petunjuk')]);

            if ($named) {
                $this->assertStringNotContainsStringIgnoringCase('tanpa nama', $texts, $kind);
                $this->assertStringNotContainsStringIgnoringCase('menulis nama', $texts, $kind);
                $this->assertStringContainsString('tidak ditampilkan kepada publik', $questionnaire['pembuka'], $kind);
            } else {
                $this->assertStringContainsString('tanpa nama', $questionnaire['pembuka'], $kind);
            }
        }
    }

    public function test_asks_to_try_the_features_before_filling_in_and_which_ones_were_tried(): void
    {
        foreach ($this->questions()['kuesioner'] as $kind => $questionnaire) {
            $before = $questionnaire['sebelum'];
            $this->assertNotSame('', trim($before['teks']), $kind);
            $this->assertNotSame('', trim($before['konfirmasi']), $kind);
            $this->assertGreaterThanOrEqual(5, count($before['fitur']), $kind);

            foreach ($before['fitur'] as $feature) {
                $this->assertSame(['nama', 'alamat', 'keterangan'], array_keys($feature), $kind);
                $this->assertNotSame('', trim($feature['keterangan']), "{$kind}: {$feature['nama']}");
            }

            // The answer records which of the listed features were tried, and at least one must be
            $tried = array_values(array_filter($questionnaire['pertanyaan'], fn (array $question): bool => $question['kunci'] === 'fitur'));
            $this->assertCount(1, $tried, $kind);
            $this->assertSame('pilihan-ganda', $tried[0]['jenis'], $kind);
            $this->assertTrue($tried[0]['wajib'] ?? false, $kind);
            $this->assertSame([...array_column($before['fitur'], 'nama'), 'Lainnya'], $tried[0]['pilihan'], $kind);
        }
    }

    public function test_links_the_features_to_be_tried_to_pages_that_exist(): void
    {
        $router = (string) file_get_contents(resource_path('js/dashboard/app/router.tsx'));

        foreach ($this->questions()['kuesioner'] as $kind => $questionnaire) {
            foreach ($questionnaire['sebelum']['fitur'] as $feature) {
                $label = "{$kind}: {$feature['nama']}";
                $this->assertMatchesRegularExpression('#^/(?!/)#', $feature['alamat'], $label);
                [$path, $fragment] = array_pad(explode('#', $feature['alamat'], 2), 2, null);

                if ($path === '/dashboard') {
                    // Guests sign in first and come back to the same screen of the dashboard
                    $this->get($path)->assertRedirect('/login?lanjut=dashboard%2F');
                    if ($fragment !== null) {
                        $this->assertMatchesRegularExpression('#^/[a-z-]+$#', $fragment, $label);
                        $this->assertStringContainsString('path: "'.substr($fragment, 1).'"', $router, $label);
                    }

                    continue;
                }

                $response = $this->get($path)->assertOk();
                if ($fragment !== null) {
                    $response->assertSee('id="'.$fragment.'"', false);
                }
            }
        }
    }

    public function test_builds_the_google_forms_from_the_same_questions(): void
    {
        $script = (string) file_get_contents(base_path('scripts/kuesioner-google-form.gs'));

        $this->assertSame(1, preg_match('#// @pertanyaan\R+const KUESIONER = (.*?);\R+// /@pertanyaan#s', $script, $match));
        $this->assertSame($this->questions(), json_decode($match[1], true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_never_makes_a_google_form_question_required(): void
    {
        // Google drops the whole answer when a required question arrives empty, and the portal cannot see that
        $script = (string) file_get_contents(base_path('scripts/kuesioner-google-form.gs'));

        $this->assertSame(5, substr_count($script, '.setRequired(false)'));
        $this->assertStringNotContainsString('.setRequired(true)', $script);
    }

    public function test_leaves_each_questionnaire_closed_or_connected_to_every_question_of_its_form(): void
    {
        $forms = json_decode((string) file_get_contents(resource_path('js/portal/kuesioner/google-form.json')), true, flags: JSON_THROW_ON_ERROR);
        $kinds = array_keys($forms);
        sort($kinds);
        $this->assertSame(self::KINDS, $kinds);

        foreach ($this->questions()['kuesioner'] as $kind => $questionnaire) {
            $form = $forms[$kind];
            if ($form === null) {
                continue;
            }

            $this->assertSame(['aksi', 'entri'], array_keys($form), $kind);
            $this->assertMatchesRegularExpression('#^https://docs\.google\.com/forms/d/e/[\w-]+/formResponse$#', $form['aksi'], $kind);
            $this->assertSame(array_column($questionnaire['pertanyaan'], 'kunci'), array_keys($form['entri']), $kind);
            foreach ($form['entri'] as $entry) {
                $this->assertMatchesRegularExpression('/^entry\.\d+$/', $entry, $kind);
            }
            $this->assertSame(array_values(array_unique($form['entri'])), array_values($form['entri']), "{$kind}: entri ganda");
        }
    }
}
