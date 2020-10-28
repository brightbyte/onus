<?php

namespace Wikimedia\Onus;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../arcanist/src/__phutil_library_init__.php';

( new ListTickets() )->main();

class ListTickets {
	private $config;

	private function getConfig( $name ) {
		if ( !$this->config ) {
			$this->config = require __DIR__ . '/../config/config.php';
		}
		if ( !isset( $this->config[$name] ) ) {
			throw new \Exception( "No such config key \"$name\"" );
		}
		return $this->config[$name];
	}

	public function main() {
		$queryEngine = new QueryPhab(
			$this->getConfig( 'projectNames' ),
			$this->getConfig( 'priorities' ),
			$this->getConfig( 'delay' ),
			$this->getConfig( 'phabURL' ),
			$this->getConfig( 'apiToken' )
		);

		$phabTasks = $queryEngine->executeQueries();

		echo PHP_EOL;
		foreach ($phabTasks as $id => $phabTask ) {
			echo $id . ': ' . $phabTask['name'] . ' (' . $phabTask['priority'] . ')' . PHP_EOL;
		}
		echo 'Found ' . count( $phabTasks ) . ' tasks.';
	}
}
