<!DOCTYPE html>
<html lang="en">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>SpotifyBaton: Blaster</title>
		<script defer src="view/blaster/functions.js"></script>
		<link rel="stylesheet" href="view/blaster/style.css">
	</head>
	<body>
		<main>
			<section id="history">
				<?php foreach (array_reverse($sb->player_history() ?? []) as $track): ?>
					<div class="cover"><img src="<?= $track["cover"] ?>"></div>
				<?php endforeach; ?>
			</section>
			<section id="current">
				<?php if ($track = $sb->player_current()): ?>
					<div class="cover"><img src="<?= $track["cover"] ?>"></div>
					<ul>
						<li class="title"><?= $track["track"]["title"] ?></li>
						<li class="artist"><?= $sb->format_artists($track["artists"]) ?></li>
						<li class="album"><?= $track["album"]["title"] ?></li>
					</ul>
				<?php else: ?>
					<div class="cover"><svg><use href="view/blaster/icons/spotify.svg#spotify"/></svg></div>
					<ul>
						<li class="title">Not playing</li>
						<li class="artist">Anything</li>
						<li class="album">Right now</li>
					</ul>
				<?php endif; ?>
			</section>
			<section id="upcoming">
				<?php foreach ($sb->player_upcoming() ?? [] as $track): ?>
					<div class="cover"><img src="<?= $track["cover"] ?>"></div>
				<?php endforeach; ?>
			</section>
		</main>
		<?php require_once "{$home}/view/select.php" ?>
	</body>
</html>
