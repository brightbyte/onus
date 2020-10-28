<?php

use Wikimedia\Onus\GitHistoryProcessor;

include( __DIR__ . '/../vendor/autoload.php' );

$dir = $argv[1];
$processor = new GitHistoryProcessor( $dir );
//$processor->setBatchSize( 3 );
$processor->setFilter( [ 'T263592' ] );
$processor->setStartTime( new DateTime( '2020-09-01' ) );

$processor->setProgressCallback( function() {
	print '.';
} );

print "Scanning...";
$commits = $processor->listCommits();
print " done.\n";

foreach ( $commits as $cmtInfo ) {
	print 'commit ' . $cmtInfo['hash']. "\n";
	print 'Date: ' . $cmtInfo['date'] . "\n";
	print 'Subject: ' . $cmtInfo['subject'] . "\n";
	print 'Bugs: ' . implode( ', ', $cmtInfo['bugs'] ) . "\n";
	print "\n";
	foreach ( $cmtInfo['files'] as $file ) {
		print $file . "\n";
	}
	print "\n";
}
