<?php
/**
 * Blocks and links example
 *
 * Demonstrates rendering blocks, inline items, and item links from DatoCMS
 * GraphQL responses
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DealNews\StructuredText\Renderer;
use DealNews\StructuredText\RenderSettings;

// Create renderer instance
$renderer = new Renderer();

$graphql_response = [
    'value'  => [
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
                            'value' => 'Blog Post ',
                        ],
                        [
                            'type' => 'inlineItem',
                            'item' => 'author-123',
                        ],
                    ],
                ],
                [
                    'type'     => 'paragraph',
                    'children' => [
                        [
                            'type'  => 'span',
                            'value' => 'Read more about this in our ',
                        ],
                        [
                            'type'     => 'itemLink',
                            'item'     => 'article-456',
                            'children' => [
                                [
                                    'type'  => 'span',
                                    'value' => 'detailed guide',
                                ],
                            ],
                        ],
                        [
                            'type'  => 'span',
                            'value' => '.',
                        ],
                    ],
                ],
                [
                    'type' => 'block',
                    'item' => 'image-789',
                ],
                [
                    'type'     => 'paragraph',
                    'children' => [
                        [
                            'type'  => 'span',
                            'value' => 'Thanks for reading!',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'blocks' => [
        (object) [
            'id'         => 'image-789',
            '__typename' => 'ImageRecord',
            'url'        => 'https://example.com/photo.jpg',
            'alt'        => 'Beautiful landscape',
        ],
    ],
    'links'  => [
        (object) [
            'id'         => 'author-123',
            '__typename' => 'AuthorRecord',
            'name'       => 'Jane Doe',
            'slug'       => 'jane-doe',
        ],
        (object) [
            'id'         => 'article-456',
            '__typename' => 'ArticleRecord',
            'title'      => 'Complete Guide',
            'slug'       => 'complete-guide',
        ],
    ],
];

$settings = new RenderSettings();

$settings->render_inline_record = function($context) {
    $record = $context['record'];
    $adapter = $context['adapter'];
    
    switch ($record->__typename) {
        case 'AuthorRecord':
            return $adapter->renderNode(
                'a',
                [
                    'href'  => "/authors/{$record->slug}",
                    'class' => 'author-link',
                ],
                $record->name
            );
        default:
            return null;
    }
};

$settings->render_link_to_record = function($context) {
    $record = $context['record'];
    $adapter = $context['adapter'];
    $children = $context['children'];
    
    switch ($record->__typename) {
        case 'ArticleRecord':
            return $adapter->renderNode(
                'a',
                ['href' => "/articles/{$record->slug}"],
                $children
            );
        default:
            return null;
    }
};

$settings->render_block = function($context) {
    $record = $context['record'];
    $adapter = $context['adapter'];
    
    switch ($record->__typename) {
        case 'ImageRecord':
            return $adapter->renderNode(
                'figure',
                null,
                $adapter->renderNode(
                    'img',
                    [
                        'src' => $record->url,
                        'alt' => $record->alt,
                    ]
                ),
                $adapter->renderNode(
                    'figcaption',
                    null,
                    $record->alt
                )
            );
        default:
            return null;
    }
};

echo "Rendered HTML:\n";
echo $renderer->render($graphql_response, $settings) . "\n";
