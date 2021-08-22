<?php

$config = new PhpCsFixer\Config();
$finder = PhpCsFixer\Finder::create();
$finder
    // The vendor directory is excluded by default.
    ->exclude('.github')
    ->exclude('bin')
    ->exclude('build')
    ->exclude('docker')
    ->in(__DIR__)
;
$config
    ->setRiskyAllowed(true)
    ->setCacheFile('build/.php-cs-fixer/php_cs.json')
    ->setRules(
        [
            '@PSR12' => true,
            '@PSR12:risky' => true,
            '@PHP70Migration' => true,
            '@PHP70Migration:risky' => true,
            '@PHP80Migration' => true,
            '@PHP80Migration:risky' => true,
            '@Symfony:risky' => true,
            'no_unused_imports' => true,
            'ordered_imports' => true,
            'ordered_class_elements' => true,
            'strict_comparison' => true,
            'native_constant_invocation' => false,
            'php_unit_test_class_requires_covers' => true,
            'php_unit_dedicate_assert_internal_type' => true,
            'php_unit_mock' => true,
            'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
            'no_extra_blank_lines' => true,
            'header_comment' => [
                'comment_type' => 'PHPDoc',
                'location' => 'after_open',
                'separate' => 'both',
                'header' => <<<'HEADER'
This file is part of the streak package.

(C) Alan Gabriel Bem <alan.bem@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER
                ,
            ],
        ]
    )
    ->setFinder($finder)
;

return $config;
