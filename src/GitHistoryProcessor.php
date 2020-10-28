<?php

namespace Wikimedia\Onus;

use DateTime;
use Gitonomy\Git\Commit;
use Gitonomy\Git\Log;
use Gitonomy\Git\Repository;

class GitHistoryProcessor {

	/**
	 * @var Repository
	 */
	private $repository;

	/**
	 * @var array
	 */
	private $filter = null;
	private $batchSize = 1000;
	private $branch = 'master';
	private $path = null;

	/** @var DateTime|null */
	private $startTime = null;

	/** @var callable|null */
	private $progressCallback;

	public function __construct( $dir ) {
		$this->repository = new Repository( $dir );
	}

	/**
	 * @param string $path
	 */
	public function setPath( string $path ): void {
		$this->path = $path;
	}

	/**
	 * @param DateTime $startTime
	 */
	public function setStartTime( DateTime $startTime ): void {
		$this->startTime = $startTime;
	}

	/**
	 * @param string[] $filter
	 */
	public function setFilter( array $filter ): void {
		$this->filter = $filter;
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize( int $batchSize ): void {
		$this->batchSize = $batchSize;
	}

	/**
	 * @param string $branch
	 */
	public function setBranch( string $branch ): void {
		$this->branch = $branch;
	}

	/**
	 * @param callable $progressCallback
	 */
	public function setProgressCallback( callable $progressCallback ): void {
		$this->progressCallback = $progressCallback;
	}

	/**
	 * @return array[]
	 */
	public function getActivityByFile(): array {
		$files = [];
		$commits = $this->listCommits();

		foreach ( $commits as $cmtInfo ) {
			foreach ( $cmtInfo['files'] as $file ) {
				if ( !isset( $files[$file] ) ) {
					$files[$file] = [
						'File' => $file,
						'TicketCount' => 0,
						'CommitCount' => 0,
						'Tickets' => [],
						'Commits' => [],
					];
				}

				// don't list all files on all files
				unset( $cmtInfo['files'] );

				$files[ $file ]['Commits'][] = $cmtInfo;
				$files[ $file ]['CommitCount']++;

				$files[ $file ]['Tickets'] =
					array_unique( array_merge( $cmtInfo['bugs'], $files[ $file ]['Tickets'] ) );
				$files[ $file ]['TicketCount'] = count( $files[ $file ]['Tickets'] );
			}
		}

		uasort( $files, function( $a, $b ) {
			return $b['CommitCount'] <=> $a['CommitCount'];
		} );

		return $files;
	}

	/**
	 * @return array[]
	 */
	public function listCommits(): array {
		$commits = [];
		$log = $this->repository->getLog( $this->branch, $this->path ? [ $this->path ] : null );
		$log->setLimit( $this->batchSize );

		$offset = 0;
		$done = false;
		while ( !$done ) {
			$this->listCommitBatch( $log, $commits, $offset, $done );

			if ( $this->progressCallback ) {
				( $this->progressCallback )( $offset );
			}
		}

		return $commits;
	}

	public function listCommitBatch( Log $log, array &$commits, &$offset, &$done ) {
		$done = true; // will be set to false again if we find anything to iterate over

		$log->setOffset( $offset );

		/** @var Commit $commit */
		foreach ( $log->getIterator() as $commit ) {
			if (
				$this->startTime &&
				$commit->getCommitterDate()->getTimestamp() < $this->startTime->getTimestamp()
			) {
				$done = true;
				break;
			}

			$done = false;
			$offset++;

			if ( count( $commit->getParentHashes() ) > 1 ) {
				// skip merge commits
				continue;
			}

			$info = $this->parseMessage( $commit->getMessage() );

			$info['hash'] = $commit->getHash();
			$info['date'] = gmdate( 'Y-m-d\TH:i:s', $commit->getCommitterDate()->getTimestamp() );

			if ( $this->filter ) {
				$match = array_intersect( $this->filter, $info['bugs'] );
				if ( !$match ) {
					continue;
				}
			}

			$info['files'] = [];
			foreach ( $commit->getDiff()->getFiles() as $file ) {
				$name = $file->getName();

				// only include files that match the path prefix
				if ( !$this->path || substr( $name, 0, strlen( $this->path ) ) === $this->path ) {
					$info['files'][] = $name;
				}
			}

			if ( !$info['files'] ) {
				continue;
			}

			$commits[] = $info;
		}
	}

	/**
	 * @param string $message
	 *
	 * @return array
	 */
	private function parseMessage( string $message ) {
		$info = [ 'message' => $message ];
		$info['bugs'] = [];
		$info['subject'] = null;

		$paragraphs = preg_split( '/(\r\n|\n|\r){2,}/', $message );
		if ( !$paragraphs ) {
			return $info;
		}

		$info['subject'] = trim( $paragraphs[0] );

		if ( count( $paragraphs ) < 2 ) {
			return $info;
		}

		$footer = $paragraphs[ count( $paragraphs ) - 1 ];
		$footerLines = preg_split( '/(\r\n|\n|\r)+/', $footer );

		foreach ( $footerLines as $line ) {
			if ( preg_match( '/^Bug: *(T\d+)/i', $line, $m ) ) {
				$info['bugs'][] = $m[1];
			}
		}

		return $info;
	}

}
