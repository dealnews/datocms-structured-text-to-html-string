<?php
namespace DealNews\StructuredText;

/**
 * Adapter interface for rendering HTML
 *
 * Defines the contract for how structured text nodes are converted to HTML
 * strings. Implementations provide the actual rendering logic.
 */
interface Adapter {

    /**
     * Renders an HTML node/element
     *
     * @param string|null        $tag_name Tag name (e.g., 'div', 'p') or null
     *                                     for fragment
     * @param array|null         $attrs    HTML attributes as key-value pairs
     * @param string|array|null  ...$children Child content (strings or arrays)
     *
     * @return string|null The rendered HTML string
     */
    public function renderNode(
        ?string $tag_name,
        ?array $attrs = null,
        ...$children
    ): ?string;

    /**
     * Renders a fragment (collection of children without wrapper)
     *
     * @param array $children Array of rendered child strings
     *
     * @return string|null The concatenated HTML string
     */
    public function renderFragment(array $children): ?string;

    /**
     * Renders plain text content
     *
     * @param string $text The text to render
     *
     * @return string|null The rendered text (may be escaped/transformed)
     */
    public function renderText(string $text): ?string;
}
