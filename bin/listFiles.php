<?php

namespace Wikimedia\Onus;

use DateTime;

require __DIR__ . '/../vendor/autoload.php';

( new ListFiles( $argv[1] ) )->main();

class ListFiles {
	public function main() {
		$options = getopt(
			'', [
				'dir:',
				'input:',
				'output:'
			],
			$optind );

		if ( !isset( $options['dir'] ) ) {
			echo "--dir is required\n";
			exit( 1 );
		}
		if ( !file_exists( $options['dir'] ) ) {
			echo "git directory \"{$options['git']}\"not found\n";
			exit( 1 );
		}
		if ( !isset( $options['input'] ) ) {
			echo "--input is required\n";
			exit( 1 );
		}
		if ( !file_exists( $options['input'] ) ) {
			echo "input file \"{$options['input']}\"not found\n";
			exit( 1 );
		}
		$outputFile = fopen( $options['output'], 'w' );
		if ( !$outputFile ) {
			echo "Unable to open output file \"{$options['output']}\"\n";
			exit( 1 );
		}

		// TODO: use this as input to GitHistoryProcessor once it uses that
		$tasks = array_keys(json_decode(file_get_contents($options['input']), true));
		array_walk($tasks, function( &$value, $key ) {
			$value = 'T' . $value;
		});

		$processor = new GitHistoryProcessor( $options['dir'] );
		$processor->setFilter( $tasks );
		$processor->setStartTime( new DateTime( '2020-09-01' ) );

		$processor->setProgressCallback( function() {
			print '.';
		} );

		print "Scanning...";
		$commits = $processor->listCommits();
		print " done.\n";

		fwrite( $outputFile,
			json_encode(
				$commits,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			) .
			"\n"
		);
		echo "\nFound " . count( $commits ) . " commits.\n";
	}
}