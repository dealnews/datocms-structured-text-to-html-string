<?php
namespace DealNews\StructuredText;

/**
 * Main renderer class for DatoCMS Structured Text
 *
 * Converts DatoCMS Structured Text (DAST format) to HTML strings.
 * Provides the main entry point for rendering operations.
 *
 * Usage:
 * ```php
 * use DealNews\StructuredText\Renderer;
 *
 * $renderer = new Renderer();
 * $html = $renderer->render($structured_text);
 *
 * // With custom settings
 * $settings = new RenderSettings();
 * $settings->render_block = function($context) { ... };
 * $html = $renderer->render($structured_text, $settings);
 * ```
 */
class Renderer {

    /**
     * Rule builder for creating custom rendering rules
     *
     * @var RenderRule
     */
    protected RenderRule $rule_builder;

    /**
     * Constructor
     *
     * @param RenderRule|null $rule_builder Optional rule builder instance
     */
    public function __construct(?RenderRule $rule_builder = null) {
        $this->rule_builder = $rule_builder ?? new RenderRule();
    }

    /**
     * Renders DatoCMS Structured Text to HTML string
     *
     * Main entry point for converting structured text documents to HTML.
     * Supports custom rendering for blocks, inline items, links, and custom
     * node/mark rules.
     *
     * @param mixed               $structured_text_or_node Structured text
     *                                                      from DatoCMS or a
     *                                                      node
     * @param RenderSettings|null $settings                Rendering options
     *
     * @return string|null Rendered HTML or null if input is null
     *
     * @throws RenderError If rendering fails due to missing configuration
     */
    public function render(
        $structured_text_or_node,
        ?RenderSettings $settings = null
    ): ?string {
        if ($structured_text_or_node === null) {
            return null;
        }

        if ($settings === null) {
            $settings = new RenderSettings();
        }

        $default_adapter = new DefaultAdapter();

        $adapter = new class(
            $settings->render_text ?? [$default_adapter, 'renderText'],
            $settings->render_node ?? [$default_adapter, 'renderNode'],
            $settings->render_fragment ?? [
                $default_adapter,
                'renderFragment',
            ]
        ) implements Adapter {
            protected $render_text_fn;
            protected $render_node_fn;
            protected $render_fragment_fn;

            public function __construct(
                callable $render_text,
                callable $render_node,
                callable $render_fragment
            ) {
                $this->render_text_fn = $render_text;
                $this->render_node_fn = $render_node;
                $this->render_fragment_fn = $render_fragment;
            }

            public function renderNode(
                ?string $tag_name,
                ?array $attrs = null,
                ...$children
            ): ?string {
                return ($this->render_node_fn)(
                    $tag_name,
                    $attrs,
                    ...$children
                );
            }

            public function renderFragment(array $children): ?string {
                return ($this->render_fragment_fn)($children);
            }

            public function renderText(string $text): ?string {
                return ($this->render_text_fn)($text);
            }
        };

        $render_inline_record = $settings->render_inline_record;
        $render_link_to_record = $settings->render_link_to_record;
        $render_block = $settings->render_block;
        $render_inline_block = $settings->render_inline_block;

        $custom_rules = $settings->custom_node_rules ?? [];

        $inline_item_rule = $this->rule_builder->forNode(
            function ($node) {
                return Utils::isInlineItem($node);
            },
            function ($context) use (
                $structured_text_or_node,
                $render_inline_record
            ) {
                $node = $context['node'];

                if ($render_inline_record === null) {
                    throw new RenderError(
                        "The Structured Text document contains an ".
                        "'inlineItem' node, but no 'render_inline_record' ".
                        "option is specified!",
                        $node
                    );
                }

                if (!Utils::isStructuredText($structured_text_or_node)) {
                    throw new RenderError(
                        "The document contains an 'inlineItem' node, but ".
                        "the passed value is not a Structured Text GraphQL ".
                        "response, or .links is not present!",
                        $node
                    );
                }

                $st_array = (array) $structured_text_or_node;
                if (!isset($st_array['links'])) {
                    throw new RenderError(
                        "The document contains an 'inlineItem' node, but ".
                        "the passed value is not a Structured Text GraphQL ".
                        "response, or .links is not present!",
                        $node
                    );
                }

                $item_id = $node['item'] ?? null;
                $item = null;
                foreach ($st_array['links'] as $link) {
                    $link_array = (array) $link;
                    if (isset($link_array['id']) &&
                        $link_array['id'] === $item_id)
                    {
                        $item = $link;
                        break;
                    }
                }

                if ($item === null) {
                    throw new RenderError(
                        "The Structured Text document contains an ".
                        "'inlineItem' node, but cannot find a record with ".
                        "ID {$item_id} inside .links!",
                        $node
                    );
                }

                return $render_inline_record([
                    'record'  => $item,
                    'adapter' => $context['adapter'],
                ]);
            }
        );

        $item_link_rule = $this->rule_builder->forNode(
            function ($node) {
                return Utils::isItemLink($node);
            },
            function ($context) use (
                $structured_text_or_node,
                $render_link_to_record,
                $settings
            ) {
                $node = $context['node'];

                if ($render_link_to_record === null) {
                    throw new RenderError(
                        "The Structured Text document contains an ".
                        "'itemLink' node, but no 'render_link_to_record' ".
                        "option is specified!",
                        $node
                    );
                }

                if (!Utils::isStructuredText($structured_text_or_node)) {
                    throw new RenderError(
                        "The document contains an 'itemLink' node, but ".
                        "the passed value is not a Structured Text ".
                        "GraphQL response, or .links is not present!",
                        $node
                    );
                }

                $st_array = (array) $structured_text_or_node;
                if (!isset($st_array['links'])) {
                    throw new RenderError(
                        "The document contains an 'itemLink' node, but ".
                        "the passed value is not a Structured Text ".
                        "GraphQL response, or .links is not present!",
                        $node
                    );
                }

                $item_id = $node['item'] ?? null;
                $item = null;
                foreach ($st_array['links'] as $link) {
                    $link_array = (array) $link;
                    if (isset($link_array['id']) &&
                        $link_array['id'] === $item_id)
                    {
                        $item = $link;
                        break;
                    }
                }

                if ($item === null) {
                    throw new RenderError(
                        "The Structured Text document contains an ".
                        "'itemLink' node, but cannot find a record with ".
                        "ID {$item_id} inside .links!",
                        $node
                    );
                }

                $transformed_meta = null;
                if (isset($node['meta'])) {
                    $meta_transformer = $settings->meta_transformer ??
                                        [$this->rule_builder, 'defaultMetaTransformer'];
                    $transformed_meta = $meta_transformer([
                        'node' => $node,
                        'meta' => $node['meta'],
                    ]);
                }

                return $render_link_to_record([
                    'record'           => $item,
                    'adapter'          => $context['adapter'],
                    'children'         => $context['children'],
                    'transformed_meta' => $transformed_meta,
                ]);
            }
        );

        $block_rule = $this->rule_builder->forNode(
            function ($node) {
                return Utils::isBlock($node);
            },
            function ($context) use (
                $structured_text_or_node,
                $render_block
            ) {
                $node = $context['node'];

                if ($render_block === null) {
                    throw new RenderError(
                        "The Structured Text document contains a 'block' ".
                        "node, but no 'render_block' option is specified!",
                        $node
                    );
                }

                if (!Utils::isStructuredText($structured_text_or_node)) {
                    throw new RenderError(
                        "The document contains a 'block' node, but the ".
                        "passed value is not a Structured Text GraphQL ".
                        "response, or .blocks is not present!",
                        $node
                    );
                }

                $st_array = (array) $structured_text_or_node;
                if (!isset($st_array['blocks'])) {
                    throw new RenderError(
                        "The document contains a 'block' node, but the ".
                        "passed value is not a Structured Text GraphQL ".
                        "response, or .blocks is not present!",
                        $node
                    );
                }

                $item_id = $node['item'] ?? null;
                $item = null;
                foreach ($st_array['blocks'] as $block) {
                    $block_array = (array) $block;
                    if (isset($block_array['id']) &&
                        $block_array['id'] === $item_id)
                    {
                        $item = $block;
                        break;
                    }
                }

                if ($item === null) {
                    throw new RenderError(
                        "The Structured Text document contains a 'block' ".
                        "node, but cannot find a record with ID ".
                        "{$item_id} inside .blocks!",
                        $node
                    );
                }

                return $render_block([
                    'record'  => $item,
                    'adapter' => $context['adapter'],
                ]);
            }
        );

        $inline_block_rule = $this->rule_builder->forNode(
            function ($node) {
                return Utils::isInlineBlock($node);
            },
            function ($context) use (
                $structured_text_or_node,
                $render_inline_block
            ) {
                $node = $context['node'];

                if ($render_inline_block === null) {
                    throw new RenderError(
                        "The Structured Text document contains an ".
                        "'inlineBlock' node, but no ".
                        "'render_inline_block' option is specified!",
                        $node
                    );
                }

                if (!Utils::isStructuredText($structured_text_or_node)) {
                    throw new RenderError(
                        "The document contains an 'inlineBlock' node, ".
                        "but the passed value is not a Structured Text ".
                        "GraphQL response, or .inlineBlocks is not ".
                        "present!",
                        $node
                    );
                }

                $st_array = (array) $structured_text_or_node;
                if (!isset($st_array['inlineBlocks'])) {
                    throw new RenderError(
                        "The document contains an 'inlineBlock' node, ".
                        "but the passed value is not a Structured Text ".
                        "GraphQL response, or .inlineBlocks is not ".
                        "present!",
                        $node
                    );
                }

                $item_id = $node['item'] ?? null;
                $item = null;
                foreach ($st_array['inlineBlocks'] as $inline_block) {
                    $ib_array = (array) $inline_block;
                    if (isset($ib_array['id']) &&
                        $ib_array['id'] === $item_id)
                    {
                        $item = $inline_block;
                        break;
                    }
                }

                if ($item === null) {
                    throw new RenderError(
                        "The Structured Text document contains an ".
                        "'inlineBlock' node, but cannot find a record ".
                        "with ID {$item_id} inside .inlineBlocks!",
                        $node
                    );
                }

                return $render_inline_block([
                    'record'  => $item,
                    'adapter' => $context['adapter'],
                ]);
            }
        );

        $all_custom_rules = array_merge(
            $custom_rules,
            [
                $inline_item_rule,
                $item_link_rule,
                $block_rule,
                $inline_block_rule,
            ]
        );

        $renderer = new GenericHtmlRenderer(
            $adapter,
            $all_custom_rules,
            $settings->custom_mark_rules ?? [],
            $settings->meta_transformer,
            $this->rule_builder
        );

        return $renderer->render($structured_text_or_node);
    }
}
