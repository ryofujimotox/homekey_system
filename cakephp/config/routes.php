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

    $routes->connect('/', ['controller' => 'Homes', 'action' => 'index']);
};
