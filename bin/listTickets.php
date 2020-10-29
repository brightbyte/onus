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
		$options = getopt(
			'', [
				'output:',
				'start-date:'
			],
			$optind );

		if ( !isset( $options['output'] ) ) {
			echo "--output is required\n";
			exit( 1 );
		}
		$outputFile = fopen( $options['output'], 'w' );
		if ( !$outputFile ) {
			echo "Unable to open output file \"{$options['output']}\"\n";
			exit( 1 );
		}
		$startEpoch = null;
		if ( isset( $options['start-date'] ) ) {
			$startEpoch = strtotime( $options['start-date'] );
			if ( $startEpoch === false ) {
				echo "Invalid start date\n";
				exit( 1 );
			}
		}
		$queryEngine = new QueryPhab(
			$this->getConfig( 'projectNames' ),
			$this->getConfig( 'priorities' ),
			$this->getConfig( 'delay' ),
			$this->getConfig( 'phabURL' ),
			$this->getConfig( 'apiToken' ),
			$startEpoch
		);

		$phabTasks = $queryEngine->executeQueries();

		fwrite( $outputFile,
			json_encode(
				$phabTasks,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			) .
			"\n"
		);
		echo "\nFound " . count( $phabTasks ) . " tasks.\n";
	}
}
