<!DOCTYPE html>
<html>
<head>
	<?= $this->Html->charset(); ?>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">


	<title><?= env('COMPETITION_NAME'); ?>: Inject Engine</title>
	<?php
	echo $this->Html->meta('icon');

	echo $this->Html->css('bootstrap.min');
	echo $this->Html->css('style');

	echo $this->Html->script('vendor/jquery.min');
	echo $this->Html->script('vendor/bootstrap.min');
	echo $this->Html->script('site');

	echo $this->Html->scriptBlock('window.BASE = "'.$this->Html->url('/').'";', ['safe' => false]);

	echo $this->fetch('meta');
	echo $this->fetch('css');
	echo $this->fetch('script');
	?>
</head>
<body>
<?php if ( isset($emulating) && $emulating ): ?>
<div class="alert alert-danger" style="margin-bottom: 0px;">
	You are currently emulating a user's account!
	<?= $this->Html->link('EXIT', '/user/emulate_clear', ['class' => 'btn btn-sm btn-info pull-right']); ?>
</div>
<?php endif; ?>

<nav class="navbar navbar-default<?= env('COMPETITION_LOGO') != false ? ' navbar-with-logo' : ''; ?>">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-nav">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="<?= $this->Html->url('/'); ?>">
				<?php if ( env('COMPETITION_LOGO') != false ): ?>
				
				<img src="<?= $this->Html->url(env('COMPETITION_LOGO')); ?>"/>
				
				<?php else: ?>

				<?= env('COMPETITION_NAME'); ?>
				
				<?php endif; ?>
			</a>
		</div>
		<div class="navbar-collapse collapse" id="main-nav">
			<ul class="nav navbar-nav">
				<?php
				echo $this->Misc->navbarItem('Home', '/', isset($at_home));
				
				if ( $this->Auth->loggedIn() ) {
					echo $this->Misc->navbarItem('Injects', '/injects', isset($at_injects));
				}

				echo $this->Misc->navbarItem('Scoreboard', '/pages/scoreboard', isset($at_scoreboard));
				?>
			</ul>
			
			<ul class="nav navbar-nav navbar-right">
				<?php
				if ( $this->Auth->loggedIn() ) {
					if ( $this->Auth->isBlueTeam() ) {
						echo $this->Misc->navbarItem('Team Panel', '/team', isset($at_teampanel));
					}

					if ( $this->Auth->isStaff() ) {
						echo $this->Misc->navbarDropdown('Competition Central', isset($at_staff), [
							$this->Misc->navbarItem('Competition Overview', '/staff'),
							'<li role="separator" class="divider"></li>',
							$this->Misc->navbarItem('Grader Island', '/staff/grader'),
							$this->Misc->navbarItem('Scheduler', '/scheduler'),
						]);
					}

					if ( $this->Auth->isAdmin() ) {
						echo $this->Misc->navbarDropdown('Backend', isset($at_backend), [
							$this->Misc->navbarItem('Site Manager', '/site'),
							'<li role="separator" class="divider"></li>',
							$this->Misc->navbarItem('User Manager', '/site/users'),
							$this->Misc->navbarItem('Inject Manager', '/site/injects'),
							//$this->Misc->navbarItem('Service Manager', '/admin/service'),
							$this->Misc->navbarItem('Log Manager', '/logs'),
						]);
					}

					echo $this->Misc->navbarDropdown($this->Auth->user('username'), isset($at_profile), [
						$this->Misc->navbarItem('My Profile', '/user/profile'),
						$this->Misc->navbarItem('Logout', '/user/logout'),
					]);
				} else {
					echo $this->Misc->navbarItem('Login', '/user/login', isset($at_login));
				}
				?>
			</ul>
		</div>
	</div>
</nav>

<div class="container">
	<?php
	foreach ( $announcements AS $a ):
	if ( in_array($a['Announcement']['id'], $this->Session->read('read_announcements')) ) continue;
	?>
	<div class="alert alert-info alert-dismissible alert-announcement" role="alert" data-aid="<?= $a['Announcement']['id']; ?>">
		<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
		<p><?= $a['Announcement']['content']; ?></p>
	</div>
	<?php endforeach; ?>

	<?= $this->Session->flash(); ?>

	<?= $this->fetch('content'); ?>
</div>

<footer class="footer">
	<div class="container">
		<p class="text-muted pull-right">
			ie<sup>2</sup> <abbr title="DEV">DEV</abbr>
		</p>
	</div>
</footer>

</body>
</html>