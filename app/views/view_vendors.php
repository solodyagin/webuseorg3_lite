<?php
/*
 * WebUseOrg3 Lite - учёт оргтехники в организации
 * Лицензия: GPL-3.0
 * Разработчики:
 *   Грибов Павел,
 *   Сергей Солодягин (solodyagin@gmail.com)
 * Сайт: http://грибовы.рф
 */

/*
 * Справочники / Производители
 */

// Запрещаем прямой вызов скрипта.
defined('WUO') or die('Доступ запрещён');

$user = User::getInstance();
$cfg = Config::getInstance();

// Проверка: если пользователь - не администратор и не назначена одна из ролей, то
if (!$user->isAdmin() && !$user->TestRoles('1')):
	?>

	<div class="alert alert-danger">
		У вас нет доступа в раздел "Справочники / Производители"!<br><br>
		Возможно не назначена <a href="http://грибовы.рф/wiki/doku.php/основы:доступ:роли" target="_blank">роль</a>:
		"Полный доступ".
	</div>

<?php else: ?>

	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12 col-md-12 col-sm-12">
				<table id="list2"></table>
				<div id="pager2"></div>
			</div>
		</div>
	</div>
	<script src="templates/<?= $cfg->theme; ?>/assets/js/vendors.js"></script>

<?php endif;