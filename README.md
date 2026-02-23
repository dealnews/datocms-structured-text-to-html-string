# DatoCMS Structured Text to HTML String (PHP)

Convert DatoCMS Structured Text (DAST format) to HTML strings. PHP port of the [official JavaScript library](https://github.com/datocms/structured-text/tree/main/packages/to-html-string).

## Requirements

- **PHP 8.2+**
- Composer

## Installation

```bash
composer require dealnews/datocms-structured-text-to-html-string
```

## Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use DealNews\StructuredText\Renderer;

// Create renderer instance
$renderer = new Renderer();

// Simple structured text
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
                        'value' => 'Hello world!',
                    ],
                ],
            ],
        ],
    ],
];

$html = $renderer->render($structured_text);
// Output: <p>Hello world!</p>
```

## Features

- ✅ Converts DAST documents to HTML strings
- ✅ Supports custom node and mark rendering rules
- ✅ Handles blocks, inline blocks, inline items, and item links
- ✅ Text transformation support
- ✅ Newline handling (converts to `<br>` tags)
- ✅ Type-safe with full PHPDoc coverage

## Advanced Usage

### Custom Text Transformation

```php
use DealNews\StructuredText\Renderer;
use DealNews\StructuredText\RenderSettings;

$renderer = new Renderer();

$settings = new RenderSettings();
$settings->render_text = function(string $text): string {
    return str_replace('Hello', 'Howdy', $text);
};

$html = $renderer->render($structured_text, $settings);
```

### Custom Node Rules

Change how specific node types are rendered:

```php
use DealNews\StructuredText\Renderer;
use DealNews\StructuredText\RenderRule;
use DealNews\StructuredText\RenderSettings;
use DealNews\StructuredText\Utils;

$settings = new RenderSettings();
$settings->custom_node_rules = [
    RenderRule::forNode(
        function($node) {
            return Utils::isHeading($node);
        },
        function($context) {
            // Increase heading level by 1
            $level = $context['node']['level'] + 1;
            return $context['adapter']->renderNode(
                "h{$level}",
                ['key' => $context['key']],
                $context['children']
            );
        }
    ),
];

$html = Renderer::render($structured_text, $settings);
```

### Custom Mark Rules

Change how text marks (bold, italic, etc.) are rendered:

```php
use DealNews\StructuredText\RenderRule;

$settings->custom_mark_rules = [
    $rule_builder->forMark('strong', function($context) {
        // Render strong as <b> instead of <strong>
        return $context['adapter']->renderNode(
            'b',
            ['key' => $context['key']],
            $context['children']
        );
    }),
];
```

### Rendering Blocks and Links

```php
$graphql_response = [
    'value'  => [
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
                            'type'     => 'itemLink',
                            'item'     => '123',
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => 'this article',
                                ],
                            ],
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
    'blocks' => [
        (object) [
            'id'    => '456',
            '__typename' => 'ImageRecord',
            'url'   => 'https://example.com/image.jpg',
            'alt'   => 'Example image',
        ],
    ],
    'links'  => [
        (object) [
            'id'    => '123',
            '__typename' => 'ArticleRecord',
            'title' => 'How to Code',
            'slug'  => 'how-to-code',
        ],
    ],
];

$settings = new RenderSettings();

// Render inline item references
$settings->render_inline_record = function($context) {
    $record = $context['record'];
    $adapter = $context['adapter'];
    
    if ($record->__typename === 'ArticleRecord') {
        return $adapter->renderNode(
            'a',
            ['href' => "/articles/{$record->slug}"],
            $record->title
        );
    }
    
    return null;
};

// Render item links
$settings->render_link_to_record = function($context) {
    $record = $context['record'];
    $adapter = $context['adapter'];
    $children = $context['children'];
    
    if ($record->__typename === 'ArticleRecord') {
        return $adapter->renderNode(
            'a',
            ['href' => "/articles/{$record->slug}"],
            $children
        );
    }
    
    return null;
};

// Render blocks
$settings->render_block = function($context) {
    $record = $context['record'];
    $adapter = $context['adapter'];
    
    if ($record->__typename === 'ImageRecord') {
        return $adapter->renderNode(
            'img',
            [
                'src' => $record->url,
                'alt' => $record->alt,
            ]
        );
    }
    
    return null;
};

$html = Renderer::render($graphql_response, $settings);
// Output: <p>Check out <a href="/articles/how-to-code">this article</a></p>
//         <img src="https://example.com/image.jpg" alt="Example image" />
```

## API Reference

### `Renderer::render($structured_text_or_node, ?RenderSettings $settings = null): ?string`

Main rendering function. Converts structured text to HTML.

**Parameters:**
- `$structured_text_or_node` - Structured text from DatoCMS or a document node
- `$settings` - Optional rendering configuration

**Returns:** HTML string or `null` if input is `null`

**Throws:** `RenderError` if rendering fails

### `RenderSettings`

Configuration object for customizing rendering behavior.

**Properties:**
- `custom_node_rules` - Array of custom node rendering rules
- `custom_mark_rules` - Array of custom mark rendering rules
- `render_inline_record` - Callback for rendering inline items
- `render_link_to_record` - Callback for rendering item links
- `render_block` - Callback for rendering blocks
- `render_inline_block` - Callback for rendering inline blocks
- `render_text` - Callback for transforming text
- `render_node` - Custom node renderer (advanced)
- `render_fragment` - Custom fragment renderer (advanced)
- `meta_transformer` - Function to transform link meta into attributes

### Utility Functions

```php
use DealNews\StructuredText\Utils;

Utils::isBlock($node);        // Check if node is a block
Utils::isInlineBlock($node);  // Check if node is inline block
Utils::isInlineItem($node);   // Check if node is inline item
Utils::isItemLink($node);     // Check if node is item link
Utils::isHeading($node);      // Check if node is heading
Utils::isParagraph($node);    // Check if node is paragraph
Utils::isSpan($node);         // Check if node is span
Utils::isLink($node);         // Check if node is link
```

### Rule Builders

```php
use DealNews\StructuredText\RenderRule;

// Create a custom node rule
$rule = RenderRule::forNode(
    function($node) { return $node['type'] === 'heading'; },
    function($context) { /* return HTML */ }
);

// Create a custom mark rule
$mark_rule = $rule_builder->forMark('strong', function($context) {
    /* return HTML */
});
```

## Error Handling

The library throws `RenderError` exceptions when:
- Required render callbacks are missing (e.g., `render_block` when blocks are present)
- Referenced records cannot be found in the links/blocks arrays
- Document structure is invalid

```php
use DealNews\StructuredText\RenderError;
use DealNews\StructuredText\Renderer;

try {
    $html = Renderer::render($structured_text, $settings);
} catch (RenderError $e) {
    echo "Rendering failed: " . $e->getMessage();
    $problematic_node = $e->getNode();
    // Handle error...
}
```

## Default Mark Rendering

| Mark | HTML Tag |
|------|----------|
| `strong` | `<strong>` |
| `code` | `<code>` |
| `emphasis` | `<em>` |
| `underline` | `<u>` |
| `strikethrough` | `<s>` |
| `highlight` | `<mark>` |

## Edge Cases

- **Newlines in text**: Converted to `<br />` tags automatically
- **Empty nodes**: Rendered as self-closing tags (e.g., `<hr />`)
- **Null renderers**: Returning `null` from custom renderers skips that node
- **Missing records**: Throws `RenderError` to prevent silent failures

## Development

### Requirements

- PHP 8.2+
- Composer
- PHPUnit 11.5+

### Running Tests

```bash
composer install
./vendor/bin/phpunit
```

### Running Examples

```bash
php examples/basic.php
php examples/custom_rendering.php
php examples/blocks_and_links.php
```

## Testing

The library includes mocks-friendly dependency injection:

```php
// Inject a mock RenderRule for testing
$mock_rule_builder = $this->createMock(RenderRule::class);
$renderer = new Renderer($mock_rule_builder);

$html = $renderer->render($structured_text, $settings);
```

## License

BSD 3-Clause License - see LICENSE file for details

## Credits

This is a PHP port of the official [DatoCMS Structured Text to HTML String](https://github.com/datocms/structured-text/tree/main/packages/to-html-string) JavaScript library.

Ported and maintained by [DealNews](https://github.com/dealnews).
