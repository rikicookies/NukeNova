<?php

declare(strict_types=1);

namespace NovaNuke\Core\Security;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlSanitizer
{
    private const TAGS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre',
        'h2', 'h3', 'h4', 'a', 'span', 'div', 'hr',
    ];
    private const ATTRIBUTES = ['class', 'title'];

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="novanuke-sanitizer-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return '';
        }
        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root instanceof DOMElement) {
            return '';
        }
        $this->cleanChildren($root);
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($node->tagName);
            if (! in_array($tag, self::TAGS, true)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'], true)) {
                    $parent->removeChild($node);
                    continue;
                }
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if ($tag === 'a' && in_array($name, ['href', 'target', 'rel'], true)) {
                    continue;
                }
                if (! in_array($name, self::ATTRIBUTES, true) || str_starts_with($name, 'on')) {
                    $node->removeAttribute($attribute->name);
                }
            }
            if ($tag === 'a') {
                $href = trim($node->getAttribute('href'));
                if (! $this->safeUrl($href)) {
                    $node->removeAttribute('href');
                }
                if ($node->getAttribute('target') === '_blank') {
                    $node->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $node->removeAttribute('target');
                    $node->removeAttribute('rel');
                }
            }
            $this->cleanChildren($node);
        }
    }

    private function safeUrl(string $url): bool
    {
        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }
}
