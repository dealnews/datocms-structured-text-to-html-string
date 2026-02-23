<?php
/**
 * Custom rendering example
 *
 * Demonstrates custom node rules, mark rules, and text transformation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DealNews\StructuredText\Renderer;
use DealNews\StructuredText\RenderRule;
use DealNews\StructuredText\RenderSettings;
use DealNews\StructuredText\Utils;

// Create renderer and rule builder instances
$renderer = new Renderer();
$rule_builder = new RenderRule();

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
                            'value' => 'Hello World',
                        ],
                    ],
                ],
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
                            'value' => 'important',
                        ],
                        [
                            'type'  => 'span',
                            'value' => ' text.',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

echo "Default rendering:\n";
echo $renderer->render($structured_text) . "\n\n";

// Custom text transformation
$settings = new RenderSettings();
$settings->render_text = function(string $text): string {
    return str_replace('Hello', 'Howdy', $text);
};

echo "With text transformation (Hello -> Howdy):\n";
echo $renderer->render($structured_text, $settings) . "\n\n";

// Custom node rule - increase heading level
$settings->custom_node_rules = [
    $rule_builder->forNode(
        function($node) {
            return Utils::isHeading($node);
        },
        function($context) {
            $level = $context['node']['level'] + 1;
            return $context['adapter']->renderNode(
                "h{$level}",
                ['key' => $context['key']],
                $context['children']
            );
        }
    ),
];

echo "With custom heading rule (level +1):\n";
echo $renderer->render($structured_text, $settings) . "\n\n";

// Custom mark rule - render strong as <b>
$settings->custom_mark_rules = [
    $rule_builder->forMark('strong', function($context) {
        return $context['adapter']->renderNode(
            'b',
            ['key' => $context['key']],
            $context['children']
        );
    }),
];

echo "With custom mark rule (strong -> b):\n";
echo $renderer->render($structured_text, $settings) . "\n";
