<?php
namespace DealNews\StructuredText;

/**
 * Exception thrown when rendering structured text fails
 *
 * This exception is thrown when the renderer encounters errors such as:
 * - Missing required render callbacks (renderInlineRecord, renderBlock, etc.)
 * - Missing records in the links/blocks arrays
 * - Invalid document structure
 *
 * Usage:
 * ```php
 * try {
 *     $html = render($structured_text);
 * } catch (RenderError $e) {
 *     echo "Rendering failed: " . $e->getMessage();
 *     $node = $e->getNode(); // Get the problematic node
 * }
 * ```
 */
class RenderError extends \RuntimeException {

    /**
     * The node that caused the error
     *
     * @var array|object|null
     */
    protected $node;

    /**
     * Creates a new RenderError exception
     *
     * @param string            $message Error message describing what went
     *                                   wrong
     * @param array|object|null $node    The document node that caused the
     *                                   error (optional)
     * @param int               $code    Exception code (default: 0)
     * @param \Throwable|null   $previous Previous exception for chaining
     */
    public function __construct(
        string $message,
        $node = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->node = $node;
    }

    /**
     * Gets the node that caused the error
     *
     * @return array|object|null The problematic node, or null if not
     *                           available
     */
    public function getNode() {
        return $this->node;
    }
}
