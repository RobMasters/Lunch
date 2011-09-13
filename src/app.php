<?php

$app = require __DIR__.'/bootstrap.php';

use Symfony\Component\HttpFoundation\Request;

$app->get('/', function () use ($app) {
	$sql = "SELECT 
				users.id, users.name, users.debt, 
				COALESCE( (
					SELECT date FROM trips WHERE user_id = users.id ORDER BY date DESC LIMIT 1
				), 0) AS `last_bought` 
			FROM users
			ORDER BY name ASC;";
	$users = $app['db']->fetchAll($sql);
	
	$highestDebt = 0;
	$matches = array();
	foreach($users as $user) {
		if($user['debt'] > $highestDebt) {
			$matches = array($user['id'] => $user['last_bought']);
			$highestDebt = $user['debt'];
		} elseif($user['debt'] == $highestDebt) {
			$matches[$user['id']] = $user['last_bought'];
		}
	}
	
	asort($matches);
	$turn = array_shift(array_keys($matches));
	
    return $app['twig']->render('index.html.twig', array('users' => $users, 'turn' => $turn));
})
->bind('homepage');



$app->get('/reset', function () use ($app) {
	$app['db']->executeQuery('DELETE FROM trips;');
	$app['db']->executeQuery('DELETE FROM trip_benefactors;');
	$app['db']->executeQuery('UPDATE users SET debt = 0;');
	
	return $app->redirect('/');
});


$app->post('/buy', function(Request $request) use ($app) {
	$benefactors = $request->get('benefactors');
	$buyer = $request->get('user_id');
	
	// Remove the user buying lunch if they checked themself
	$self = array_search($buyer, $benefactors);
	if($self !== false) {
		unset($benefactors[$self]);
	}
	
	$app['db']->insert('trips', array('user_id' => $buyer));
	if(!empty($benefactors)) {
		$app['db']->update('users', array('debt' => '0'), array('id' => $buyer));
		
		$tripId = $app['db']->lastInsertId('trips');
		foreach($benefactors as $benactor) {
			$app['db']->insert('trip_benefactors', array('trip_id' => $tripId, 'user_id' => $benactor));
			$app['db']->executeUpdate('UPDATE users SET debt = debt + 1 WHERE id = ?', array($benactor));
		}
	}
	return $app->redirect('/');
})
->bind('buy_lunch');

return $app;