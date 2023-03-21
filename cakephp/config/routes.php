<?php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    // API
    $routes->prefix('Api', function (RouteBuilder $builder) {
        $builder->setExtensions(['json']);//最後にjsonをつけるとjsonで出力する

        // その他
        $builder->resources('Commons', [
            'map' => [
                'ogp' => [
                    'action' => 'ogp',
                    'method' => ['GET'],
                ],
            ]
        ]);
        $builder->fallbacks();
    });

    $routes->prefix('V1', function (RouteBuilder $builder) {
        $alphabets = '[a-zA-Z]+';
        $alphabetNum = '[a-zA-Z0-90-9]+';
        $page_pattern = array(
            'alphabets' => $alphabetNum,
            'key' => '[a-zA-Z0-9_]+',
            'id' => '[1-9]+[0-9]*',
            'file_type' => 'file|image',
            'is_view' => '0|view',

            'model' => $alphabets,
            'column' => $alphabetNum,
            'asset_prefix' => $alphabetNum,
            'preview_type' => 'preview|download',
        );

        // プレビュー用
        $builder->connect('/{preview_type}/{model}/{column}/{asset_prefix}/{id}', ['controller' => 'FileManage', 'action' => 'attachImage'])->setPatterns($page_pattern)->setPass(['preview_type', 'model', 'column', 'asset_prefix', 'id']);

        //ファイル出力
        $builder->connect('/preview/{alphabets}/{key}', ['controller' => 'FileManage', 'action' => 'viewContentFile'])->setPatterns($page_pattern)->setPass(['alphabets', 'key']);
        $builder->connect('/{file_type}/{alphabets}/{id}/', ['controller' => 'FileManage', 'action' => 'manageAttaches'])->setPatterns($page_pattern)->setPass(['alphabets', 'id', 'file_type']);
        $builder->connect('/{file_type}/{alphabets}/{id}/{is_view}', ['controller' => 'FileManage', 'action' => 'manageAttaches'])->setPatterns($page_pattern)->setPass(['alphabets', 'id', 'file_type', 'is_view']);
    });
};
