<?php

namespace Wikimedia\Onus;

use Gitonomy\Git\Commit;
use Gitonomy\Git\Diff\File;
use Gitonomy\Git\Repository;
use Gitonomy\Git\Revision;

class GitHistoryProcessor {

	/**
	 * @var Repository
	 */
	private $repository;

	/**
	 * @var array
	 */
	private $filter;

	public function __construct( $dir, $filter ) {
		$this->repository = new Repository( $dir );
		$this->filter = $filter;
	}

	/**
	 * @return Commit[]
	 */
	public function listCommits() {
		$commits = [];
		$log = $this->repository->getLog('master');

		/** @var Revision $revision */
		foreach ( $log->getRevisions() as $revision ) {
			$commits[] = $revision->getCommit();
		}

		return $commits;
	}

}

include( __DIR__ . '/../vendor/autoload.php' );

$dir = $argv[1];
$processor = new GitHistoryProcessor( $dir, [] );

$commits = $processor->listCommits();

foreach ( $commits as $cmt ) {
	print 'commit ' . $cmt->getHash() . "\n";
	print 'Author: ' . $cmt->getAuthorName() . '<' . $cmt->getAuthorEmail() . '>' . "\n";
	print 'Date: ' . $cmt->getAuthorDate()->format( 'r' ) . "\n";
	print "\n";
	print preg_replace( '/^/m', "\t", $cmt->getMessage() ) . "\n";
	print "\n";

	/** @var File $file */
	foreach ( $cmt->getDiff()->getFiles() as $file ) {
		print $file->getName() . "\n";
	}
	print "\n";
}
