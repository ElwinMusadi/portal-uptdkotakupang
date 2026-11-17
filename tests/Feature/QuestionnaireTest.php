<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The questionnaires for taxpayers and operators (D-095). Their questions live
 * in one file that the portal component reads and that the Apps Script copies
 * to build the Google Forms; google-form.json holds each form's address and
 * entry numbers once the script has run.
 */
class QuestionnaireTest extends TestCase
{
    private const KINDS = ['operator', 'wajib-pajak'];

    /**
     * @return array{skala: list<string>, kuesioner: array<string, array{judul: string, untuk: string, ringkas: string, pembuka: string, pertanyaan: list<array{kunci: string, jenis: string, teks: string, petunjuk?: string, pilihan?: list<string>}>}>}
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
        $questions = array_filter($this->questions()['kuesioner'][$kind]['pertanyaan'], fn (array $question): bool => $question['jenis'] === 'skala');

        return array_values(array_map(fn (array $question): string => $question['teks'], $questions));
    }

    public function test_asks_the_operators_the_statements_of_the_trial_feedback_form(): void
    {
        // Output Kegiatan 5, Tabel 7: the same form the operators filled in after the trial
        $this->assertSame([
            'Menu website mudah dipahami.',
            'Informasi layanan dan persyaratan lengkap.',
            'Informasi mudah dibaca di telepon pintar.',
            'Dashboard mudah digunakan untuk memperbarui konten.',
            'Alur pemeriksaan dan persetujuan konten jelas.',
            'Panduan Singkat Operator membantu saya.',
        ], $this->statements('operator'));
    }

    public function test_asks_the_taxpayers_the_statements_of_the_evaluation_questionnaire(): void
    {
        // Output Kegiatan 7, Tabel 3
        $this->assertSame([
            'Saya mudah menemukan informasi persyaratan layanan di PINTU.',
            'Informasi di PINTU jelas dan mudah dipahami.',
            'PINTU nyaman dibuka di telepon pintar.',
            'Informasi PINTU membantu saya menyiapkan berkas sebelum datang.',
            'Saya akan membuka PINTU lagi sebelum mengurus pajak kendaraan.',
        ], $this->statements('wajib-pajak'));
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
                $this->assertMatchesRegularExpression('/^[a-z][a-z0-9]*$/', $question['kunci']);
                $this->assertContains($question['jenis'], ['skala', 'pilihan', 'pilihan-ganda', 'isian'], $question['kunci']);
                $this->assertNotSame('', trim($question['teks']));

                if (in_array($question['jenis'], ['pilihan', 'pilihan-ganda'], true)) {
                    $choices = $question['pilihan'] ?? [];
                    $this->assertGreaterThanOrEqual(2, count($choices), $question['kunci']);
                    $this->assertSame(array_values(array_unique($choices)), $choices, "{$kind}.{$question['kunci']}: pilihan ganda");
                } else {
                    $this->assertArrayNotHasKey('pilihan', $question, "{$kind}.{$question['kunci']}");
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

        $this->assertSame(4, substr_count($script, '.setRequired(false)'));
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
