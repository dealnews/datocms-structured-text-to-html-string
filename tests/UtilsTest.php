<?php
namespace DealNews\StructuredText\Tests;

use DealNews\StructuredText\Utils;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Utils class
 */
class UtilsTest extends TestCase {

    /**
     * Test isStructuredText identifies valid structured text
     */
    public function testIsStructuredTextWithValidData(): void {
        // isStructuredText checks for 'value' key (GraphQL response wrapper)
        $valid = [
            'value' => [
                'schema'   => 'dast',
                'document' => ['type' => 'root', 'children' => []],
            ],
        ];
        
        $this->assertTrue(Utils::isStructuredText($valid));
    }

    /**
     * Test isStructuredText rejects invalid data
     */
    public function testIsStructuredTextWithInvalidData(): void {
        $this->assertFalse(Utils::isStructuredText(null));
        $this->assertFalse(Utils::isStructuredText([]));
        $this->assertFalse(Utils::isStructuredText(['schema' => 'dast']));
        $this->assertFalse(Utils::isStructuredText(['document' => []]));
    }

    /**
     * Test isBlock identifies block nodes
     */
    public function testIsBlock(): void {
        $block = ['type' => 'block'];
        $not_block = ['type' => 'paragraph'];
        
        $this->assertTrue(Utils::isBlock($block));
        $this->assertFalse(Utils::isBlock($not_block));
        $this->assertFalse(Utils::isBlock([]));
    }

    /**
     * Test isInlineBlock identifies inline block nodes
     */
    public function testIsInlineBlock(): void {
        $inline_block = ['type' => 'inlineBlock'];
        $not_inline_block = ['type' => 'block'];
        
        $this->assertTrue(Utils::isInlineBlock($inline_block));
        $this->assertFalse(Utils::isInlineBlock($not_inline_block));
        $this->assertFalse(Utils::isInlineBlock([]));
    }

    /**
     * Test isInlineItem identifies inline item nodes
     */
    public function testIsInlineItem(): void {
        $inline_item = ['type' => 'inlineItem'];
        $not_inline_item = ['type' => 'span'];
        
        $this->assertTrue(Utils::isInlineItem($inline_item));
        $this->assertFalse(Utils::isInlineItem($not_inline_item));
        $this->assertFalse(Utils::isInlineItem([]));
    }

    /**
     * Test isItemLink identifies item link nodes
     */
    public function testIsItemLink(): void {
        $item_link = ['type' => 'itemLink'];
        $regular_link = ['type' => 'link'];
        
        $this->assertTrue(Utils::isItemLink($item_link));
        $this->assertFalse(Utils::isItemLink($regular_link));
        $this->assertFalse(Utils::isItemLink([]));
    }

    /**
     * Test isHeading identifies heading nodes
     */
    public function testIsHeading(): void {
        $heading = ['type' => 'heading', 'level' => 1];
        $not_heading = ['type' => 'paragraph'];
        
        $this->assertTrue(Utils::isHeading($heading));
        $this->assertFalse(Utils::isHeading($not_heading));
        $this->assertFalse(Utils::isHeading([]));
    }

    /**
     * Test isParagraph identifies paragraph nodes
     */
    public function testIsParagraph(): void {
        $paragraph = ['type' => 'paragraph'];
        $not_paragraph = ['type' => 'heading'];
        
        $this->assertTrue(Utils::isParagraph($paragraph));
        $this->assertFalse(Utils::isParagraph($not_paragraph));
        $this->assertFalse(Utils::isParagraph([]));
    }

    /**
     * Test isSpan identifies span nodes
     */
    public function testIsSpan(): void {
        $span = ['type' => 'span', 'value' => 'text'];
        $not_span = ['type' => 'link'];
        
        $this->assertTrue(Utils::isSpan($span));
        $this->assertFalse(Utils::isSpan($not_span));
        $this->assertFalse(Utils::isSpan([]));
    }

    /**
     * Test isLink identifies link nodes
     */
    public function testIsLink(): void {
        $link = ['type' => 'link', 'url' => 'https://example.com'];
        $not_link = ['type' => 'itemLink'];
        
        $this->assertTrue(Utils::isLink($link));
        $this->assertFalse(Utils::isLink($not_link));
        $this->assertFalse(Utils::isLink([]));
    }

    /**
     * Test all type guards return false for empty arrays
     */
    public function testAllTypeGuardsReturnFalseForEmptyArray(): void {
        $empty = [];
        
        $this->assertFalse(Utils::isBlock($empty));
        $this->assertFalse(Utils::isInlineBlock($empty));
        $this->assertFalse(Utils::isInlineItem($empty));
        $this->assertFalse(Utils::isItemLink($empty));
        $this->assertFalse(Utils::isHeading($empty));
        $this->assertFalse(Utils::isParagraph($empty));
        $this->assertFalse(Utils::isSpan($empty));
        $this->assertFalse(Utils::isLink($empty));
    }

    /**
     * Test type guards with missing type key
     */
    public function testTypeGuardsWithMissingTypeKey(): void {
        $no_type = ['value' => 'test'];
        
        $this->assertFalse(Utils::isBlock($no_type));
        $this->assertFalse(Utils::isHeading($no_type));
        $this->assertFalse(Utils::isParagraph($no_type));
        $this->assertFalse(Utils::isSpan($no_type));
        $this->assertFalse(Utils::isLink($no_type));
    }
}
