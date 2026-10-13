<?php

namespace App\Models;

use App\Enums\FaqStatus;
use App\Rules\PlainText;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pertanyaan', 'jawaban', 'status', 'publik', 'tampil', 'urutan'])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory, HasUlids;

    /**
     * Longest publishable question, in characters. Matches the dashboard form.
     */
    public const int MAX_QUESTION_LENGTH = 200;

    /**
     * Longest publishable answer, in characters. Matches the dashboard form.
     */
    public const int MAX_ANSWER_LENGTH = 800;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FaqStatus::class,
            'publik' => 'boolean',
            'tampil' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * Limit the query to entries whose stored flags allow public display:
     * published, explicitly intended for the public, and switched on.
     */
    #[Scope]
    protected function eligibleForPublic(Builder $query): void
    {
        $query->where('status', FaqStatus::Published->value)
            ->where('publik', true)
            ->where('tampil', true);
    }

    /**
     * Whether this entry may appear on the public portal as plain text.
     *
     * The status is compared exactly because database collations can treat
     * "Published" or "published " as equal to "published". Both texts must be
     * valid UTF-8, within their length limit, free of control characters other
     * than tab and line breaks, and contain something visible: not only
     * spaces, format or private-use characters, combining marks or blank
     * fillers. Failing entries are withheld until corrected; their text is
     * never trimmed or truncated.
     */
    public function isPublishable(): bool
    {
        return ($this->getAttributes()['status'] ?? null) === FaqStatus::Published->value
            && $this->publik === true
            && $this->tampil === true
            && self::isPublishableText($this->pertanyaan, self::MAX_QUESTION_LENGTH)
            && self::isPublishableText($this->jawaban, self::MAX_ANSWER_LENGTH);
    }

    private static function isPublishableText(mixed $text, int $maxLength): bool
    {
        return is_string($text)
            && mb_check_encoding($text, 'UTF-8')
            && mb_strlen($text, 'UTF-8') <= $maxLength
            && ! PlainText::hasForbiddenControlCharacter($text)
            && PlainText::hasVisibleText($text);
    }
}
