<?php
namespace DealNews\StructuredText;

/**
 * Default HTML adapter implementation
 *
 * Provides simple HTML string generation similar to the vhtml library.
 * Converts tag names, attributes, and children into HTML strings.
 *
 * Usage:
 * ```php
 * $adapter = new DefaultAdapter();
 * $html = $adapter->renderNode('div', ['class' => 'wrapper'], 'Hello');
 * // Returns: <div class="wrapper">Hello</div>
 * ```
 */
class DefaultAdapter implements Adapter {

    /**
     * Renders an HTML node/element
     *
     * @param string|null       $tag_name Tag name or null for fragment
     * @param array|null        $attrs    HTML attributes
     * @param string|array|null ...$children Child content
     *
     * @return string|null The rendered HTML string
     */
    public function renderNode(
        ?string $tag_name,
        ?array $attrs = null,
        ...$children
    ): ?string {
        if ($attrs !== null && isset($attrs['key'])) {
            unset($attrs['key']);
        }

        return $this->vhtml($tag_name, $attrs, ...$children);
    }

    /**
     * Renders a fragment (children without wrapper tag)
     *
     * @param array $children Array of child strings
     *
     * @return string|null The concatenated HTML
     */
    public function renderFragment(array $children): ?string {
        return $this->vhtml(null, null, $children);
    }

    /**
     * Renders plain text (no escaping by default)
     *
     * @param string $text The text to render
     *
     * @return string The text as-is
     */
    public function renderText(string $text): string {
        return $text;
    }

    /**
     * Core HTML rendering logic (vhtml equivalent)
     *
     * @param string|null $tag_name Tag name or null
     * @param array|null  $attrs    Attributes array
     * @param mixed       ...$children Child content
     *
     * @return string|null Rendered HTML string
     */
    protected function vhtml(
        ?string $tag_name,
        ?array $attrs = null,
        ...$children
    ): ?string {
        $children_html = $this->flattenChildren($children);

        if ($tag_name === null) {
            return $children_html;
        }

        $attrs_string = $this->buildAttributesString($attrs);

        if ($children_html === '') {
            return "<{$tag_name}{$attrs_string} />";
        }

        return "<{$tag_name}{$attrs_string}>{$children_html}</{$tag_name}>";
    }

    /**
     * Flattens and concatenates child content
     *
     * @param array $children Children to flatten
     *
     * @return string Concatenated HTML string
     */
    protected function flattenChildren(array $children): string {
        $result = '';

        foreach ($children as $child) {
            if (is_array($child)) {
                $result .= $this->flattenChildren($child);
            } elseif ($child !== null && $child !== false) {
                $result .= (string) $child;
            }
        }

        return $result;
    }

    /**
     * Builds HTML attributes string
     *
     * @param array|null $attrs Attributes array
     *
     * @return string Formatted attributes string (with leading space)
     */
    protected function buildAttributesString(?array $attrs): string {
        if ($attrs === null || empty($attrs)) {
            return '';
        }

        $parts = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $parts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            } else {
                $escaped_value = htmlspecialchars(
                    (string) $value,
                    ENT_QUOTES,
                    'UTF-8'
                );
                $parts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8').
                           '="'.$escaped_value.'"';
            }
        }

        $result = '';
        if (!empty($parts)) {
            $result = ' ' . implode(' ', $parts);
        }

        return $result;
    }
}
