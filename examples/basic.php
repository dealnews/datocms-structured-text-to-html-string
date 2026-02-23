<?php
/**
 * Basic usage example
 *
 * Demonstrates simple structured text rendering
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DealNews\StructuredText\Renderer;

// Create renderer instance
$renderer = new Renderer();

// Simple paragraph
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

echo "Simple paragraph:\n";
echo $renderer->render($structured_text) . "\n\n";

// Text with formatting
$formatted_text = [
    'schema'   => 'dast',
    'document' => [
        'type'     => 'root',
        'children' => [
            [
                'type'     => 'paragraph',
                'children' => [
                    [
                        'type'  => 'span',
                        'value' => 'This is ',
                    ],
                    [
                        'type'  => 'span',
                        'marks' => ['strong'],
                        'value' => 'bold',
                    ],
                    [
                        'type'  => 'span',
                        'value' => ' and this is ',
                    ],
                    [
                        'type'  => 'span',
                        'marks' => ['emphasis'],
                        'value' => 'italic',
                    ],
                    [
                        'type'  => 'span',
                        'value' => '.',
                    ],
                ],
            ],
        ],
    ],
];

echo "Formatted text:\n";
echo $renderer->render($formatted_text) . "\n\n";

// Multiple paragraphs with headings
$document = [
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
                        'value' => 'Welcome',
                    ],
                ],
            ],
            [
                'type'     => 'paragraph',
                'children' => [
                    [
                        'type'  => 'span',
                        'value' => 'This is a sample document.',
                    ],
                ],
            ],
            [
                'type'     => 'heading',
                'level'    => 2,
                'children' => [
                    [
                        'type'  => 'span',
                        'value' => 'Features',
                    ],
                ],
            ],
            [
                'type'     => 'paragraph',
                'children' => [
                    [
                        'type'  => 'span',
                        'value' => 'Easy HTML rendering.',
                    ],
                ],
            ],
        ],
    ],
];

echo "Complete document:\n";
echo $renderer->render($document) . "\n";
