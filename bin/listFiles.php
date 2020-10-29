<?php

namespace Wikimedia\Onus;

use DateTime;

require __DIR__ . '/../vendor/autoload.php';

( new ListFiles() )->main();

class ListFiles {
	public function main() {
		$options = getopt(
			'', [
				'dir:',
				'start-date:',
				'path:',
				'tasks:',
				'output:'
			],
			$optind );

		if ( !isset( $options['dir'] ) ) {
			echo "--dir is required\n";
			exit( 1 );
		}
		if ( !file_exists( $options['dir'] ) ) {
			echo "git directory \"{$options['dir']}\" not found\n";
			exit( 1 );
		}
		if ( !isset( $options['start-date'] ) ) {
			echo "--start-date is required\n";
			exit( 1 );
		}
		if ( isset( $options['tasks'] ) && !file_exists( $options['tasks'] ) ) {
			echo "tasks file \"{$options['tasks']}\" not found\n";
			exit( 1 );
		}
		if ( isset( $options['path'] ) && !file_exists( $options['dir'] . '/' . $options['path'] ) ) {
			echo "path \"{$options['dir']}/{$options['path']}\" not found\n";
			exit( 1 );
		}
		$outputFile = fopen( $options['output'], 'w' );
		if ( !$outputFile ) {
			echo "Unable to open output file \"{$options['output']}\"\n";
			exit( 1 );
		}

		$processor = new GitHistoryProcessor( $options['dir'] );
		$processor->setStartTime( new DateTime( $options['start-date'] ) );

		$processor->setProgressCallback( function () {
			print '.';
		} );

		if ( isset( $options['tasks'] ) ) {
			$tasks = array_keys( json_decode( file_get_contents( $options['tasks'] ), true ) );
			array_walk( $tasks, function ( &$value, $key ) {
				$value = 'T' . $value;
			} );
			$processor->setFilter( $tasks );
			print "Filtering by " . count( $tasks ) . " tasks.\n";
		}

		if ( isset( $options['path'] ) ) {
			$processor->setPath( $options['path'] );
		}

		print "Scanning...";
		$files = $processor->getActivityByFile();
		print " done.\n";

		fwrite( $outputFile,
			json_encode(
				$files,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			) .
			"\n"
		);
		echo "\nFound activity on " . count( $files ) . " files.\n";
	}
}
