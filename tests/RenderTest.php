<?php
namespace DealNews\StructuredText\Tests;

use DealNews\StructuredText\RenderError;
use DealNews\StructuredText\Renderer;
use DealNews\StructuredText\RenderRule;
use DealNews\StructuredText\RenderSettings;
use DealNews\StructuredText\Utils;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the structured text to HTML renderer
 */
class RenderTest extends TestCase {

    /**
     * Renderer instance for testing
     *
     * @var Renderer
     */
    protected Renderer $renderer;

    /**
     * RenderRule instance for building custom rules
     *
     * @var RenderRule
     */
    protected RenderRule $rule_builder;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void {
        $this->renderer = new Renderer();
        $this->rule_builder = new RenderRule();
    }

    /**
     * Test rendering null value returns null
     */
    public function testRenderNullValue(): void {
        $this->assertNull($this->renderer->render(null));
    }

    /**
     * Test simple DAST with heading and newlines
     */
    public function testSimpleDastWithHeading(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'heading',
                        'level'    => 1,
                        'children' => [
                            [
                                'type'  => 'span',
                                'value' => "This\nis a\ntitle!",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<h1>This<br />is a<br />title!</h1>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test DAST with link inside paragraph
     */
    public function testDastWithLinkInParagraph(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            [
                                'url'      => 'https://www.datocms.com/',
                                'type'     => 'link',
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'https://www.datocms.com/',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<p><a href="https://www.datocms.com/">'.
                    'https://www.datocms.com/</a></p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test simple DAST with value wrapper
     */
    public function testSimpleDastWithValueWrapper(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'heading',
                            'level'    => 1,
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => "This\nis a\ntitle!",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<h1>This<br />is a<br />title!</h1>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test custom text transformation
     */
    public function testCustomTextTransformation(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'heading',
                            'level'    => 1,
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => "This\nis a\ntitle!",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_text = function (string $text): string {
            return str_replace('This', 'That', $text);
        };

        $expected = '<h1>That<br />is a<br />title!</h1>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test custom node rule for heading level change
     */
    public function testCustomNodeRule(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'heading',
                            'level'    => 1,
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => "This\nis a\ntitle!",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_text = function (string $text): string {
            return str_replace('This', 'That', $text);
        };
        $settings->custom_node_rules = [
            $this->rule_builder->forNode(
                function ($node) {
                    return Utils::isHeading($node);
                },
                function ($context) {
                    $node = $context['node'];
                    $level = $node['level'] + 1;
                    return $context['adapter']->renderNode(
                        "h{$level}",
                        ['key' => $context['key']],
                        $context['children']
                    );
                }
            ),
        ];

        $expected = '<h2>That<br />is a<br />title!</h2>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test rendering with blocks, inline blocks, inline items, and item
     * links
     */
    public function testComplexStructuredTextWithBlocksAndLinks(): void {
        $structured_text = [
            'value'        => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'heading',
                            'level'    => 1,
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => 'This is a',
                                ],
                                [
                                    'type'  => 'span',
                                    'marks' => ['highlight'],
                                    'value' => 'title',
                                ],
                                [
                                    'type' => 'inlineItem',
                                    'item' => '123',
                                ],
                                [
                                    'type'     => 'itemLink',
                                    'item'     => '123',
                                    'children' => [
                                        [
                                            'type'  => 'span',
                                            'value' => 'here!',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'inlineBlock',
                                    'item' => '789',
                                ],
                            ],
                        ],
                        [
                            'type' => 'block',
                            'item' => '456',
                        ],
                    ],
                ],
            ],
            'blocks'       => [
                (object) [
                    'id'         => '456',
                    '__typename' => 'QuoteRecord',
                    'quote'      => 'Foo bar.',
                    'author'     => 'Mark Smith',
                ],
            ],
            'inlineBlocks' => [
                (object) [
                    'id'         => '789',
                    '__typename' => 'MentionRecord',
                    'name'       => 'John Doe',
                ],
            ],
            'links'        => [
                (object) [
                    'id'         => '123',
                    '__typename' => 'DocPageRecord',
                    'title'      => 'How to code',
                    'slug'       => 'how-to-code',
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_record = function ($context) {
            $record = $context['record'];
            $adapter = $context['adapter'];
            if ($record->__typename === 'DocPageRecord') {
                return $adapter->renderNode(
                    'a',
                    ['href' => "/docs/{$record->slug}"],
                    $record->title
                );
            }
            return null;
        };

        $settings->render_link_to_record = function ($context) {
            $record = $context['record'];
            $adapter = $context['adapter'];
            $children = $context['children'];
            if ($record->__typename === 'DocPageRecord') {
                return $adapter->renderNode(
                    'a',
                    ['href' => "/docs/{$record->slug}"],
                    $children
                );
            }
            return null;
        };

        $settings->render_block = function ($context) {
            $record = $context['record'];
            $adapter = $context['adapter'];
            if ($record->__typename === 'QuoteRecord') {
                return $adapter->renderNode(
                    'figure',
                    null,
                    $adapter->renderNode('blockquote', null, $record->quote),
                    $adapter->renderNode('figcaption', null, $record->author)
                );
            }
            return null;
        };

        $settings->render_inline_block = function ($context) {
            $record = $context['record'];
            $adapter = $context['adapter'];
            if ($record->__typename === 'MentionRecord') {
                return $adapter->renderNode('em', null, $record->name);
            }
            return null;
        };

        $expected = '<h1>This is a<mark>title</mark>'.
                    '<a href="/docs/how-to-code">How to code</a>'.
                    '<a href="/docs/how-to-code">here!</a>'.
                    '<em>John Doe</em></h1>'.
                    '<figure><blockquote>Foo bar.</blockquote>'.
                    '<figcaption>Mark Smith</figcaption></figure>';

        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test error when render_inline_record is missing
     */
    public function testMissingRenderInlineRecordThrowsError(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineItem',
                                    'item' => '123',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'links' => [
                (object) ['id' => '123', 'title' => 'Test'],
            ],
        ];

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage(
            "The Structured Text document contains an 'inlineItem' node"
        );
        $this->renderer->render($structured_text);
    }

    /**
     * Test error when record is missing from links
     */
    public function testMissingRecordThrowsError(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineItem',
                                    'item' => '999',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'links' => [
                (object) ['id' => '123', 'title' => 'Test'],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_record = function ($context) {
            return 'test';
        };

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage(
            "cannot find a record with ID 999 inside .links"
        );
        $this->renderer->render($structured_text, $settings);
    }

    /**
     * Test skipping rendering of custom nodes by returning null
     */
    public function testSkipRenderingCustomNodes(): void {
        $structured_text = [
            'value'        => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'heading',
                            'level'    => 1,
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => 'This is a',
                                ],
                                [
                                    'type'  => 'span',
                                    'marks' => ['highlight'],
                                    'value' => 'title',
                                ],
                                [
                                    'type' => 'inlineItem',
                                    'item' => '123',
                                ],
                                [
                                    'type'     => 'itemLink',
                                    'item'     => '123',
                                    'children' => [
                                        [
                                            'type'  => 'span',
                                            'value' => 'here!',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'inlineBlock',
                                    'item' => '789',
                                ],
                            ],
                        ],
                        [
                            'type' => 'block',
                            'item' => '456',
                        ],
                    ],
                ],
            ],
            'blocks'       => [
                (object) ['id' => '456'],
            ],
            'inlineBlocks' => [
                (object) ['id' => '789'],
            ],
            'links'        => [
                (object) ['id' => '123'],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_record = function ($context) {
            return null;
        };
        $settings->render_link_to_record = function ($context) {
            return null;
        };
        $settings->render_block = function ($context) {
            return null;
        };
        $settings->render_inline_block = function ($context) {
            return null;
        };

        $expected = '<h1>This is a<mark>title</mark></h1>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test rendering blockquote node
     */
    public function testRenderBlockquote(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'blockquote',
                        'children' => [
                            [
                                'type'     => 'paragraph',
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'Quoted text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<blockquote><p>Quoted text</p></blockquote>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering code block node
     */
    public function testRenderCodeBlock(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type' => 'code',
                        'code' => 'console.log("hello");',
                    ],
                ],
            ],
        ];

        // Code content is not escaped - it's passed as-is to adapter
        $expected = '<pre><code>console.log("hello");</code></pre>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering thematic break (hr) node
     */
    public function testRenderThematicBreak(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            ['type' => 'span', 'value' => 'Before'],
                        ],
                    ],
                    [
                        'type' => 'thematicBreak',
                    ],
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            ['type' => 'span', 'value' => 'After'],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<p>Before</p><hr /><p>After</p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering numbered list
     */
    public function testRenderNumberedList(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'list',
                        'style'    => 'numbered',
                        'children' => [
                            [
                                'type'     => 'listItem',
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'First',
                                    ],
                                ],
                            ],
                            [
                                'type'     => 'listItem',
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'Second',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<ol><li>First</li><li>Second</li></ol>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering bulleted list explicitly
     */
    public function testRenderBulletedList(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'list',
                        'style'    => 'bulleted',
                        'children' => [
                            [
                                'type'     => 'listItem',
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'Item 1',
                                    ],
                                ],
                            ],
                            [
                                'type'     => 'listItem',
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'Item 2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<ul><li>Item 1</li><li>Item 2</li></ul>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering list with missing style defaults to bulleted
     */
    public function testRenderListDefaultStyle(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'list',
                        'children' => [
                            [
                                'type'     => 'listItem',
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'Default',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<ul><li>Default</li></ul>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering heading with missing level defaults to 1
     */
    public function testRenderHeadingDefaultLevel(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'heading',
                        'children' => [
                            [
                                'type'  => 'span',
                                'value' => 'No Level',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<h1>No Level</h1>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering code block with missing code property
     */
    public function testRenderCodeBlockWithMissingCode(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type' => 'code',
                    ],
                ],
            ],
        ];

        // Empty code renders as self-closing code tag
        $expected = '<pre><code /></pre>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering link with meta attributes
     */
    public function testRenderLinkWithMeta(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            [
                                'type'     => 'link',
                                'url'      => 'https://example.com',
                                'meta'     => [
                                    'target' => '_blank',
                                    'rel'    => 'noopener',
                                ],
                                'children' => [
                                    [
                                        'type'  => 'span',
                                        'value' => 'Link',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<p><a href="https://example.com" target="_blank" rel="noopener">Link</a></p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering span with empty value
     */
    public function testRenderSpanWithEmptyValue(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            [
                                'type'  => 'span',
                                'value' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Empty paragraph renders as self-closing tag
        $expected = '<p />';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test rendering unknown node type returns null
     */
    public function testRenderUnknownNodeType(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            [
                                'type'  => 'span',
                                'value' => 'Before',
                            ],
                        ],
                    ],
                    [
                        'type' => 'unknownNode',
                    ],
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            [
                                'type'  => 'span',
                                'value' => 'After',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<p>Before</p><p>After</p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test custom mark rule
     */
    public function testCustomMarkRule(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            [
                                'type'  => 'span',
                                'marks' => ['strong'],
                                'value' => 'Bold text',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->custom_mark_rules = [
            $this->rule_builder->forMark('strong', function ($context) {
                return $context['adapter']->renderNode(
                    'b',
                    ['key' => $context['key']],
                    $context['children']
                );
            }),
        ];

        $expected = '<p><b>Bold text</b></p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test span with multiple marks
     */
    public function testSpanWithMultipleMarks(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            [
                                'type'  => 'span',
                                'marks' => ['strong', 'emphasis', 'underline'],
                                'value' => 'Multi',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<p><u><em><strong>Multi</strong></em></u></p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text));
    }

    /**
     * Test constructor with custom RenderRule dependency injection
     */
    public function testConstructorWithCustomRenderRule(): void {
        $custom_rule_builder = new RenderRule();
        $renderer = new Renderer($custom_rule_builder);

        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            ['type' => 'span', 'value' => 'Test'],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<p>Test</p>';
        $this->assertEquals($expected, $renderer->render($structured_text));
    }

    /**
     * Test constructor uses default RenderRule when null
     */
    public function testConstructorUsesDefaultRenderRule(): void {
        $renderer = new Renderer(null);

        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            ['type' => 'span', 'value' => 'Test'],
                        ],
                    ],
                ],
            ],
        ];

        $expected = '<p>Test</p>';
        $this->assertEquals($expected, $renderer->render($structured_text));
    }

    /**
     * Test successful inline block rendering
     */
    public function testRenderInlineBlock(): void {
        $structured_text = [
            'value'        => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => 'Check out ',
                                ],
                                [
                                    'type' => 'inlineBlock',
                                    'item' => 'mention-1',
                                ],
                                [
                                    'type'  => 'span',
                                    'value' => ' for more info',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'inlineBlocks' => [
                (object) [
                    'id'   => 'mention-1',
                    'name' => 'John Smith',
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_block = function ($context) {
            $record = $context['record'];
            $adapter = $context['adapter'];
            return $adapter->renderNode('strong', null, '@'.$record->name);
        };

        $expected = '<p>Check out <strong>@John Smith</strong> for more info</p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test error when render_inline_block is missing
     */
    public function testMissingRenderInlineBlockThrowsError(): void {
        $structured_text = [
            'value'        => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineBlock',
                                    'item' => 'mention-1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'inlineBlocks' => [
                (object) ['id' => 'mention-1', 'name' => 'Test'],
            ],
        ];

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage(
            "The Structured Text document contains an 'inlineBlock' node"
        );
        $this->renderer->render($structured_text);
    }

    /**
     * Test error when inlineBlocks key is missing from GraphQL response
     */
    public function testInlineBlockMissingInlineBlocksKeyThrowsError(): void {
        $structured_text = [
            'value'    => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineBlock',
                                    'item' => 'mention-1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_block = function ($context) {
            return '<strong>test</strong>';
        };

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage('.inlineBlocks is not present');
        $this->renderer->render($structured_text, $settings);
    }

    /**
     * Test error when inline block record not found in inlineBlocks array
     */
    public function testInlineBlockRecordNotFoundThrowsError(): void {
        $structured_text = [
            'value'        => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineBlock',
                                    'item' => 'missing-id',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'inlineBlocks' => [
                (object) ['id' => 'different-id', 'name' => 'Test'],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_block = function ($context) {
            return '<strong>test</strong>';
        };

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage(
            'cannot find a record with ID missing-id inside .inlineBlocks'
        );
        $this->renderer->render($structured_text, $settings);
    }

    /**
     * Test multiple inline blocks in one document
     */
    public function testMultipleInlineBlocks(): void {
        $structured_text = [
            'value'        => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineBlock',
                                    'item' => 'ib-1',
                                ],
                                [
                                    'type'  => 'span',
                                    'value' => ' and ',
                                ],
                                [
                                    'type' => 'inlineBlock',
                                    'item' => 'ib-2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'inlineBlocks' => [
                (object) ['id' => 'ib-1', 'text' => 'First'],
                (object) ['id' => 'ib-2', 'text' => 'Second'],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_block = function ($context) {
            $record = $context['record'];
            $adapter = $context['adapter'];
            return $adapter->renderNode('code', null, $record->text);
        };

        $expected = '<p><code>First</code> and <code>Second</code></p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test error when inline item has no links key
     */
    public function testInlineItemMissingLinksKeyThrowsError(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineItem',
                                    'item' => '123',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_record = function ($context) {
            return '<a>test</a>';
        };

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage('.links is not present');
        $this->renderer->render($structured_text, $settings);
    }

    /**
     * Test error when inline item input is not structured text
     */
    public function testInlineItemNonStructuredTextThrowsError(): void {
        $non_structured_text = [
            'type'     => 'root',
            'children' => [
                [
                    'type'     => 'paragraph',
                    'children' => [
                        [
                            'type' => 'inlineItem',
                            'item' => '123',
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_record = function ($context) {
            return '<a>test</a>';
        };

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage(
            'is not a Structured Text GraphQL response'
        );
        $this->renderer->render($non_structured_text, $settings);
    }

    /**
     * Test error when item link has no links key
     */
    public function testItemLinkMissingLinksKeyThrowsError(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type'     => 'itemLink',
                                    'item'     => '123',
                                    'children' => [
                                        ['type' => 'span', 'value' => 'Link'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_link_to_record = function ($context) {
            return '<a>test</a>';
        };

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage('.links is not present');
        $this->renderer->render($structured_text, $settings);
    }

    /**
     * Test error when item link input is not structured text
     */
    public function testItemLinkNonStructuredTextThrowsError(): void {
        $non_structured_text = [
            'type'     => 'root',
            'children' => [
                [
                    'type'     => 'paragraph',
                    'children' => [
                        [
                            'type'     => 'itemLink',
                            'item'     => '123',
                            'children' => [
                                ['type' => 'span', 'value' => 'Link'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_link_to_record = function ($context) {
            return '<a>test</a>';
        };

        $this->expectException(RenderError::class);
        $this->expectExceptionMessage(
            'is not a Structured Text GraphQL response'
        );
        $this->renderer->render($non_structured_text, $settings);
    }

    /**
     * Test custom render_text adapter function
     */
    public function testCustomRenderTextAdapter(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            ['type' => 'span', 'value' => 'hello world'],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_text = function (string $text): string {
            return strtoupper($text);
        };

        $expected = '<p>HELLO WORLD</p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test custom render_node adapter function
     */
    public function testCustomRenderNodeAdapter(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            ['type' => 'span', 'value' => 'Test'],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_node = function (
            ?string $tag,
            ?array $attrs,
            ...$children
        ): string {
            $children_str = implode('', $children);
            return "<{$tag} class=\"custom\">{$children_str}</{$tag}>";
        };

        $expected = '<p class="custom">Test</p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test custom render_fragment adapter function
     */
    public function testCustomRenderFragmentAdapter(): void {
        $structured_text = [
            'schema'   => 'dast',
            'document' => [
                'type'     => 'root',
                'children' => [
                    [
                        'type'     => 'paragraph',
                        'children' => [
                            ['type' => 'span', 'value' => 'First'],
                            ['type' => 'span', 'value' => 'Second'],
                        ],
                    ],
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_fragment = function (array $children): string {
            return implode(' | ', $children);
        };

        $expected = '<p>First | Second</p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test item link with custom meta transformer
     */
    public function testItemLinkWithCustomMetaTransformer(): void {
        $structured_text = [
            'value' => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type'     => 'itemLink',
                                    'item'     => '123',
                                    'meta'     => [
                                        'target' => '_blank',
                                        'rel'    => 'nofollow',
                                    ],
                                    'children' => [
                                        [
                                            'type'  => 'span',
                                            'value' => 'External Link',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'links' => [
                (object) [
                    'id'   => '123',
                    'slug' => 'example',
                ],
            ],
        ];

        $settings = new RenderSettings();
        $settings->meta_transformer = function (array $context): array {
            $meta = $context['meta'];
            return array_merge($meta, ['data-transformed' => 'yes']);
        };

        $settings->render_link_to_record = function ($context) {
            $adapter = $context['adapter'];
            $children = $context['children'];
            $meta = $context['transformed_meta'];
            return $adapter->renderNode('a', $meta, $children);
        };

        $expected = '<p><a target="_blank" rel="nofollow" '.
                    'data-transformed="yes">External Link</a></p>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test document with multiple blocks
     */
    public function testMultipleBlocks(): void {
        $structured_text = [
            'value'  => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        ['type' => 'block', 'item' => 'b1'],
                        ['type' => 'block', 'item' => 'b2'],
                    ],
                ],
            ],
            'blocks' => [
                (object) ['id' => 'b1', 'text' => 'Block One'],
                (object) ['id' => 'b2', 'text' => 'Block Two'],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_block = function ($context) {
            $record = $context['record'];
            $adapter = $context['adapter'];
            return $adapter->renderNode('div', null, $record->text);
        };

        $expected = '<div>Block One</div><div>Block Two</div>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }

    /**
     * Test document with all special node types together
     */
    public function testAllSpecialNodeTypesTogether(): void {
        $structured_text = [
            'value'        => [
                'schema'   => 'dast',
                'document' => [
                    'type'     => 'root',
                    'children' => [
                        [
                            'type'     => 'paragraph',
                            'children' => [
                                [
                                    'type' => 'inlineItem',
                                    'item' => 'link-1',
                                ],
                                [
                                    'type'     => 'itemLink',
                                    'item'     => 'link-1',
                                    'children' => [
                                        ['type' => 'span', 'value' => 'Link'],
                                    ],
                                ],
                                [
                                    'type' => 'inlineBlock',
                                    'item' => 'ib-1',
                                ],
                            ],
                        ],
                        ['type' => 'block', 'item' => 'b1'],
                    ],
                ],
            ],
            'blocks'       => [
                (object) ['id' => 'b1', 'content' => 'Block Content'],
            ],
            'inlineBlocks' => [
                (object) ['id' => 'ib-1', 'name' => 'InlineBlock'],
            ],
            'links'        => [
                (object) ['id' => 'link-1', 'href' => '/page'],
            ],
        ];

        $settings = new RenderSettings();
        $settings->render_inline_record = function ($context) {
            return '<a href="'.$context['record']->href.'">inline</a>';
        };
        $settings->render_link_to_record = function ($context) {
            return '<a href="'.$context['record']->href.'">'.
                   $context['children'].'</a>';
        };
        $settings->render_inline_block = function ($context) {
            return '<em>'.$context['record']->name.'</em>';
        };
        $settings->render_block = function ($context) {
            return '<section>'.$context['record']->content.'</section>';
        };

        $expected = '<p><a href="/page">inline</a>'.
                    '<a href="/page">Link</a>'.
                    '<em>InlineBlock</em></p>'.
                    '<section>Block Content</section>';
        $this->assertEquals($expected, $this->renderer->render($structured_text, $settings));
    }
}
