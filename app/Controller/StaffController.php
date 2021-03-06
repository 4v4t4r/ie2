<?php
App::uses('AppController', 'Controller');

class StaffController extends AppController {
	public $helpers = ['ScoreEngine.EngineOutputter'];
	public $uses = [
		'Config', 'Inject', 'Log', 'Grade', 'Group', 'Schedule', 'Submission',
		'ScoreEngine.Check', 'ScoreEngine.Service', 'ScoreEngine.Team'
	];

	/**
	 * Pagination Settings
	 */
	public $paginate = [
		'Log' => [
			'fields' => [
				'Log.id', 'Log.time', 'Log.type', 'Log.data',
				'Log.ip', 'Log.message', 'User.username', 'User.group_id',
			],
			'contain' => [
				'User' => [
					'Group.name',
				]
			],
			'order' => [
				'Log.id' => 'DESC'
			],
		],

		'OnlyGraded' => [
			'fields' => [
				'Submission.id', 'Submission.created', 'Submission.deleted',
				'Inject.id', 'Inject.title', 'Inject.sequence', 'Inject.type',
				'User.username', 'Group.name', 'Group.team_number',
				'Grade.created', 'Grade.grade', 'Grade.comments',
				'Grader.username',
			],
			'joins' => [
				[
					'table'      => 'users',
					'alias'      => 'Grader',
					'type'       => 'LEFT',
					'conditions' => [
						'Grader.id = Grade.grader_id',
					],
				]
			],
			'conditions' => [
				'OR' => [
					'Grade.created IS NOT NULL',
					'Submission.deleted' => true,
				],
			],
			'order' => [
				'Grade.created' => 'DESC',
				'Submission.created' => 'DESC',
			],
		],
	];

	public function beforeFilter() {
		parent::beforeFilter();

		// Enforce staff only
		$this->Auth->protect(env('GROUP_STAFF'));

		// Load + setup the InjectStyler helper
		$this->helpers[] = 'InjectStyler';
		$this->helpers['InjectStyler'] = [
			'types'  => $this->Config->getInjectTypes(),
			'inject' => new stdClass(), // Nothing...for now
		];

		// We're at the staff page
		$this->set('at_staff', true);
	}

	public function beforeRender() {
		parent::beforeRender();

		// Setup the ScoreEngine EngineOutputter
		$this->helpers['ScoreEngine.EngineOutputter']['data'] = $this->Check->getChecksTable(
			$this->Team->find('all'),
			$this->Service->find('all')
		);
	}

	/**
	 * Competition Overview Page
	 *
	 * @url /staff
	 * @url /staff/index
	 */
	public function index() {
		$this->set('active_injects', $this->Schedule->getActiveInjects(env('GROUP_BLUE')));
		$this->set('recent_expired', $this->Schedule->getRecentExpired(env('GROUP_BLUE')));

		$this->Paginator->settings += $this->paginate['Log'];
		$this->set('recent_logs', $this->Paginator->paginate('Log'));
	}

	/**
	 * Inject View Page
	 *
	 * @url /staff/inject/<id>
	 */
	public function inject($id=false) {
		$inject = $this->Inject->findById($id);
		if ( empty($inject) ) {
			throw new NotFoundException('Unknown Inject');
		}
	}

	/**
	 * Grader Island Page
	 *
	 * @url /staff/graders
	 */
	public function graders() {
		$this->set('ungraded', $this->Submission->getAllUngradedSubmissions());

		$this->Paginator->settings += $this->paginate['OnlyGraded'];
		$this->set('graded', $this->Paginator->paginate('Submission'));
	}

	/**
	 * Submission Grade Page
	 *
	 * @url /staff/grade/<sid>
	 */
	public function grade($sid=false) {
		$submission = $this->Submission->getSubmission($sid);
		if ( empty($submission) ) {
			throw new NotFoundException('Unknown submission');
		}

		if ( $this->request->is('post') ) {
			if (
				!isset($this->request->data['grade']) ||
				!isset($this->request->data['comments']) ||
				empty($this->request->data['grade']) ||
				empty($this->request->data['comments']) ||
				$this->request->data['grade'] > $submission['Inject']['max_points']
			) {
				$this->Flash->danger('Incomplete data. Please try again.');
				return $this->redirect('/staff/grade/'.$sid);
			}

			$data = [
				'grade'    => $this->request->data['grade'],
				'comments' => $this->request->data['comments'],
			];
			$grade = $this->Grade->findBySubmissionId($sid);

			if ( empty($grade) ) {
				$this->Grade->create();

				$data['submission_id'] = $sid;
				$data['grader_id']     = $this->Auth->user('id');
				$data['created']       = time();

				$logMessage = sprintf('Graded submission #%d for %s', $sid, $submission['Group']['name']);
			} else {
				$this->Grade->id = $grade['Grade']['id'];

				$logMessage = sprintf('Edited submission #%d for %s', $sid, $submission['Group']['name']);
			}

			// Save + log
			$this->Grade->save($data);
			$this->logMessage(
				'grading',
				$logMessage,
				[
					'previous_grade'    => (empty($grade) ? null : $grade['Grade']['grade']),
					'previous_comments' => (empty($grade) ? null : $grade['Grade']['comments']),
					'new_grade'         => $data['grade'],
					'new_comments'      => $data['comments'],
				],
				$this->Grade->id
			);

			// Return home, ponyboy
			$this->Flash->success('Saved!');
			return $this->redirect('/staff/graders');
		}

		$this->set('submission', $submission);
	}

	/**
	 * View (download) Submission
	 *
	 * @url /staff/submission/<sid>
	 */
	public function submission($sid=false) {
		$submission = $this->Submission->getSubmission($sid);
		if ( empty($submission) ) {
			throw new NotFoundException('Unknown submission');
		}

		$data = json_decode($submission['Submission']['data'], true);
		$download = (isset($this->params['url']['download']) && $this->params['url']['download'] == true);

		// Let's verify our data is correct
		if ( md5(base64_decode($data['data'])) !== $data['hash'] ) {
			throw new InternalErrorException('Data storage failure.');
		}

		// Create the new response for the data
		$response = new CakeResponse();
		$response->type($data['extension']);
		$response->body(base64_decode($data['data']));
		$response->disableCache();

		$type = ($download ? 'attachment' : 'inline');
		$filename = $data['filename'];
		$response->header('Content-Disposition', $type.'; filename="'.$filename.'"');

		return $response;
	}

	/**
	 * Export Grades
	 *
	 * @url /staff/export
	 */
	public function export() {
		$blueTeams = $this->Group->getChildren(env('GROUP_BLUE'));
		$submissions = $this->Submission->getAllSubmissions($blueTeams, true);
		$out = ['team_number,inject_number,grade'];

		foreach ( $submissions AS $s ) {
			$out[] = $s['Group']['team_number'].','.$s['Inject']['sequence'].','.$s['Grade']['grade'];
		}

		return $this->ajaxResponse(implode(PHP_EOL, $out));
	}
}
