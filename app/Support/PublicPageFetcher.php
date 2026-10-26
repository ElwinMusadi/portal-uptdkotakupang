<?php

namespace App\Support;

use App\Exceptions\SourceUnreadable;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use finfo;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads a public web page, PDF or image from a link an employee gives for an
 * AI draft (D-085), guarding the server against being used to reach inside
 * its own network: only http(s) on the standard ports, every address of the
 * host must be public, the connection goes to the address that was checked,
 * and each of at most three redirects is checked again. Pages are limited to
 * 3 MB and 15 seconds, PDFs and images to 10 MB. Nothing is stored.
 */
class PublicPageFetcher
{
    public const int MAX_REDIRECTS = 3;

    public const int MAX_PAGE_BYTES = 3 * 1024 * 1024;

    public const int MAX_FILE_BYTES = 10 * 1024 * 1024;

    /**
     * Characters of page text passed on to the AI.
     */
    public const int MAX_TEXT_CHARACTERS = 40000;

    /**
     * Fewer characters than this means the page is built by scripts or empty.
     */
    public const int MIN_TEXT_CHARACTERS = 200;

    private const string USER_AGENT = 'PINTU/1.0 (+https://uptdpenda-kupang.web.id)';

    private const string TOO_LARGE = 'Isi tautan terlalu besar (halaman maksimal 3 MB, PDF atau gambar maksimal 10 MB).';

    /**
     * Elements that are never the article's text.
     */
    private const array NOISE = ['script', 'style', 'noscript', 'template', 'svg', 'iframe', 'nav', 'header', 'footer', 'aside', 'form', 'button', 'select'];

    /**
     * Elements after which the text continues on a new line.
     */
    private const array BLOCKS = ['p', 'div', 'section', 'article', 'main', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'tr', 'table', 'ul', 'ol', 'dd', 'dt', 'figcaption', 'br'];

    public function __construct(private HostResolver $resolver) {}

    /**
     * @return array{jenis: 'halaman', alamat: string, judul: string, tanggal: string, teks: string}|array{jenis: 'pdf'|'gambar', alamat: string, mime: string, isi: string}
     *
     * @throws SourceUnreadable
     */
    public function fetch(string $url): array
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $response = $this->request($url);

            if ($response->redirect()) {
                $url = $this->resolveLocation($url, (string) $response->header('Location'));

                continue;
            }

            if (! $response->successful()) {
                throw new SourceUnreadable("Tautan menjawab HTTP {$response->status()}. Pastikan halamannya bisa dibuka tanpa masuk akun.");
            }

            return $this->read($url, $response);
        }

        throw new SourceUnreadable('Tautan dialihkan terlalu banyak kali.');
    }

    /**
     * @throws SourceUnreadable
     */
    private function request(string $url): Response
    {
        [$host, $port, $address] = $this->checkedTarget($url);

        try {
            return Http::withUserAgent(self::USER_AGENT)
                ->accept('text/html,application/xhtml+xml,application/pdf,image/jpeg,image/png;q=0.9,*/*;q=0.5')
                ->withOptions([
                    'allow_redirects' => false,
                    // Connect to the address that was checked, not to a second DNS answer
                    'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:".(str_contains($address, ':') ? "[{$address}]" : $address)]],
                    // Stop a download as soon as it passes the largest size allowed
                    'progress' => function (int $total, int $downloaded): void {
                        if ($total > self::MAX_FILE_BYTES || $downloaded > self::MAX_FILE_BYTES) {
                            throw new SourceUnreadable(self::TOO_LARGE);
                        }
                    },
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->get($url);
        } catch (ConnectionException|TransferException $exception) {
            if ($this->causedBy($exception, SourceUnreadable::class)) {
                throw new SourceUnreadable(self::TOO_LARGE);
            }

            throw new SourceUnreadable('Tautan tidak dapat dibuka. Periksa alamatnya, atau coba lagi.');
        }
    }

    /**
     * Whether an exception or one before it is of the given class.
     *
     * @param  class-string  $class
     */
    private function causedBy(?Throwable $exception, string $class): bool
    {
        for (; $exception !== null; $exception = $exception->getPrevious()) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * The host, port and one checked address to connect to.
     *
     * @return array{0: string, 1: int, 2: string}
     *
     * @throws SourceUnreadable
     */
    private function checkedTarget(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new SourceUnreadable('Tautan harus alamat http:// atau https:// yang lengkap.');
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (! in_array($port, [80, 443], true)) {
            throw new SourceUnreadable('Tautan harus memakai port web biasa (80 atau 443).');
        }

        $addresses = $this->resolver->resolve($host);
        if ($addresses === []) {
            throw new SourceUnreadable('Alamat tautan tidak ditemukan.');
        }

        foreach ($addresses as $address) {
            if (! self::isPublicAddress($address)) {
                throw new SourceUnreadable('Tautan menunjuk ke alamat jaringan internal dan tidak dapat dibuka.');
            }
        }

        return [$host, $port, $addresses[0]];
    }

    /**
     * Whether an address is on the public internet: not private, loopback,
     * link-local, shared (100.64.0.0/10) or otherwise reserved.
     */
    public static function isPublicAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $long = ip2long($address);

        // Shared address space of carrier-grade NAT, which PHP does not treat as private
        return $long === false || ($long & 0xFFC00000) !== (ip2long('100.64.0.0') & 0xFFC00000);
    }

    /**
     * @throws SourceUnreadable
     */
    private function resolveLocation(string $from, string $location): string
    {
        if ($location === '') {
            throw new SourceUnreadable('Tautan dialihkan tanpa alamat tujuan.');
        }
        if (preg_match('~^https?://~i', $location) === 1) {
            return $location;
        }

        $base = parse_url($from);
        $origin = $base['scheme'].'://'.$base['host'].(isset($base['port']) ? ':'.$base['port'] : '');
        if (str_starts_with($location, '//')) {
            return $base['scheme'].':'.$location;
        }
        if (str_starts_with($location, '/')) {
            return $origin.$location;
        }

        return $origin.preg_replace('~/[^/]*$~', '/', $base['path'] ?? '/').$location;
    }

    /**
     * @return array{jenis: 'halaman', alamat: string, judul: string, tanggal: string, teks: string}|array{jenis: 'pdf'|'gambar', alamat: string, mime: string, isi: string}
     *
     * @throws SourceUnreadable
     */
    private function read(string $url, Response $response): array
    {
        $declared = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $body = $response->body();
        $type = (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($body);
        $page = ! in_array($type, ['application/pdf', 'image/jpeg', 'image/png'], true);

        if (strlen($body) > ($page ? self::MAX_PAGE_BYTES : self::MAX_FILE_BYTES)) {
            throw new SourceUnreadable(self::TOO_LARGE);
        }

        if ($type === 'application/pdf') {
            return ['jenis' => 'pdf', 'alamat' => $url, 'mime' => 'application/pdf', 'isi' => $body];
        }
        if (in_array($type, ['image/jpeg', 'image/png'], true)) {
            return ['jenis' => 'gambar', 'alamat' => $url, 'mime' => $type, 'isi' => $body];
        }
        if (str_contains($declared, 'html') || str_contains($type, 'html') || $declared === 'text/plain') {
            return ['jenis' => 'halaman', 'alamat' => $url, ...$this->pageText($body, $declared === 'text/plain' && ! str_contains($type, 'html'))];
        }

        throw new SourceUnreadable('Tautan tidak berisi halaman web, PDF, atau gambar JPEG/PNG.');
    }

    /**
     * The title, date and readable text of a page: the article or main part
     * when there is one, without scripts, menus, headers and footers.
     *
     * @return array{judul: string, tanggal: string, teks: string}
     *
     * @throws SourceUnreadable
     */
    private function pageText(string $body, bool $plain): array
    {
        if ($plain) {
            return ['judul' => '', 'tanggal' => '', 'teks' => $this->checkedText($body)];
        }

        if (! mb_check_encoding($body, 'UTF-8')) {
            $body = (string) mb_convert_encoding($body, 'UTF-8', 'Windows-1252');
        }

        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$body, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        $meta = fn (string $query): string => trim((string) ($xpath->query($query)->item(0)?->nodeValue ?? ''));
        $title = $meta('//meta[@property="og:title"]/@content') ?: $meta('//title');
        $date = $meta('//meta[@property="article:published_time"]/@content') ?: $meta('//time/@datetime');

        foreach (self::NOISE as $name) {
            foreach (iterator_to_array($dom->getElementsByTagName($name)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $root = null;
        foreach (['//article', '//main', '//*[@role="main"]'] as $query) {
            $candidate = $xpath->query($query)->item(0);
            if ($candidate instanceof DOMElement && mb_strlen(trim($candidate->textContent)) >= self::MIN_TEXT_CHARACTERS) {
                $root = $candidate;
                break;
            }
        }
        $root ??= $dom->getElementsByTagName('body')->item(0) ?? $dom->documentElement;

        return [
            'judul' => mb_substr(preg_replace('/\s+/u', ' ', $title) ?? '', 0, 300),
            'tanggal' => mb_substr($date, 0, 40),
            'teks' => $this->checkedText($root === null ? '' : $this->text($root)),
        ];
    }

    private function text(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->nodeValue ?? '';
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->text($child);
        }

        return $node instanceof DOMElement && in_array(strtolower($node->tagName), self::BLOCKS, true) ? "\n{$text}\n" : $text;
    }

    /**
     * @throws SourceUnreadable
     */
    private function checkedText(string $text): string
    {
        $text = preg_replace('/[^\S\n]+/u', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s*\n\s*(\n\s*)+/u', "\n\n", preg_replace('/ *\n */u', "\n", $text) ?? '') ?? '');

        if (mb_strlen($text) < self::MIN_TEXT_CHARACTERS) {
            throw new SourceUnreadable('Isi halaman tidak dapat dibaca dari tautan ini, mungkin karena dimuat dengan skrip. Simpan halamannya sebagai PDF, lalu unggah.');
        }

        return mb_substr($text, 0, self::MAX_TEXT_CHARACTERS);
    }
}
