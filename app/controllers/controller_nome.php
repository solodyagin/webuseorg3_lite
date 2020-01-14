<?php

/*
 * WebUseOrg3 - учёт оргтехники в организации
 * Лицензия: GPL-3.0
 * Разработчик: Грибов Павел
 * Сайт: http://грибовы.рф
 */
/*
 * Inventory - учёт оргтехники в организации
 * Лицензия: GPL-3.0
 * Разработчик: Сергей Солодягин (solodyagin@gmail.com)
 */

/* Запрещаем прямой вызов скрипта. */
defined('SITE_EXEC') or die('Доступ запрещён');

class Controller_Nome extends Controller {

	function index() {
		$cfg = Config::getInstance();
		$this->view->generate('view_nome', $cfg->theme);
	}

	/* Для работы jqGrid */
	function list() {
		$user = User::getInstance();
		/* Проверяем может ли пользователь просматривать? */
		($user->isAdmin() || $user->TestRights([1,3,4,5,6])) or die('Недостаточно прав');
		$page = GetDef('page', 1);
		if ($page == 0) {
			$page = 1;
		}
		$limit = GetDef('rows');
		$sidx = GetDef('sidx', '1');
		$sord = GetDef('sord');
		$filters = GetDef('filters');
		$flt = json_decode($filters, true);
		$cnt = is_array($flt['rules']) ? count($flt['rules']) : 0;
		$where = '';
		for ($i = 0; $i < $cnt; $i++) {
			$field = $flt['rules'][$i]['field'];
			if ($field == 'nomeid') {
				$field = 'nome.id';
			}
			$data = $flt['rules'][$i]['data'];
			$where = $where . "($field LIKE '%$data%')";
			if ($i < ($cnt - 1)) {
				$where = $where . ' AND ';
			}
		}
		if ($where != '') {
			$where = 'WHERE ' . $where;
		}
		/* Готовим ответ */
		$responce = new stdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;
		$sql = 'SELECT COUNT(*) AS cnt FROM nome';
		try {
			$row = DB::prepare($sql)->execute()->fetch();
			$count = ($row) ? $row['cnt'] : 0;
		} catch (PDOException $ex) {
			throw new DBException('Не могу выбрать список номенклатуры (1)', 0, $ex);
		}
		if ($count == 0) {
			jsonExit($responce);
		}
		$total_pages = ceil($count / $limit);
		if ($page > $total_pages) {
			$page = $total_pages;
		}
		$start = $limit * $page - $limit;
		if ($start < 0) {
			jsonExit($responce);
		}
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		$sql = <<<TXT
SELECT     nome.id         AS nomeid,
           group_nome.name AS groupname,
           vendor.name     AS vendorname,
           nome.name       AS nomename,
           nome.active     AS nomeactive
FROM       nome
INNER JOIN group_nome
ON         group_nome.id = nome.groupid
INNER JOIN vendor
ON         nome.vendorid = vendor.id
$where
ORDER BY   $sidx $sord
LIMIT      $start, $limit
TXT;
		try {
			$arr = DB::prepare($sql)->execute()->fetchAll();
			$i = 0;
			foreach ($arr as $row) {
				$responce->rows[$i]['id'] = $row['nomeid'];
				$ic = ($row['nomeactive'] == '1') ? 'fa-check-circle-o' : 'fa-ban';
				$responce->rows[$i]['cell'] = array(
						"<i class=\"fa $ic\" aria-hidden=\"true\"></i>",
						$row['nomeid'], $row['groupname'], $row['vendorname'], $row['nomename']
				);
				$i++;
			}
		} catch (PDOException $ex) {
			throw new DBException('Не могу выбрать список номенклатуры (2)', 0, $ex);
		}
		jsonExit($responce);
	}

	/* Для работы jqGrid (editurl) */
	function change() {
		$user = User::getInstance();
		$oper = PostDef('oper');
		$id = PostDef('id');
		$nomename = PostDef('nomename');
		switch ($oper) {
//			case 'add':
//				/* Проверяем может ли пользователь добавлять? */
//				($user->isAdmin() || $user->TestRights([1,4])) or die('Для добавления недостаточно прав');
//				$sql = 'INSERT INTO knt (id, name, comment, active) VALUES (null, :name, :comment, 1)';
//				try {
//					DB::prepare($sql)->execute([
//							':name' => $nomename,
//							':comment' => $comment
//					]);
//				} catch (PDOException $ex) {
//					throw new DBException('Не могу добавить пользователя', 0, $ex);
//				}
//				break;
			case 'edit':
				/* Проверяем может ли пользователь редактировать? */
				($user->isAdmin() || $user->TestRights([1,5])) or die('Для редактирования недостаточно прав');
				/* Есть ли уже такая запись? */
				$sql = 'SELECT COUNT(*) AS cnt FROM nome WHERE name = :name';
				try {
					$row = DB::prepare($sql)->execute([':name' => $nomename])->fetch();
					$count = ($row) ? $row['cnt'] : 0;
				} catch (PDOException $ex) {
					throw new DBException('Не могу обновить данные по номенклатуре (1)', 0, $ex);
				}
				if ($count == 0) {
					$sql = 'UPDATE nome SET name = :name WHERE id = :id';
					try {
						DB::prepare($sql)->execute([
								':name' => $nomename,
								':id' => $id
						]);
					} catch (PDOException $ex) {
						throw new DBException('Не могу обновить данные по номенклатуре (2)', 0, $ex);
					}
				}
				break;
			case 'del':
				/* Проверяем может ли пользователь удалять? */
				($user->isAdmin() || $user->TestRights([1,6])) or die('Для удаления недостаточно прав');
				$sql = 'UPDATE nome SET active = NOT active WHERE id = :id';
				try {
					DB::prepare($sql)->execute([':id' => $id]);
				} catch (PDOException $ex) {
					throw new DBException('Не могу пометить на удаление номенклатуру', 0, $ex);
				}
				break;
		}
	}

}
