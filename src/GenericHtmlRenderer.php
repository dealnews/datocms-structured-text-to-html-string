<?php
namespace DealNews\StructuredText;

/**
 * Generic HTML renderer for structured text documents
 *
 * Traverses the document tree and converts nodes to HTML using the provided
 * adapter and rules. Handles default rendering for standard DAST nodes and
 * applies custom rules when provided.
 */
class GenericHtmlRenderer {

    /**
     * Default mark to HTML tag mapping
     */
    protected const DEFAULT_MARKS = [
        'strong'    => 'strong',
        'code'      => 'code',
        'emphasis'  => 'em',
        'underline' => 'u',
        'strikethrough' => 's',
        'highlight' => 'mark',
    ];

    /**
     * The adapter instance for rendering
     *
     * @var Adapter
     */
    protected Adapter $adapter;

    /**
     * Custom node rendering rules
     *
     * @var array
     */
    protected array $custom_node_rules = [];

    /**
     * Custom mark rendering rules
     *
     * @var array
     */
    protected array $custom_mark_rules = [];

    /**
     * Meta transformer function
     *
     * @var callable
     */
    protected $meta_transformer;

    /**
     * Rule builder instance
     *
     * @var RenderRule
     */
    protected RenderRule $rule_builder;

    /**
     * Creates a new generic HTML renderer
     *
     * @param Adapter       $adapter          Adapter for rendering HTML
     * @param array         $custom_node_rules Custom node rules
     * @param array         $custom_mark_rules Custom mark rules
     * @param callable|null $meta_transformer Meta transformation function
     * @param RenderRule|null $rule_builder   Optional rule builder instance
     */
    public function __construct(
        Adapter $adapter,
        array $custom_node_rules = [],
        array $custom_mark_rules = [],
        ?callable $meta_transformer = null,
        ?RenderRule $rule_builder = null
    ) {
        $this->adapter = $adapter;
        $this->custom_node_rules = $custom_node_rules;
        $this->custom_mark_rules = $custom_mark_rules;
        $this->rule_builder = $rule_builder ?? new RenderRule();
        $this->meta_transformer = $meta_transformer ??
                                  [$this->rule_builder, 'defaultMetaTransformer'];
    }

    /**
     * Renders a document or node to HTML
     *
     * @param mixed $value The document or node to render
     * @param int   $key   Unique key for this node
     *
     * @return string|null Rendered HTML or null
     */
    public function render($value, int $key = 0): ?string {
        if ($value === null) {
            return null;
        }

        $value_array = (array) $value;

        if (isset($value_array['value']) &&
            !isset($value_array['type']))
        {
            return $this->render($value_array['value'], $key);
        }

        if (isset($value_array['document'])) {
            return $this->render($value_array['document'], $key);
        }

        if (!isset($value_array['type'])) {
            return null;
        }

        return $this->renderNode($value_array, $key);
    }

    /**
     * Renders a single node
     *
     * @param array $node Node data
     * @param int   $key  Unique key
     *
     * @return string|null Rendered HTML
     */
    protected function renderNode(array $node, int $key): ?string {
        foreach ($this->custom_node_rules as $rule) {
            $predicate = $rule['predicate'];
            if ($predicate($node)) {
                $context = [
                    'node'     => $node,
                    'children' => $this->renderChildren($node),
                    'key'      => (string) $key,
                    'adapter'  => $this->adapter,
                ];
                return $rule['renderer']($context);
            }
        }

        $node_type = $node['type'];

        switch ($node_type) {
            case 'root':
                return $this->renderChildren($node);

            case 'paragraph':
                return $this->adapter->renderNode(
                    'p',
                    ['key' => (string) $key],
                    $this->renderChildren($node)
                );

            case 'heading':
                $level = $node['level'] ?? 1;
                return $this->adapter->renderNode(
                    "h{$level}",
                    ['key' => (string) $key],
                    $this->renderChildren($node)
                );

            case 'list':
                $style = $node['style'] ?? 'bulleted';
                $tag = $style === 'numbered' ? 'ol' : 'ul';
                return $this->adapter->renderNode(
                    $tag,
                    ['key' => (string) $key],
                    $this->renderChildren($node)
                );

            case 'listItem':
                return $this->adapter->renderNode(
                    'li',
                    ['key' => (string) $key],
                    $this->renderChildren($node)
                );

            case 'blockquote':
                return $this->adapter->renderNode(
                    'blockquote',
                    ['key' => (string) $key],
                    $this->renderChildren($node)
                );

            case 'code':
                $code_value = $node['code'] ?? '';
                return $this->adapter->renderNode(
                    'pre',
                    ['key' => (string) $key],
                    $this->adapter->renderNode('code', null, $code_value)
                );

            case 'link':
                $url = $node['url'] ?? '#';
                $attrs = ['href' => $url, 'key' => (string) $key];
                if (isset($node['meta'])) {
                    $transformed = ($this->meta_transformer)([
                        'node' => $node,
                        'meta' => $node['meta'],
                    ]);
                    if ($transformed) {
                        $attrs = array_merge($attrs, $transformed);
                    }
                }
                return $this->adapter->renderNode(
                    'a',
                    $attrs,
                    $this->renderChildren($node)
                );

            case 'span':
                return $this->renderSpan($node);

            case 'thematicBreak':
                return $this->adapter->renderNode(
                    'hr',
                    ['key' => (string) $key]
                );

            default:
                return null;
        }
    }

    /**
     * Renders a span node with marks
     *
     * @param array $node Span node data
     *
     * @return string|null Rendered HTML
     */
    protected function renderSpan(array $node): ?string {
        $value = $node['value'] ?? '';

        $lines = explode("\n", $value);
        $children = [];
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $children[] = $this->adapter->renderNode('br', null);
            }
            if ($line !== '') {
                $children[] = $this->adapter->renderText($line);
            }
        }

        $content = $this->adapter->renderFragment($children);

        if (empty($node['marks'])) {
            return $content;
        }

        $result = $content;
        foreach ($node['marks'] as $mark_index => $mark) {
            $result = $this->renderMark($mark, $result, $mark_index);
        }

        return $result;
    }

    /**
     * Renders a text mark (bold, italic, etc.)
     *
     * @param string $mark  Mark name
     * @param string $content Content to wrap
     * @param int    $key   Unique key
     *
     * @return string|null Rendered HTML
     */
    protected function renderMark(
        string $mark,
        string $content,
        int $key
    ): ?string {
        foreach ($this->custom_mark_rules as $rule) {
            if ($rule['mark'] === $mark) {
                $context = [
                    'children' => $content,
                    'key'      => (string) $key,
                    'adapter'  => $this->adapter,
                ];
                return $rule['renderer']($context);
            }
        }

        if (isset(self::DEFAULT_MARKS[$mark])) {
            $tag = self::DEFAULT_MARKS[$mark];
            return $this->adapter->renderNode(
                $tag,
                ['key' => (string) $key],
                $content
            );
        }

        return $content;
    }

    /**
     * Renders children of a node
     *
     * @param array $node Parent node
     *
     * @return string|null Rendered children HTML
     */
    protected function renderChildren(array $node): ?string {
        $children_key = $this->getChildrenKey($node);

        if (!isset($node[$children_key]) ||
            !is_array($node[$children_key]))
        {
            return null;
        }

        $rendered = [];
        foreach ($node[$children_key] as $index => $child) {
            $child_html = $this->render($child, $index);
            if ($child_html !== null) {
                $rendered[] = $child_html;
            }
        }

        $result = null;
        if (!empty($rendered)) {
            $result = $this->adapter->renderFragment($rendered);
        }

        return $result;
    }

    /**
     * Gets the appropriate children key for a node type
     *
     * Different node types use 'children' or 'content' for child nodes
     *
     * @param array $node Node to check
     *
     * @return string Key name ('children' or 'content')
     */
    protected function getChildrenKey(array $node): string {
        if (isset($node['children'])) {
            return 'children';
        }

        if (isset($node['content'])) {
            return 'content';
        }

        return 'children';
    }
}
