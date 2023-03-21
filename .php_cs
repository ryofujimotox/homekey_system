<?php

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR2' => true,

        //デフォルト知らない設定
        'array_indentation' => true,
        'method_separation' => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'single_quote' => true,
        'binary_operator_spaces' => array(
            'align_double_arrow' => false,
            'align_equals' => false,
        ),
        'declare_equal_normalize' => true,
        'function_typehint_space' => true,
        'hash_to_slash_comment' => true,
        'include' => true,
        'lowercase_cast' => true,
        'no_spaces_around_offset' => true,
        'object_operator_without_whitespace' => true,
        'ternary_operator_spaces' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,

        //いい設定

        //関数の括弧
        'braces' => array(
            'allow_single_line_closure' => false,
            'position_after_anonymous_constructs' => 'same',
            'position_after_control_structures' => 'same',
            'position_after_functions_and_oop_constructs' => 'same'
        ),

        //配列の形   ['syntax' => 'short'],
        //'array_syntax' => array('syntax' => 'long'),

        //特定の記述の前に一行改行を入れる
        'no_extra_consecutive_blank_lines' => array(
            'curly_brace_block',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'throw',
            'use',
        ),

        //namespace前に改行
        'single_blank_line_before_namespace' => false,

        //isset to unsets 書き方
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,

        //文字 . 文字　の空白開ける
        'concat_space' => array(
            'spacing' => 'one'
        ),

        //elseif 固める
        'elseif' => true,

        //PHPコードの文字コードをBOMでなくUTF-8
        'encoding' => true,

        //関数が複数行に渡るときには1行にする
        'method_argument_space' => array(
            'ensure_fully_multiline' => true,
        ),

        //;; を修正
        'no_empty_statement' => true,

        //=>の前後で複数行になるスペースを禁止します
        'no_multiline_whitespace_around_double_arrow' => true,

        //   ; の空白消す
        'no_singleline_whitespace_before_semicolons' => true,

        //配列内で、カンマの前にスペースを禁止
        'no_whitespace_before_comma_in_array' => true,

        //空白行でスペースを禁止
        'no_whitespace_in_blank_line' => true,

        //phpのとじタグを削除しない
        //'no_closing_tag' => false
    ))
    //->setIndent("\t")
    ->setLineEnding("\n");