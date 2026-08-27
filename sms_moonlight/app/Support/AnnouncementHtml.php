<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class AnnouncementHtml
{
    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        'ul',
        'ol',
        'li',
        'a',
        'h1',
        'h2',
        'h3',
        'h4',
        'blockquote',
        'code',
        'pre',
        'span',
    ];

    /**
     * Tags whose contents must not be retained.
     *
     * @var list<string>
     */
    private const BLOCKED_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'form',
        'input',
        'button',
        'svg',
        'math',
        'link',
        'meta',
    ];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="announcement-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        $root = $document->getElementById('announcement-root');

        if (! $root) {
            return '';
        }

        self::sanitizeChildren($root);

        $sanitized = '';
        foreach ($root->childNodes as $child) {
            $sanitized .= $document->saveHTML($child);
        }

        return trim($sanitized);
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::BLOCKED_TAGS, true)) {
                $parent->removeChild($node);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::sanitizeChildren($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            self::sanitizeAttributes($node, $tag);
            self::sanitizeChildren($node);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if ($tag !== 'a' || ! in_array(strtolower($attribute->name), ['href', 'target', 'title'], true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tag !== 'a') {
            return;
        }

        $href = trim($element->getAttribute('href'));
        if ($href !== '' && ! preg_match('/^(https?:\/\/|mailto:|tel:|#|\/)/i', $href)) {
            $element->removeAttribute('href');
        }

        $target = strtolower($element->getAttribute('target'));
        if ($target !== '' && ! in_array($target, ['_blank', '_self'], true)) {
            $element->removeAttribute('target');
        }

        if ($target === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }
}
