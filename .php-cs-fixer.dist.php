<?php

$fileHeaderComment = <<<COMMENT
This file is part of the "CompactWeekBundle" for Kimai.
All rights reserved by Christoph Vollmann (https://cloudchristoph.com).

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
COMMENT;

$fixer = new PhpCsFixer\Config();
$fixer
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'header_comment' => ['header' => $fileHeaderComment, 'separate' => 'both'],
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_trim' => true,
        'single_quote' => true,
        'ternary_to_null_coalescing' => true,
        'trailing_comma_in_multiline' => false,
        'yoda_style' => false,
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'scope' => 'namespaced'
        ],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__
            ])->exclude([
                __DIR__ . '/Resources/',
                __DIR__ . '/vendor/',
                __DIR__ . '/.github/',
            ])
    )
;

return $fixer;
