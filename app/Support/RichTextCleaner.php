<?php

namespace App\Support;

use App\Rules\PortalLink;
use App\Rules\RichText;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Facades\Validator;

/**
 * Turns HTML written by an AI into the HTML the dashboard's text editor writes
 * (D-085, RichText): paragraphs, two subheading levels, lists, quotes, bold,
 * italic, underline, strikethrough, line breaks, dividers and https links,
 * in the editor's shape (list items and quotes hold paragraphs). Other
 * headings become h2 or h3, wrappers such as div or table are unwrapped, and
 * scripts, images, media and forms are dropped with their content. A result
 * that would still fail RichText, such as text of invisible characters only,
 * gives nothing, so the draft is refused rather than saved wrong.
 */
class RichTextCleaner
{
    private const array DROPPED = ['script', 'style', 'noscript', 'template', 'img', 'picture', 'svg', 'iframe', 'object', 'embed', 'video', 'audio', 'canvas', 'form', 'input', 'button', 'select', 'textarea', 'head', 'title', 'meta', 'link'];

    private const array INLINE = ['strong' => 'strong', 'b' => 'strong', 'em' => 'em', 'i' => 'em', 'u' => 'u', 's' => 's', 'del' => 's', 'strike' => 's'];

    public function clean(string $html): string
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $root = $dom->getElementsByTagName('div')->item(0);

        $clean = $root === null ? '' : $this->blocks($root);

        return $clean !== '' && Validator::make(['isi' => $clean], ['isi' => [new RichText('Isi')]])->passes() ? $clean : '';
    }

    /**
     * The block-level HTML of a node's children. Loose text and inline
     * elements between blocks are gathered into paragraphs.
     */
    private function blocks(DOMNode $parent): string
    {
        $html = '';
        $loose = '';
        $flush = function () use (&$html, &$loose): void {
            if ($this->hasText($loose)) {
                $html .= '<p>'.$this->line($loose).'</p>';
            }
            $loose = '';
        };

        foreach ($parent->childNodes as $node) {
            $name = $node instanceof DOMElement ? strtolower($node->tagName) : '';

            if ($name === '' || isset(self::INLINE[$name]) || in_array($name, ['a', 'br', 'span', 'code', 'mark', 'small', 'sup', 'sub', 'abbr', 'cite', 'q', 'time', 'label', 'font'], true)) {
                $loose .= $this->inline($node);

                continue;
            }

            $flush();
            if (in_array($name, self::DROPPED, true)) {
                continue;
            }

            $html .= match ($name) {
                'p' => $this->paragraph($node),
                'h1', 'h2' => $this->heading('h2', $node),
                'h3', 'h4', 'h5', 'h6' => $this->heading('h3', $node),
                'ul', 'ol' => $this->list($name, $node),
                'blockquote' => $this->quote($node),
                'hr' => '<hr>',
                'li', 'dt', 'dd', 'figcaption', 'caption' => $this->paragraph($node),
                default => $this->blocks($node),
            };
        }
        $flush();

        return $html;
    }

    private function paragraph(DOMNode $node): string
    {
        $text = $this->line($this->inline($node, true));

        return $this->hasText($text) ? "<p>{$text}</p>" : '';
    }

    private function heading(string $tag, DOMNode $node): string
    {
        $text = $this->line($this->inline($node, true));

        return $this->hasText($text) ? "<{$tag}>{$text}</{$tag}>" : '';
    }

    /**
     * A line of inline HTML without the spaces left by dropped elements.
     */
    private function line(string $html): string
    {
        return trim(preg_replace('/ {2,}/', ' ', $html) ?? '');
    }

    private function list(string $tag, DOMNode $node): string
    {
        $items = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                $content = $this->blocks($child);
                $items .= $content === '' ? '' : "<li>{$content}</li>";
            } elseif ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                $items .= $this->list(strtolower($child->tagName), $child);
            }
        }

        return $items === '' ? '' : "<{$tag}>{$items}</{$tag}>";
    }

    private function quote(DOMNode $node): string
    {
        $content = $this->blocks($node);

        return $content === '' ? '' : "<blockquote>{$content}</blockquote>";
    }

    /**
     * The inline HTML of a node: text escaped, known marks kept, https links
     * kept with only their address, anything else reduced to its text.
     */
    private function inline(DOMNode $node, bool $childrenOnly = false): string
    {
        if ($node->nodeType === XML_TEXT_NODE || $node->nodeType === XML_CDATA_SECTION_NODE) {
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $node->nodeValue) ?? '';

            return htmlspecialchars(preg_replace('/\s+/u', ' ', $text) ?? '', ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $name = strtolower($node->tagName);
        if (! $childrenOnly && in_array($name, self::DROPPED, true)) {
            return '';
        }
        if (! $childrenOnly && $name === 'br') {
            return '<br>';
        }

        $content = '';
        foreach ($node->childNodes as $child) {
            $content .= $this->inline($child);
        }
        if ($childrenOnly) {
            return $content;
        }
        if (isset(self::INLINE[$name])) {
            $tag = self::INLINE[$name];

            return trim($content) === '' ? $content : "<{$tag}>{$content}</{$tag}>";
        }
        if ($name === 'a') {
            $href = trim($node->getAttribute('href'));

            return preg_match('~^https?://~i', $href) === 1 && PortalLink::allows($href) && trim($content) !== ''
                ? '<a href="'.htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8').'" rel="noopener">'.$content.'</a>'
                : $content;
        }

        // Block elements met inside a line, e.g. a paragraph in a link, keep a space around them
        return in_array($name, ['p', 'div', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? " {$content} " : $content;
    }

    private function hasText(string $html): bool
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== '';
    }
}
