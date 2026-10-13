<?php

namespace Tests\Unit\Models;

use App\Models\Faq;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FaqTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function publishableValues(): array
    {
        return [
            'question at 200 characters' => [['pertanyaan' => str_repeat('ä', 200)]],
            'answer at 800 characters' => [['jawaban' => str_repeat('😀', 800)]],
            'answer with tabs and line breaks' => [['jawaban' => "Baris satu\r\nBaris\tdua\n"]],
            'answer with markup and a URL' => [['jawaban' => 'Lihat <a href="https://contoh.go.id">tautan</a>.']],
            'answer with surrounding spaces' => [['jawaban' => '  Bisa.  ']],
            'answer with a combining accent on a letter' => [['jawaban' => "Cafe\u{0301} buka."]],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    #[DataProvider('publishableValues')]
    public function test_is_publishable_with_valid_stored_values(array $values): void
    {
        $faq = $this->storedFaq($values);

        $this->assertTrue($faq->isPublishable());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function unpublishableValues(): array
    {
        return [
            'draft' => [['status' => 'draft']],
            'unpublished' => [['status' => 'unpublished']],
            'status in a different case' => [['status' => 'Published']],
            'status with a trailing space' => [['status' => 'published ']],
            'prototype status' => [['status' => 'terbit']],
            'missing status' => [['status' => null]],
            'not intended for the public' => [['publik' => 0]],
            'switched off' => [['tampil' => 0]],
            'empty question' => [['pertanyaan' => '']],
            'missing answer' => [['jawaban' => null]],
            'whitespace-only answer' => [['jawaban' => " \t\r\n "]],
            'answer of non-breaking spaces' => [['jawaban' => "\u{00A0}\u{3000}"]],
            'answer of invisible characters' => [['jawaban' => "\u{200B}\u{FEFF}"]],
            'answer of blank fillers' => [['jawaban' => "\u{3164}\u{2800}"]],
            'answer of combining marks only' => [['jawaban' => "\u{034F}\u{FE0F}"]],
            'answer of private-use characters' => [['jawaban' => "\u{E000}"]],
            'question over 200 characters' => [['pertanyaan' => str_repeat('ä', 201)]],
            'answer over 800 characters' => [['jawaban' => str_repeat('😀', 801)]],
            'answer with a control character' => [['jawaban' => "Bisa.\u{0007}"]],
            'answer with invalid UTF-8' => [['jawaban' => "Bisa \xC3\x28"]],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    #[DataProvider('unpublishableValues')]
    public function test_is_not_publishable_with_invalid_or_ineligible_stored_values(array $values): void
    {
        $faq = $this->storedFaq($values);

        $this->assertFalse($faq->isPublishable());
    }

    /**
     * Hydrate an entry from raw column values, as a database query would.
     *
     * @param  array<string, mixed>  $values
     */
    private function storedFaq(array $values): Faq
    {
        return (new Faq)->newFromBuilder([
            'id' => '01k00000000000000000000000',
            'pertanyaan' => 'Apakah pajak tahunan bisa diwakilkan?',
            'jawaban' => 'Bisa.',
            'status' => 'published',
            'publik' => 1,
            'tampil' => 1,
            ...$values,
        ]);
    }
}
