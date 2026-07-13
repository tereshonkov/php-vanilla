<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        '.build', // кеш фиксера
    ])
    ->ignoreDotFiles(false);

$config = new PhpCsFixer\Config();

return $config
    ->setCacheFile(__DIR__ . '/.build/php-cs-fixer/php-cs-fixer.cache')
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        // PER Coding Style 2.0
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,

        'array_indentation' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'ternary_to_null_coalescing' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
    ]);
