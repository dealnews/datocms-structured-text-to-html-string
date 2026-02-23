<?php
namespace DealNews\StructuredText\Tests;

use DealNews\StructuredText\RenderRule;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the RenderRule class
 */
class RenderRuleTest extends TestCase {

    protected RenderRule $rule_builder;

    protected function setUp(): void {
        $this->rule_builder = new RenderRule();
    }

    /**
     * Test forNode creates valid rule structure
     */
    public function testForNodeCreatesValidRule(): void {
        $predicate = function ($node) {
            return ($node['type'] ?? '') === 'heading';
        };
        
        $renderer = function ($context) {
            return '<h1>test</h1>';
        };

        $rule = $this->rule_builder->forNode($predicate, $renderer);

        $this->assertIsArray($rule);
        $this->assertArrayHasKey('predicate', $rule);
        $this->assertArrayHasKey('renderer', $rule);
        $this->assertSame($predicate, $rule['predicate']);
        $this->assertSame($renderer, $rule['renderer']);
    }

    /**
     * Test forNode predicate is callable
     */
    public function testForNodePredicateIsCallable(): void {
        $rule = $this->rule_builder->forNode(
            fn($node) => true,
            fn($context) => ''
        );

        $this->assertIsCallable($rule['predicate']);
        $this->assertTrue($rule['predicate'](['type' => 'test']));
    }

    /**
     * Test forNode renderer is callable
     */
    public function testForNodeRendererIsCallable(): void {
        $rule = $this->rule_builder->forNode(
            fn($node) => true,
            fn($context) => '<test />'
        );

        $this->assertIsCallable($rule['renderer']);
        $this->assertEquals(
            '<test />',
            $rule['renderer'](['node' => [], 'children' => '', 'key' => '0'])
        );
    }

    /**
     * Test forMark creates valid rule structure
     */
    public function testForMarkCreatesValidRule(): void {
        $renderer = function ($context) {
            return '<custom>test</custom>';
        };

        $rule = $this->rule_builder->forMark('custom', $renderer);

        $this->assertIsArray($rule);
        $this->assertArrayHasKey('mark', $rule);
        $this->assertArrayHasKey('renderer', $rule);
        $this->assertEquals('custom', $rule['mark']);
        $this->assertSame($renderer, $rule['renderer']);
    }

    /**
     * Test forMark renderer is callable
     */
    public function testForMarkRendererIsCallable(): void {
        $rule = $this->rule_builder->forMark(
            'highlight',
            fn($context) => '<mark>' . $context['children'] . '</mark>'
        );

        $this->assertIsCallable($rule['renderer']);
        $this->assertEquals(
            '<mark>text</mark>',
            $rule['renderer'](['children' => 'text', 'key' => '0'])
        );
    }

    /**
     * Test defaultMetaTransformer returns meta as-is
     */
    public function testDefaultMetaTransformer(): void {
        $context = [
            'node' => ['type' => 'link'],
            'meta' => ['target' => '_blank', 'rel' => 'noopener'],
        ];

        $result = $this->rule_builder->defaultMetaTransformer($context);

        $this->assertEquals(['target' => '_blank', 'rel' => 'noopener'], $result);
    }

    /**
     * Test defaultMetaTransformer with no meta returns null
     */
    public function testDefaultMetaTransformerWithNoMeta(): void {
        $context = [
            'node' => ['type' => 'link'],
        ];

        $result = $this->rule_builder->defaultMetaTransformer($context);

        $this->assertNull($result);
    }

    /**
     * Test defaultMetaTransformer with null meta
     */
    public function testDefaultMetaTransformerWithNullMeta(): void {
        $context = [
            'node' => ['type' => 'link'],
            'meta' => null,
        ];

        $result = $this->rule_builder->defaultMetaTransformer($context);

        $this->assertNull($result);
    }

    /**
     * Test forNode with complex predicate
     */
    public function testForNodeWithComplexPredicate(): void {
        $rule = $this->rule_builder->forNode(
            function ($node) {
                return isset($node['type']) &&
                       $node['type'] === 'heading' &&
                       isset($node['level']) &&
                       $node['level'] > 2;
            },
            fn($context) => '<h3>Complex</h3>'
        );

        $predicate = $rule['predicate'];
        
        $this->assertTrue($predicate(['type' => 'heading', 'level' => 3]));
        $this->assertTrue($predicate(['type' => 'heading', 'level' => 4]));
        $this->assertFalse($predicate(['type' => 'heading', 'level' => 1]));
        $this->assertFalse($predicate(['type' => 'heading', 'level' => 2]));
        $this->assertFalse($predicate(['type' => 'paragraph']));
    }
}
