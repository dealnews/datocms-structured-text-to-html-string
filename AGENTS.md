# AI Agent Instructions - DatoCMS Structured Text to HTML String

This repository is a PHP port of the DatoCMS Structured Text renderer. It converts DAST (DatoCMS Abstract Syntax Tree) documents into HTML strings. Think of it as a compiler: DAST goes in, HTML comes out.

## Requirements

- **PHP 8.2+**
- **PHPUnit 11.5+**
- Composer

## What This Library Does

Converts structured content from DatoCMS into HTML. The input is a tree structure (DAST format), the output is a clean HTML string. Handles all the standard nodes (headings, lists, links, etc.) plus special DatoCMS features like blocks, inline items, and item links.

**Key use cases:**
- Rendering blog posts from DatoCMS
- Converting structured content to HTML for emails
- Server-side rendering of DatoCMS content
- Custom HTML generation with your own rendering rules

## Quick Start

```bash
# Run all tests
./vendor/bin/phpunit

# Run single test
./vendor/bin/phpunit --filter testRenderNullValue

# Run examples
php examples/basic.php
php examples/custom_rendering.php
```

## Architecture at a Glance

Three-layer design that separates concerns:

```
DAST Document
    ↓
Renderer (orchestration layer)
    → Builds custom rules for blocks/links/inline items
    → Looks up records from GraphQL responses
    ↓
GenericHtmlRenderer (traversal engine)
    → Walks the document tree recursively
    → Applies custom rules during traversal
    → Handles both modern ('children') and legacy ('content') DAST
    ↓
Adapter (HTML generation)
    → Wraps string concatenation with proper escaping
    → Can be swapped for custom implementations
    ↓
HTML String
```

**Key components:**
- `Renderer.php` - Your entry point, static API only
- `GenericHtmlRenderer.php` - The workhorse, walks the tree
- `DefaultAdapter.php` - Builds HTML tags with escaping
- `RenderRule.php` - Factory for custom rendering rules
- `Utils.php` - Type guards for node identification

## DealNews Coding Standards

We follow PSR-12 with DealNews-specific rules. Here's what matters most:

### Class-Based API Only
No bare functions allowed. Everything goes in a class.

```php
// ✗ Wrong
function render($dast) { }

// ✓ Correct
class Renderer {
    public static function render(array $dast): string { }
}
```

### 1TBS Bracing
Opening brace on same line, except multi-line conditionals.

```php
// ✓ Correct
public function foo() {
    if (condition) {
        bar();
    }
    
    // Multi-line conditionals get opening brace on next line
    if (
        really_long_condition &&
        another_condition &&
        yet_another
    ) {
        baz();
    }
}
```

### snake_case Variables
Always. No camelCase for variables or properties.

```php
// ✗ Wrong
$myVariable = "foo";
protected $userName;

// ✓ Correct
$my_variable = "foo";
protected string $user_name;
```

### Protected Visibility
Use `protected` instead of `private` for extensibility.

```php
// ✓ Correct
protected function renderNode(array $node): string { }
protected array $custom_rules = [];
```

### Single Return Point
Prefer one return statement. Early returns for validation are OK.

```php
// ✓ Correct
public function process(array $data): string {
    // Early validation return is fine
    if (empty($data)) {
        return '';
    }
    
    // Main logic sets $result
    $result = '';
    foreach ($data as $item) {
        $result .= $this->processItem($item);
    }
    
    return $result;  // Single return point
}
```

### Complete PHPDoc Coverage
All public classes and methods need full documentation.

```php
/**
 * Renders a DAST document to HTML string
 *
 * @param array $dast The DAST document structure
 * @param array $options Optional rendering configuration
 * 
 * @return string The rendered HTML
 * 
 * @throws RenderError If required renderers are missing
 */
public static function render(array $dast, array $options = []): string {
```

## Project-Specific Patterns

### The Two DAST Flavors

DatoCMS has evolved over time. We support both formats:

```php
// Modern DAST (uses 'children')
['type' => 'paragraph', 'children' => [...]]

// Legacy DAST (uses 'content')
['type' => 'paragraph', 'content' => [...]]
```

Always use `getChildrenKey()` method—never hardcode the key name.

### GraphQL Response Unwrapping

DatoCMS GraphQL wraps documents in a `value` key:

```php
// What the API returns
['value' => ['schema' => 'dast', 'document' => [...]]]

// What we need
['schema' => 'dast', 'document' => [...]]
```

The renderer automatically unwraps this. Look for the check in `GenericHtmlRenderer::render()` that tests for `value` key AND missing `type` key (this distinction is critical—some nodes legitimately have a `value` property).

### Record Lookup Strategy

Special nodes (`block`, `inlineItem`, `itemLink`) reference external records by ID. The rendering flow:

1. Check if custom renderer provided (throw `RenderError` if missing)
2. Verify GraphQL response structure exists
3. Loop through appropriate array (`blocks`, `links`, etc.)
4. Match by `id` field
5. Throw `RenderError` if record not found

**Edge case:** The GraphQL response structure varies by node type. Blocks live in `$dast['blocks']`, links in `$dast['links']`, inline items in `$dast['inlineBlocks']`. Don't mix them up.

### Mark Rendering Chain

Text marks (bold, italic, etc.) wrap content in layers, applied in reverse order:

```php
"Hello" + ['strong', 'emphasis']
→ <em><strong>Hello</strong></em>
```

The last mark in the array becomes the outermost wrapper. This matches how rich text editors typically structure formatting.

### Newline Handling

Span nodes split on `\n` characters:

```php
"Line 1\nLine 2\nLine 3"
→ "Line 1" + <br /> + "Line 2" + <br /> + "Line 3"
```

This happens in `renderSpan()` via `explode()` + `implode()`. Every `\n` becomes a `<br />` tag.

## Testing Strategy

Tests verify identical output to the JavaScript version. We ported all test cases from the original library.

**What we test:**
- Null/empty document handling
- All standard node types (headings, lists, links, code blocks)
- Custom rendering rules (nodes and marks)
- Blocks with record lookup
- Error cases (missing renderers, missing records)
- Complex GraphQL responses
- Newline conversion

**Testing pattern:**
```php
$html = Renderer::render($dast, $options);
$this->assertSame($expected_html, $html);
```

All assertions use `assertSame()` for strict string matching. We want byte-for-byte identical output.

## Common Tasks

### Adding a New Node Type

1. Add type guard to `Utils.php`:
   ```php
   public static function isMyNode(array $node): bool {
       return ($node['type'] ?? '') === 'myNode';
   }
   ```

2. Add case to switch in `GenericHtmlRenderer::renderNode()`:
   ```php
   case 'myNode':
       return $this->renderMyNode($node, $key);
   ```

3. Implement the renderer method:
   ```php
   protected function renderMyNode(array $node, int $key): string {
       $children = $this->renderChildren($node, $key);
       return $this->adapter->renderNode('div', ['class' => 'my-node'], $children);
   }
   ```

4. Add test cases in `RenderTest.php`

### Adding a New Mark Type

1. Add to `DEFAULT_MARKS` in `GenericHtmlRenderer.php`:
   ```php
   protected const DEFAULT_MARKS = [
       // ... existing marks
       'myMark' => 'mark',
   ];
   ```

2. Add test case verifying the mark renders correctly

### Creating Custom Rendering Rules

Use the factory methods in `RenderRule.php`:

```php
use DealNews\StructuredText\RenderRule;

// Custom node rule
$node_rule = RenderRule::forNode(
    function($node) { return $node['type'] === 'heading'; },
    function($context) {
        $level = $context['node']['level'];
        return $context['adapter']->renderNode(
            "h{$level}",
            ['class' => 'custom-heading'],
            $context['children']
        );
    }
);

// Custom mark rule
$mark_rule = RenderRule::forMark(
    'highlight',
    function($context) {
        return $context['adapter']->renderNode(
            'mark',
            ['class' => 'highlight'],
            $context['children']
        );
    }
);
```

## Gotchas & Edge Cases

### The "Value Property" Confusion

Some nodes have a `value` property (like `span` with text content). GraphQL responses also wrap documents in a `value` key. Don't confuse them!

**The fix:** Check for BOTH `value` key AND absence of `type` key. If `type` exists, it's a real node, not a wrapper.

```php
// GraphQL wrapper (unwrap this)
['value' => ['schema' => 'dast', ...]]  // No 'type' key

// Span node (don't unwrap this)
['type' => 'span', 'value' => 'Hello']  // Has 'type' key
```

### Children vs Content Key

Never hardcode `'children'` or `'content'` as the key name. Always use:

```php
$children_key = $this->getChildrenKey($node);
$children = $node[$children_key] ?? [];
```

### Empty Text Nodes

Spans can have empty `value` properties. These should render as empty strings, not be skipped entirely. The difference matters for document fidelity.

### Record Lookup Can Fail

When rendering blocks/links/inline items, the referenced record might not exist in the GraphQL response. This should throw `RenderError`, not return empty string or silently fail. Users need to know their data is incomplete.

### Mark Order Matters

Marks are applied in reverse order. `['strong', 'emphasis']` becomes `<em><strong>`, not `<strong><em>`. This is intentional and matches the JavaScript implementation.

## Making Changes

### Workflow

1. **Write the test first** - We're TDD-friendly here
2. **Run existing tests** - Make sure you start green
3. **Implement the change** - Keep it surgical
4. **Run tests again** - Verify you didn't break anything
5. **Update docs** - README.md if user-facing, inline PHPDoc always

### Before Committing

```bash
# All tests must pass
./vendor/bin/phpunit

# Examples must still work
php examples/basic.php
php examples/custom_rendering.php
php examples/blocks_and_links.php
```

### Performance Considerations

This library does recursive tree traversal. Very deep documents (100+ levels) could hit recursion limits. In practice, CMS content rarely exceeds 10-15 levels.

**If you need to optimize:**
- Cache rendered nodes (not implemented yet)
- Implement iterative traversal (instead of recursive)
- Stream output instead of building full string

### Security Notes

All text content goes through `htmlspecialchars()` in the adapter. User-provided custom rendering rules bypass this—they're responsible for escaping.

**When writing custom rules:** Always escape user content unless you explicitly trust the source.

## File Reference

Quick map of where things live:

- **src/Renderer.php** - Main API, record lookup logic (lines 94-390 are complex)
- **src/GenericHtmlRenderer.php** - Tree traversal, node switching
- **src/DefaultAdapter.php** - HTML tag building with escaping
- **src/RenderRule.php** - Factory methods for custom rules
- **src/Utils.php** - Type guards (`isHeading()`, `isParagraph()`, etc.)
- **src/RenderError.php** - Exception class for rendering failures
- **src/RenderSettings.php** - Configuration value object
- **tests/RenderTest.php** - All test cases (10 tests)
- **examples/** - Working examples for common use cases

## Questions?

If something's unclear or you hit an edge case not documented here, check:

1. The JavaScript source (this is a port, behavior should match)
2. Test cases (they show real-world usage)
3. IMPLEMENTATION_SUMMARY.md (has additional technical details)
4. REFACTORING_NOTES.md (explains the function-to-class refactoring)

This library is production-ready and battle-tested against the official JavaScript implementation. Make changes confidently, but verify with tests.
