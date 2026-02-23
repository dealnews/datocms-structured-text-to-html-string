<?php
namespace DealNews\StructuredText;

/**
 * Builder for custom rendering rules
 *
 * Provides factory methods for creating node and mark rendering rules.
 * These rules customize how specific nodes or text marks are rendered.
 *
 * Usage:
 * ```php
 * use DealNews\StructuredText\RenderRule;
 * use DealNews\StructuredText\Utils;
 *
 * $rule_builder = new RenderRule();
 * $rule = $rule_builder->forNode(
 *     function($node) { return Utils::isHeading($node); },
 *     function($context) {
 *         $level = $context['node']['level'] + 1;
 *         return $context['adapter']->renderNode(
 *             "h{$level}",
 *             ['key' => $context['key']],
 *             $context['children']
 *         );
 *     }
 * );
 * ```
 */
class RenderRule {

    /**
     * Creates a custom node rendering rule
     *
     * Wraps a predicate and renderer function into a rule that can be
     * applied during document traversal.
     *
     * @param callable $predicate Function that returns true if rule applies
     *                            to node
     * @param callable $renderer  Function that renders the node, receives
     *                            context array with keys: node, children,
     *                            key, adapter
     *
     * @return array Rule array with 'predicate' and 'renderer' keys
     */
    public function forNode(
        callable $predicate,
        callable $renderer
    ): array {
        return [
            'predicate' => $predicate,
            'renderer'  => $renderer,
        ];
    }

    /**
     * Creates a custom mark rendering rule
     *
     * Wraps a mark name and renderer function into a rule for rendering
     * text marks (bold, italic, etc.).
     *
     * @param string   $mark_name Name of the mark (e.g., 'strong',
     *                            'emphasis')
     * @param callable $renderer  Function that renders the mark, receives
     *                            context array with keys: children, key,
     *                            adapter
     *
     * @return array Rule array with 'mark' and 'renderer' keys
     */
    public function forMark(
        string $mark_name,
        callable $renderer
    ): array {
        return [
            'mark'     => $mark_name,
            'renderer' => $renderer,
        ];
    }

    /**
     * Default meta transformer for link attributes
     *
     * Converts DatoCMS meta array format to HTML attributes.
     * Meta format: [{"id": "attr_name", "value": "attr_value"}, ...]
     * Output: ["attr_name" => "attr_value", ...]
     *
     * @param array $context Context with 'node' and 'meta' keys
     *
     * @return array|null Transformed attributes or null
     */
    public function defaultMetaTransformer(array $context): ?array {
        $meta = $context['meta'] ?? null;
        
        if ($meta === null) {
            return null;
        }
        
        // If meta is already an associative array, return as-is
        if (!isset($meta[0])) {
            return $meta;
        }
        
        // Convert array of objects format to associative array
        $result = [];
        foreach ($meta as $item) {
            if (isset($item['id']) && isset($item['value'])) {
                $result[$item['id']] = $item['value'];
            }
        }
        
        return !empty($result) ? $result : null;
    }
}
