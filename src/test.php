<?php

require_once 'QueryPhab.php';

$projectNames = [ 'wikimedia-production-error' ];
$priorities = [ 100 ];
$delay = 0;
$phabURL = 'https://phabricator.wikimedia.org';
$apiToken = '<insert api token here>';

$queryEngine = new QueryPhab(
	$projectNames,
	$priorities,
	$delay,
	$phabURL,
	$apiToken
);

$phabTasks = $queryEngine->executeQueries();

echo PHP_EOL;
foreach ($phabTasks as $id => $phabTask ) {
	echo $id . ': ' . $phabTask['name'] . ' (' . $phabTask['priority'] . ')' . PHP_EOL;
}
echo 'Found ' . count( $phabTasks ) . ' tasks.';
