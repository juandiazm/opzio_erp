<?php

namespace App\Support;

class SanitizedHtml
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'blockquote', 'div', 'span', 'a',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    private const ALLOWED_ATTRIBUTES = ['style', 'href', 'target', 'rel', 'colspan', 'rowspan'];

    private const ALLOWED_STYLES = [
        'text-align', 'font-weight', 'font-style', 'text-decoration', 'color',
        'background-color', 'font-size', 'font-family', 'line-height', 'padding',
        'margin', 'width', 'height', 'border', 'border-top', 'border-right',
        'border-bottom', 'border-left', 'border-collapse', 'vertical-align',
        'float', 'display', 'box-sizing', 'page-break-inside', 'page-break-before',
        'page-break-after',
    ];

    public static function clean($html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        if (!class_exists(\DOMDocument::class)) {
            $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
            $html = preg_replace('/(?:javascript|vbscript)\s*:/i', '', $html);
            return strip_tags($html, '<'.implode('><', self::ALLOWED_TAGS).'>');
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="income-html-root">'.$html.'</div>',
            LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        $root = (new \DOMXPath($document))->query('//*[@id="income-html-root"]')->item(0);
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '';
        }

        $forbiddenTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'meta', 'link'];
        $sanitizeNode = function ($node) use (&$sanitizeNode, $forbiddenTags) {
            for ($child = $node->firstChild; $child;) {
                $next = $child->nextSibling;
                if ($child instanceof \DOMElement) {
                    $tag = strtolower($child->tagName);
                    if (in_array($tag, $forbiddenTags, true)) {
                        $node->removeChild($child);
                        $child = $next;
                        continue;
                    }
                    if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                        $child = $next;
                        continue;
                    }
                    for ($index = $child->attributes->length - 1; $index >= 0; $index--) {
                        $attribute = $child->attributes->item($index);
                        $name = strtolower($attribute->name);
                        if (!in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                            $child->removeAttribute($attribute->name);
                            continue;
                        }
                        if ($name === 'style') {
                            $styles = [];
                            foreach (explode(';', $attribute->value) as $declaration) {
                                $parts = explode(':', $declaration, 2);
                                $property = strtolower(trim($parts[0] ?? ''));
                                $value = trim($parts[1] ?? '');
                                if ($property === '' || $value === '' || !in_array($property, self::ALLOWED_STYLES, true) || preg_match('/url\s*\(|expression\s*\(|javascript\s*:|vbscript\s*:|[<>]/i', $value)) {
                                    continue;
                                }
                                $styles[] = $property.': '.$value;
                            }
                            if ($styles) {
                                $child->setAttribute('style', implode('; ', $styles));
                            } else {
                                $child->removeAttribute('style');
                            }
                        }
                        if ($name === 'href' && !preg_match('/^(https?:|mailto:|\/|#)/i', trim($attribute->value))) {
                            $child->removeAttribute('href');
                        }
                    }
                    $sanitizeNode($child);
                }
                $child = $next;
            }
        };

        $sanitizeNode($root);
        $result = '';
        for ($child = $root->firstChild; $child; $child = $child->nextSibling) {
            $result .= $document->saveHTML($child);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return trim($result);
    }

    public static function plainText($html): string
    {
        $html = (string) $html;
        $html = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])\b[^>]*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim($text);
    }
}