<?php

namespace App\Support;

use App\Exceptions\SourceUnreadable;
use DOMDocument;
use DOMElement;
use ZipArchive;

/**
 * The text of a Word document (.docx) for an AI draft (D-085), paragraph by
 * paragraph, since the AI reads PDFs and images but not Word files. Only
 * word/document.xml is read, never more than 20 MB of it, without network
 * access or entities.
 */
class DocxText
{
    public const int MAX_XML_BYTES = 20 * 1024 * 1024;

    public const int MAX_TEXT_CHARACTERS = 60000;

    private const string WORD = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * Whether a file is a Word document: a zip archive with word/document.xml.
     */
    public static function isDocx(string $path): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }
        $found = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        return $found;
    }

    /**
     * @throws SourceUnreadable
     */
    public function extract(string $path, string $name): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new SourceUnreadable("Dokumen {$name} tidak dapat dibuka.", 'berkas');
        }

        $stat = $zip->statName('word/document.xml');
        $xml = is_array($stat) && $stat['size'] <= self::MAX_XML_BYTES ? $zip->getFromName('word/document.xml') : false;
        $zip->close();

        $dom = new DOMDocument;
        if (! is_string($xml) || ! @$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new SourceUnreadable("Dokumen {$name} tidak dapat dibaca. Simpan sebagai PDF, lalu unggah.", 'berkas');
        }

        $paragraphs = [];
        foreach ($dom->getElementsByTagNameNS(self::WORD, 'p') as $paragraph) {
            $paragraphs[] = $this->paragraph($paragraph);
        }

        $text = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $paragraphs)) ?? '');
        if ($text === '') {
            throw new SourceUnreadable("Dokumen {$name} tidak berisi teks. Bila isinya hasil pindaian, unggah sebagai PDF atau gambar.", 'berkas');
        }

        return mb_substr($text, 0, self::MAX_TEXT_CHARACTERS);
    }

    private function paragraph(DOMElement $paragraph): string
    {
        $text = '';
        foreach ($paragraph->getElementsByTagNameNS(self::WORD, '*') as $node) {
            $text .= match ($node->localName) {
                't' => $node->textContent,
                'tab' => "\t",
                'br', 'cr' => "\n",
                default => '',
            };
        }

        return trim($text);
    }
}
