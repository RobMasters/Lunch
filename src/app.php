<?php

$app = require __DIR__.'/bootstrap.php';

$app->get('/', function () use ($app) {
	
	$sql = "SELECT users.user_id, users.name, trips.date 
			FROM users
			LEFT OUTER JOIN trips ON trips.user_id = users.user_id  
			ORDER BY trips.date DESC
			GROUP BY users.user_id;";
	
	$users = $app['db']->fetchAll("SELECT * FROM users;");
	
	
    return $app['twig']->render('index.html.twig', array('users' => $users));
})
->bind('homepage');

return $app;