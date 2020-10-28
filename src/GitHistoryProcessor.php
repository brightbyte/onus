<?php

namespace Wikimedia\Onus;

use Gitonomy\Git\Commit;
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
	private $limit = 10000;
	private $branch = 'master';

	public function __construct( $dir ) {
		$this->repository = new Repository( $dir );
	}

	/**
	 * @param string[] $filter
	 */
	public function setFilter( array $filter ): void {
		$this->filter = $filter;
	}

	/**
	 * @param int $limit
	 */
	public function setLimit( int $limit ): void {
		$this->limit = $limit;
	}

	/**
	 * @param string $branch
	 */
	public function setBranch( string $branch ): void {
		$this->branch = $branch;
	}

	/**
	 * @return Commit[]
	 */
	public function listCommits() {
		$commits = [];
		$log = $this->repository->getLog( $this->branch );
		$log->setLimit( $this->limit );

		/** @var Commit $commit */
		foreach ( $log->getIterator() as $commit ) {
			$info = $this->parseMessage( $commit->getMessage() );

			$info['hash'] = $commit->getHash();
			$info['date'] = $commit->getAuthorDate()->format( 'r' );

			// TODO: report progress

			if ( empty( $info['bugs'] ) ) {
				continue;
			}

			if ( $this->filter ) {
				$match = array_intersect( $this->filter, $info['bugs'] );
				if ( !$match ) {
					continue;
				}
			}

			$info['files'] = [];
			foreach ( $commit->getDiff()->getFiles() as $file ) {
				$info['files'][] = $file->getName();
			}

			$commits[] = $info;
		}

		return $commits;
	}

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
