<?php
namespace DealNews\StructuredText\Tests;

use DealNews\StructuredText\RenderError;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the RenderError class
 */
class RenderErrorTest extends TestCase {

    /**
     * Test getNode returns the problematic node
     */
    public function testGetNode(): void {
        $node = [
            'type' => 'block',
            'id'   => '123',
        ];

        $error = new RenderError('Test error message', $node);

        $this->assertSame($node, $error->getNode());
    }

    /**
     * Test error message is preserved
     */
    public function testErrorMessage(): void {
        $node = ['type' => 'test'];
        $message = 'Custom error message';

        $error = new RenderError($message, $node);

        $this->assertEquals($message, $error->getMessage());
    }

    /**
     * Test getNode with complex node structure
     */
    public function testGetNodeWithComplexStructure(): void {
        $node = [
            'type'     => 'itemLink',
            'item'     => '456',
            'children' => [
                [
                    'type'  => 'span',
                    'value' => 'Link text',
                ],
            ],
            'meta' => [
                'target' => '_blank',
            ],
        ];

        $error = new RenderError('Missing record', $node);

        $retrieved_node = $error->getNode();
        $this->assertSame($node, $retrieved_node);
        $this->assertEquals('itemLink', $retrieved_node['type']);
        $this->assertEquals('456', $retrieved_node['item']);
        $this->assertIsArray($retrieved_node['children']);
        $this->assertIsArray($retrieved_node['meta']);
    }

    /**
     * Test error can be caught and node extracted
     */
    public function testErrorCanBeCaughtAndNodeExtracted(): void {
        $node = ['type' => 'block', 'id' => 'abc'];

        $caught_node = null;
        $caught_message = null;

        try {
            throw new RenderError('Test throw', $node);
        } catch (RenderError $e) {
            $caught_node = $e->getNode();
            $caught_message = $e->getMessage();
        }

        $this->assertSame($node, $caught_node);
        $this->assertEquals('Test throw', $caught_message);
    }

    /**
     * Test RenderError is instance of RuntimeException
     */
    public function testIsRuntimeException(): void {
        $error = new RenderError('test', []);

        $this->assertInstanceOf(\RuntimeException::class, $error);
    }

    /**
     * Test RenderError is throwable
     */
    public function testIsThrowable(): void {
        $error = new RenderError('test', []);

        $this->assertInstanceOf(\Throwable::class, $error);
    }

    /**
     * Test getNode with empty node array
     */
    public function testGetNodeWithEmptyArray(): void {
        $node = [];
        $error = new RenderError('Empty node', $node);

        $this->assertSame($node, $error->getNode());
        $this->assertIsArray($error->getNode());
        $this->assertEmpty($error->getNode());
    }
}
