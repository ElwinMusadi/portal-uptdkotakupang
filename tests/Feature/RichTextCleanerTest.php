<?php

namespace Tests\Feature;

use App\Rules\RichText;
use App\Support\RichTextCleaner;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RichTextCleanerTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function documents(): array
    {
        return [
            'the editor\'s own HTML stays' => [
                '<h2>Jadwal</h2><p>Teks <strong>tebal</strong>, <em>miring</em>, <u>garis</u>, <s>coret</s>.<br>Baris dua.</p><ol><li><p>Satu</p></li></ol><blockquote><p>Kutipan</p></blockquote><hr>',
                '<h2>Jadwal</h2><p>Teks <strong>tebal</strong>, <em>miring</em>, <u>garis</u>, <s>coret</s>.<br>Baris dua.</p><ol><li><p>Satu</p></li></ol><blockquote><p>Kutipan</p></blockquote><hr>',
            ],
            'headings become two levels' => ['<h1>A</h1><h4>B</h4><h6>C</h6>', '<h2>A</h2><h3>B</h3><h3>C</h3>'],
            'marks are renamed' => ['<p><b>a</b> <i>b</i> <del>c</del> <strike>d</strike></p>', '<p><strong>a</strong> <em>b</em> <s>c</s> <s>d</s></p>'],
            'attributes are dropped' => ['<p class="x" style="color:red" onclick="y()">Teks</p><ol start="3" type="a"><li>Satu</li></ol>', '<p>Teks</p><ol><li><p>Satu</p></li></ol>'],
            'only https links stay' => [
                '<p><a href="javascript:alert(1)">a</a> <a href="kontak.html">b</a> <a href="https://contoh.id/x?a=1&amp;b=2" target="_blank" onclick="z()">c</a> <a href="https://contoh.id">  </a></p>',
                '<p>a b <a href="https://contoh.id/x?a=1&amp;b=2" rel="noopener">c</a></p>',
            ],
            'dangerous elements go with their content' => ['<p>Aman</p><script>curi()</script><style>p{}</style><img src="x.png" alt="foto"><iframe src="https://x.test"></iframe><form><input value="v"></form>', '<p>Aman</p>'],
            'also inside a line' => ['<p>Aman <script>curi()</script><img src="x.png"> <b>lagi <svg><text>gambar</text></svg></b></p>', '<p>Aman <strong>lagi </strong></p>'],
            'invisible text only gives nothing' => ["<p>\u{200B}\u{2060}</p>", ''],
            'wrappers are unwrapped and loose text becomes paragraphs' => ['Teks lepas <b>tebal</b><div><section><p>Dalam</p></section>Sisa</div><table><tr><td>Sel</td></tr></table>', '<p>Teks lepas <strong>tebal</strong></p><p>Dalam</p><p>Sisa</p><p>Sel</p>'],
            'empty blocks disappear' => ['<p> </p><h2></h2><ul><li> </li></ul><p>Isi</p>', '<p>Isi</p>'],
            'nested lists stay in their list' => ['<ul><li>Satu<ul><li>Dua</li></ul></li></ul>', '<ul><li><p>Satu</p><ul><li><p>Dua</p></li></ul></li></ul>'],
            'text is escaped and control characters removed' => ["<p>a &lt; b &amp; c \u{0007}d</p>", '<p>a &lt; b &amp; c d</p>'],
            'only dropped content gives nothing' => ['<script>curi()</script><img src="x.png">', ''],
        ];
    }

    #[DataProvider('documents')]
    public function test_cleans_html_to_what_the_editor_writes(string $html, string $expected): void
    {
        $clean = (new RichTextCleaner)->clean($html);

        $this->assertSame($expected, $clean);
        if ($clean !== '') {
            $this->assertTrue(Validator::make(['isi' => $clean], ['isi' => [new RichText('Isi')]])->passes());
        }
    }
}
