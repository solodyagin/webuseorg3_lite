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

namespace app\controllers;

use PDO;
use PDOException;
use stdClass;
use core\controller;
use core\request;
use core\user;
use core\db;
use core\dbexception;
use core\utils;

class nomegroups extends controller {

	function index() {
		$data['section'] = 'Справочники / Группы номенклатуры';
		$user = user::getInstance();
		if ($user->isAdmin() || $user->testRights([1, 4, 5, 6])) {
			$this->view->renderTemplate('nomegroups/index', $data);
		} else {
			$this->view->renderTemplate('restricted', $data);
		}
	}

	/** Для работы jqGrid */
	function list() {
		$user = user::getInstance();
		// Проверяем: может ли пользователь просматривать?
		($user->isAdmin() || $user->testRights([1, 3, 4, 5, 6])) or die('Недостаточно прав');
		$req = request::getInstance();
		$page = $req->get('page', 1);
		if ($page == 0) {
			$page = 1;
		}
		$limit = $req->get('rows');
		$sidx = $req->get('sidx', '1');
		$sord = $req->get('sord');
		// Готовим ответ
		$responce = new stdClass();
		$responce->page = 0;
		$responce->total = 0;
		$responce->records = 0;
		try {
			$sql = 'select count(*) cnt from group_nome';
			$row = db::prepare($sql)->execute()->fetch();
			$count = ($row) ? $row['cnt'] : 0;
		} catch (PDOException $ex) {
			throw new dbexception('Не могу выбрать список групп (1)', 0, $ex);
		}
		if ($count == 0) {
			utils::jsonExit($responce);
		}
		$total_pages = ceil($count / $limit);
		if ($page > $total_pages) {
			$page = $total_pages;
		}
		$start = $limit * $page - $limit;
		if ($start < 0) {
			utils::jsonExit($responce);
		}
		$responce->page = $page;
		$responce->total = $total_pages;
		$responce->records = $count;
		try {
			switch (db::getAttribute(PDO::ATTR_DRIVER_NAME)) {
				case 'mysql':
					$sql = "select id, name, comment, active from group_nome order by $sidx $sord limit $start, $limit";
					break;
				case 'pgsql':
					$sql = "select id, name, comment, active from group_nome order by $sidx $sord offset $start limit $limit";
					break;
			}
			$arr = db::prepare($sql)->execute()->fetchAll();
			$i = 0;
			foreach ($arr as $row) {
				$responce->rows[$i]['id'] = $row['id'];
				$ic = ($row['active'] == '1') ? 'fa-check-circle' : 'fa-ban';
				$responce->rows[$i]['cell'] = [
					"<i class=\"fas $ic\"></i>",
					$row['id'], $row['name'], $row['comment']
				];
				$i++;
			}
		} catch (PDOException $ex) {
			throw new dbexception('Не могу выбрать список групп (2)', 0, $ex);
		}
		utils::jsonExit($responce);
	}

	/** Для работы jqGrid (editurl) */
	function change() {
		$user = user::getInstance();
		$req = request::getInstance();
		$oper = $req->get('oper');
		$id = $req->get('id');
		$name = $req->get('name');
		$comment = $req->get('comment');
		switch ($oper) {
			case 'add':
				// Проверяем: может ли пользователь добавлять?
				($user->isAdmin() || $user->testRights([1, 4])) or die('Недостаточно прав');
				try {
					$sql = 'insert into group_nome (name, comment, active) values (:name, :comment, 1)';
					db::prepare($sql)->execute([
						':name' => $name,
						':comment' => $comment
					]);
				} catch (PDOException $ex) {
					throw new dbexception('Не могу добавить группу', 0, $ex);
				}
				break;
			case 'edit':
				// Проверяем: может ли пользователь редактировать?
				($user->isAdmin() || $user->testRights([1, 5])) or die('Недостаточно прав');
				try {
					$sql = 'update group_nome set name = :name, comment = :comment where id = :id';
					db::prepare($sql)->execute([':name' => $name, ':comment' => $comment, ':id' => $id]);
				} catch (PDOException $ex) {
					throw new dbexception('Не могу обновить данные по группе', 0, $ex);
				}
				break;
			case 'del':
				// Проверяем: может ли пользователь удалять?
				($user->isAdmin() || $user->testRights([1, 6])) or die('Недостаточно прав');
				try {
					switch (db::getAttribute(PDO::ATTR_DRIVER_NAME)) {
						case 'mysql':
							$sql = 'update group_nome set active = not active where id = :id';
							break;
						case 'pgsql':
							$sql = 'update group_nome set active = active # 1 where id = :id';
							break;
					}
					db::prepare($sql)->execute([':id' => $id]);
				} catch (PDOException $ex) {
					throw new dbexception('Не могу пометить на удаление группу', 0, $ex);
				}
				try {
					$sql = <<<TXT
update group_param
set active = (select active from group_nome where id = :id)
where groupid = :id
TXT;
					db::prepare($sql)->execute([':id' => $id]);
				} catch (PDOException $ex) {
					throw new dbexception('Не могу обновить данные по группе', 0, $ex);
				}
				break;
		}
	}

}
