<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Faq;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ListPublicFaqs
{
    /**
     * Seconds between warnings about withheld entries, so anonymous traffic
     * cannot flood the log while an entry waits for correction.
     */
    private const int WITHHELD_WARNING_INTERVAL = 300;

    /**
     * Build the complete public FAQ collection as question and answer pairs,
     * in the order editors set in the dashboard (urutan), then by internal key.
     *
     * The collection is never partial: when the source cannot be read or more
     * entries are eligible than the configured bound, the whole request fails.
     *
     * @return list<array{pertanyaan: string, jawaban: string}>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        $maxItems = config()->integer('pintu.public_faq.max_items');

        try {
            $faqs = Faq::query()
                ->eligibleForPublic()
                ->orderBy('urutan')
                ->orderBy('id')
                ->limit($maxItems + 1)
                ->get(['id', 'pertanyaan', 'jawaban', 'status', 'publik', 'tampil']);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The FAQ source could not be read.', previous: $exception);
        }

        if ($faqs->count() > $maxItems) {
            throw new PublicContentUnavailableException("More than {$maxItems} FAQ entries are eligible for the public collection.");
        }

        [$publishable, $withheld] = $faqs->partition(fn (Faq $faq): bool => $faq->isPublishable());

        if ($withheld->isNotEmpty()) {
            RateLimiter::attempt('pintu:public-faq-withheld', 1, fn () => Log::warning(
                'Public FAQ entries withheld: stored values are not publishable.',
                ['ids' => $withheld->values()->modelKeys()],
            ), self::WITHHELD_WARNING_INTERVAL);
        }

        return $publishable
            ->map(fn (Faq $faq): array => [
                'pertanyaan' => $faq->pertanyaan,
                'jawaban' => $faq->jawaban,
            ])
            ->values()
            ->all();
    }
}
