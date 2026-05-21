<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'declare_strict_types' => true,
        'multiline_whitespace_before_semicolons' =>['strategy' => 'no_multi_line']
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);