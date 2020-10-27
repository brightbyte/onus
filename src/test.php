<?php

require_once 'QueryPhab.php';

$projectNames = [ 'wikimedia-production-error' ];
$delay = 0;
$phabURL = 'https://phabricator.wikimedia.org';
$apiToken = '<insert token here>';

$queryEngine = new QueryPhab(
	$projectNames,
	$delay,
	$phabURL,
	$apiToken
);
$queryEngine->execute();
