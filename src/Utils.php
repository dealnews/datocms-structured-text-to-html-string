<?php
namespace DealNews\StructuredText;

/**
 * Utility functions for working with structured text nodes
 *
 * Provides type guards and helper functions for identifying different node
 * types in the DatoCMS Structured Text document tree.
 */
class Utils {

    /**
     * Checks if value is a structured text GraphQL response
     *
     * @param mixed $value Value to check
     *
     * @return bool True if value is a structured text response
     */
    public static function isStructuredText($value): bool {
        if (!is_array($value) && !is_object($value)) {
            return false;
        }

        $value_array = (array) $value;

        return isset($value_array['value']);
    }

    /**
     * Checks if node is a block node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is a block
     */
    public static function isBlock($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) && $node_array['type'] === 'block';
    }

    /**
     * Checks if node is an inline block node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is an inline block
     */
    public static function isInlineBlock($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) &&
               $node_array['type'] === 'inlineBlock';
    }

    /**
     * Checks if node is an inline item node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is an inline item
     */
    public static function isInlineItem($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) &&
               $node_array['type'] === 'inlineItem';
    }

    /**
     * Checks if node is an item link node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is an item link
     */
    public static function isItemLink($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) &&
               $node_array['type'] === 'itemLink';
    }

    /**
     * Checks if node is a heading node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is a heading
     */
    public static function isHeading($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) &&
               $node_array['type'] === 'heading';
    }

    /**
     * Checks if node is a paragraph node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is a paragraph
     */
    public static function isParagraph($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) &&
               $node_array['type'] === 'paragraph';
    }

    /**
     * Checks if node is a span node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is a span
     */
    public static function isSpan($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) &&
               $node_array['type'] === 'span';
    }

    /**
     * Checks if node is a link node
     *
     * @param mixed $node Node to check
     *
     * @return bool True if node is a link
     */
    public static function isLink($node): bool {
        if (!is_array($node) && !is_object($node)) {
            return false;
        }

        $node_array = (array) $node;

        return isset($node_array['type']) &&
               $node_array['type'] === 'link';
    }
}
