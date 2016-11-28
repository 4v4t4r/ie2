<?php
namespace InjectTypes;

class FlagSubmission extends InjectSubmissionBase {
	
	public function getID() {
		return 'flag';
	}

	public function getTemplate() {
		return 'TODO.';
	}

	public function getSubmittedTemplate($submissions) {
		return 'TODO';
	}

	public function validateSubmission($inject, $submission) {
		return false;
	}

	public function handleSubmission($inject, $submission) {
		throw new BadMethodCallException('Not implemented');
	}
}