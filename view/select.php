<?php

	$views = [];
	foreach (glob(__DIR__ . "/*") as $dir) {
		if (is_dir($dir)) $views[] = basename($dir);
	}
	$selected = $_COOKIE["view"] ?? "default";
?>

<select id="view" style="
	color-scheme: dark;
	position: fixed;
	top: 0;
	right: 0;
">

	<?php foreach ($views as $view): ?>
		<option<?= ($view == $selected ? " selected": "")?>><?= $view ?></option>
	<?php endforeach; ?>

</select>
<script>
	const view = document.getElementById("view");
	view.addEventListener("change", ev => {
		document.cookie = `view=${view.value}`;
		location.reload();
	});
</script>
