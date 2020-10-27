#!/usr/bin/php
<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

require_once __DIR__ . '/../arcanist/src/__phutil_library_init__.php';

class QueryPhab {
	private $projectNames;
	private $delay;
	private $phabURL;
	private $apiToken;
	private $client;
	private $phabTasks = [];

	public function __construct( $projectNames, $delay, $phabURL, $apiToken ) {
		$this->projectNames = $projectNames;
		$this->delay = $delay;
		$this->phabURL = $phabURL;
		$this->apiToken = $apiToken;
	}

	public function execute() {
		$this->client = new ConduitClient( $this->phabURL );
		$this->client->setConduitToken( $this->apiToken );
		foreach ( $this->projectNames as $projectName ) {
			$this->getPhabricatorTasksFromProject( $projectName );
		}
	}

	private function getPhabricatorTasksFromProject( $projectName ) {
		$params = [
			'constraints' => [
				'projects' => [
					$projectName
				],
			],
			'attachments' => [
				'projects' => [
					true
				],
				'columns' => [
					true
				]
			]
		];
		$resultData = $this->callAPI( 'maniphest.search', $params );
		foreach ( $resultData as $data ) {
			$this->parseTask( $data );
		}
	}

	private function callAPI( $api, $params ) {
		$after = null;
		$allData = [];
		do {
			if ( $this->delay > 0 ) {
				sleep( $this->delay );
			}
			if ( $after === null ) {
				$p = $params;
			} else {
				$a = [
					"after" => $after
				];
				$p = $params + $a;
			}

			$results = $this->client->callMethodSynchronous( $api, $p );
			$resultData = $results['data'];
			echo count( $resultData ) . PHP_EOL;
			foreach ( $resultData as $data ) {
				$allData[] = $data;
			}
			$after = $results['cursor']['after'];
			echo $after . PHP_EOL;
		} while ( $after !== null );
		return $allData;
	}

	private function parseTask( $data ) {
		$taskID = $data['id'];
		if ( isset( $this->phabTasks[$taskID] ) ) {
			return;
		}
		$task = [];
		$task['name'] = $data['fields']['name'];
		$task['dateCreated'] = $data['fields']['dateCreated'];
		$task['dateModified'] = $data['fields']['dateModified'];
		$task['dateClosed'] = $data['fields']['dateClosed'];
		$task['status'] = $data['fields']['status']['value'];
		$task['color'] = $data['fields']['priority']['color'];
		$task['points'] = $data['fields']['points'];
		$this->phabTasks[$taskID] = $task;
	}
}
