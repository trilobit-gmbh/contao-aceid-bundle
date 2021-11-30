<?php
$date = date('Y');
$header = <<<EOF
@copyright  trilobit GmbH
@author     trilobit GmbH <https://github.com/trilobit-gmbh>
@license    LGPL-3.0-or-later
@link       http://github.com/trilobit-gmbh/contao-aceid-bundle
EOF;
$config = new \PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@DoctrineAnnotation' => true,
        'array_syntax' => [
            'syntax' => 'short'
        ],
        // one should use PHPUnit methods to set up expected exception instead of annotations
        /*'general_phpdoc_annotation_remove' => [
            'expectedException',
            'expectedExceptionMessage',
            'expectedExceptionMessageRegExp',
        ],
        'no_extra_consecutive_blank_lines' => [
            'break',
            'continue',
            'extra',
            'return',
            'throw',
            'use',
            'parenthesis_brace_block',
            'square_brace_block',
            'curly_brace_block',
        ],*/
        'blank_line_after_namespace' => true,
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'heredoc_to_nowdoc' => true,
        #'no_short_echo_tag' => false,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'php_unit_strict' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        #'psr4' => true,
        'semicolon_after_instruction' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'yoda_style' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('trilobit')
            ->in([
                __DIR__.'/src'
            ])
    )
    ;
