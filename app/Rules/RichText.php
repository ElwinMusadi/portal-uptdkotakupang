<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * HTML as the dashboard's text editor writes it (D-024): paragraphs,
 * subheadings, lists, quotes, bold, italic, underline, strikethrough, line
 * breaks, dividers and links. Every "<" must open or close one of these
 * elements, written as the editor writes it: lowercase, with double-quoted
 * attributes, and attributes only on links and numbered lists. Anything
 * else, such as a script, an image, a style, an event attribute, a comment
 * or a link outside PortalLink, is rejected rather than removed, so what is
 * saved is what the editor showed. The portal still rebuilds the content
 * from these elements only.
 */
class RichText implements ValidationRule
{
    /**
     * Elements without attributes, as the editor writes them.
     *
     * @var list<string>
     */
    public const array PLAIN_ELEMENTS = ['p', 'h2', 'h3', 'ul', 'li', 'blockquote', 'strong', 'em', 'u', 's'];

    /**
     * The attributes the editor may write on links and numbered lists.
     *
     * @var array<string, list<string>>
     */
    public const array ATTRIBUTES = [
        'a' => ['href', 'target', 'rel', 'class', 'title'],
        'ol' => ['start', 'type'],
    ];

    /**
     * @param  string  $label  The field's name in messages, e.g. "Isi halaman".
     */
    public function __construct(private string $label) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (PlainText::hasForbiddenControlCharacter($value) || self::hasControlCharacterReference($value)) {
            $fail("{$this->label} berisi karakter kontrol yang tidak diizinkan.");

            return;
        }

        $problem = $this->firstProblem($value);

        if ($problem !== null) {
            $fail($problem);
        } elseif (! PlainText::hasVisibleText(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
            $fail("{$this->label} tidak boleh hanya berisi spasi atau karakter tak terlihat.");
        }
    }

    /**
     * Whether a numeric character reference such as "&#1;" stands for a
     * control character or for no character. PHP leaves those undecoded,
     * but browsers decode them.
     */
    private static function hasControlCharacterReference(string $html): bool
    {
        preg_match_all('/&#(?:[xX]([0-9a-fA-F]+)|([0-9]+))/', $html, $references, PREG_SET_ORDER);

        foreach ($references as $reference) {
            $codePoint = ($reference[1] ?? '') !== '' ? hexdec($reference[1]) : (float) $reference[2];

            if ($codePoint > 0x10FFFF || ($codePoint >= 0xD800 && $codePoint <= 0xDFFF)
                || PlainText::hasForbiddenControlCharacter(mb_chr((int) $codePoint, 'UTF-8'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read the tags from start to end and describe the first one the editor
     * would not have written.
     */
    private function firstProblem(string $html): ?string
    {
        $plain = implode('|', self::PLAIN_ELEMENTS);
        $tag = '/\G(?:<\/('.$plain.'|ol|a)>|<(br|hr)(?: ?\/)?>|<('.$plain.')>|<(a|ol)((?: [a-z]+="[^"]*")*)>)/';
        $offset = 0;

        while (($start = strpos($html, '<', $offset)) !== false) {
            if (preg_match($tag, $html, $match, 0, $start) !== 1) {
                return $this->describeUnsupportedTag(mb_strcut($html, $start, 40, 'UTF-8'));
            }

            $offset = $start + strlen($match[0]);

            if (($match[4] ?? '') !== '') {
                $problem = $this->attributeProblem($match[4], $match[5] ?? '');

                if ($problem !== null) {
                    return $problem;
                }
            }
        }

        return null;
    }

    private function describeUnsupportedTag(string $fragment): string
    {
        if (preg_match('/^<\/?([a-z][a-z0-9-]*)/i', $fragment, $name) === 1
            && ! in_array(strtolower($name[1]), [...self::PLAIN_ELEMENTS, 'ol', 'a', 'br', 'hr'], true)) {
            return "{$this->label} memuat elemen <".strtolower($name[1]).'> yang tidak didukung. Gunakan paragraf, subjudul, daftar, kutipan, dan tautan dari editor.';
        }

        return "{$this->label} memuat kode HTML yang tidak didukung editor di dekat \"".Str::limit($fragment, 30).'". Hapus lalu tulis ulang bagian itu di editor.';
    }

    private function attributeProblem(string $element, string $attributes): ?string
    {
        preg_match_all('/ ([a-z]+)="([^"]*)"/', $attributes, $pairs, PREG_SET_ORDER);
        $values = [];

        foreach ($pairs as [, $name, $raw]) {
            if (! in_array($name, self::ATTRIBUTES[$element], true) || array_key_exists($name, $values)) {
                return "{$this->label} memuat atribut {$name} pada <{$element}> yang tidak didukung.";
            }

            $values[$name] = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (PlainText::hasForbiddenControlCharacter($values[$name]) || preg_match('/[\t\n\r]/', $values[$name]) === 1) {
                return "{$this->label} berisi karakter kontrol yang tidak diizinkan.";
            }
        }

        if ($element === 'a' && ! PortalLink::allows($values['href'] ?? '')) {
            return "{$this->label}: alamat tautan \"".Str::limit($values['href'] ?? '', 60).'" tidak valid. Gunakan alamat lengkap (https://…) atau nama halaman portal (mis. kontak.html#masukan).';
        }

        if ($element === 'ol' && (preg_match('/^\d{1,6}$/', $values['start'] ?? '1') !== 1 || ! in_array($values['type'] ?? '1', ['1', 'a', 'A', 'i', 'I'], true))) {
            return "{$this->label} memuat penomoran daftar yang tidak didukung.";
        }

        return null;
    }
}
