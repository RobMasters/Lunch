<?php

$app = require __DIR__.'/bootstrap.php';

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig');
})
->bind('homepage');

return $app;