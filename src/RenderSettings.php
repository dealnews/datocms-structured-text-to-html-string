<?php
namespace DealNews\StructuredText;

/**
 * Settings for customizing structured text rendering
 *
 * Configure how the renderer handles different node types, text
 * transformation, and custom rendering rules.
 *
 * Usage:
 * ```php
 * $settings = new RenderSettings();
 * $settings->render_block = function($context) {
 *     return '<div>' . $context['record']->content . '</div>';
 * };
 * $html = render($structured_text, $settings);
 * ```
 */
class RenderSettings {

    /**
     * Custom rules for rendering specific node types
     *
     * @var callable[]|null
     */
    public ?array $custom_node_rules = null;

    /**
     * Custom rules for rendering text marks (bold, italic, etc.)
     *
     * @var callable[]|null
     */
    public ?array $custom_mark_rules = null;

    /**
     * Function to transform link meta into HTML attributes
     *
     * @var callable|null
     */
    public $meta_transformer = null;

    /**
     * Function to render inline item nodes
     *
     * Receives: ['record' => object, 'adapter' => Adapter]
     *
     * @var callable|null
     */
    public $render_inline_record = null;

    /**
     * Function to render item link nodes
     *
     * Receives: ['record' => object, 'adapter' => Adapter, 'children' =>
     * string, 'transformed_meta' => array]
     *
     * @var callable|null
     */
    public $render_link_to_record = null;

    /**
     * Function to render block nodes
     *
     * Receives: ['record' => object, 'adapter' => Adapter]
     *
     * @var callable|null
     */
    public $render_block = null;

    /**
     * Function to render inline block nodes
     *
     * Receives: ['record' => object, 'adapter' => Adapter]
     *
     * @var callable|null
     */
    public $render_inline_block = null;

    /**
     * Function to transform text content
     *
     * @var callable|null
     */
    public $render_text = null;

    /**
     * Function to render HTML nodes
     *
     * @var callable|null
     */
    public $render_node = null;

    /**
     * Function to render HTML fragments
     *
     * @var callable|null
     */
    public $render_fragment = null;
}
