<?php

/*
 * WebUseOrg3 Lite - учёт оргтехники в организации
 * Лицензия: GPL-3.0
 * Разработчики:
 *   Грибов Павел,
 *   Сергей Солодягин (solodyagin@gmail.com)
 * Сайт: http://грибовы.рф
 */

// Запрещаем прямой вызов скрипта.
defined('WUO') or die('Доступ запрещён');

class BaseUser {

	public $id; // Идентификатор пользователя
	public $randomid; // Случайный идентификатор (время от времени может меняться)
	public $orgid; // Принадлежность к организации
	public $login; // Логин
	public $password; // Хешированный пароль
	public $salt; // Соль для хеширования пароля
	public $email; // Электронная почта
	public $mode; // 0 - пользователь 1 - администратор
	public $lastdt; // Дата и время последнего посещения
	public $active; // 1 - не помечен на удаление
	public $fio; // Фамилия, Имя, Отчество
	public $telephonenumber; // Номер телефона (сотовый)
	public $homephone; // Номер телефона (альтернатива)
	public $jpegphoto; // Фотография из папки photos
	public $post; // Должность

	/**
	 * Проверяем соответствие роли
	 *
	 * Роли:
	 * http://грибовы.рф/wiki/doku.php/основы:доступ:роли
	 *            1="Полный доступ"
	 *            2="Просмотр финансовых отчетов"
	 *            3="Просмотр"
	 *            4="Добавление"
	 *            5="Редактирование"
	 *            6="Удаление"
	 *            7="Отправка СМС"
	 *            8="Манипуляции с деньгами"
	 *            9="Редактирование карт"
	 *
	 * @param string $roles
	 * @return boolean
	 */

	function TestRoles($roles) {
		$sql = "SELECT COUNT(*) FROM usersroles WHERE userid = :id AND role IN ($roles)";
		try {
			return (DB::prepare($sql)->execute(array(':id' => $this->id))->fetchColumn() > 0);
		} catch (PDOException $ex) {
			throw new DBException('Ошибка выполнения User.TestRoles', 0, $ex);
		}
	}

	/**
	 * Обновляем данные о последнем посещении
	 * @param type $id
	 */
	function UpdateLastdt($id) {
		$lastdt = date('Y-m-d H:i:s');
		$sql = 'UPDATE `users` SET `lastdt` = :lastdt WHERE `id` = :id';
		try {
			DB::prepare($sql)->execute(array(':lastdt' => $lastdt, ':id' => $id));
		} catch (PDOException $ex) {
			throw new DBException('Ошибка выполнения User.UpdateLastdt', 0, $ex);
		}
	}

	/**
	 * Обновляем данные о текущем пользователе в базу
	 */
	function Update() {
		try {
			$sql = <<<TXT
UPDATE	`users`
SET		`orgid` = :orgid, `login` = :login, `password` = :password, `salt` = :salt,
		`email` = :email, `mode` = :mode, `active` = :active
WHERE	`id` = :id
TXT;
			DB::prepare($sql)->execute(array(
				':orgid' => $this->orgid,
				':login' => $this->login,
				':password' => $this->password,
				':salt' => $this->salt,
				':email' => $this->email,
				':mode' => $this->mode,
				':active' => $this->active,
				':id' => $this->id
			));
		} catch (PDOException $ex) {
			throw new DBException('Ошибка выполнения User.Update (1)', 0, $ex);
		}
		try {
			$sql = <<<TXT
INSERT INTO `users_profile` (`usersid`, `fio`, `telephonenumber`, `homephone`, `jpegphoto`, `post`)
VALUES (:usersid, :fio, :telephonenumber, :homephone, :jpegphoto, :post)
ON DUPLICATE KEY UPDATE
	`fio` = :fio,
	`telephonenumber` = :telephonenumber,
	`homephone` = :homephone,
	`jpegphoto` = :jpegphoto,
	`post` = :post
TXT;
			DB::prepare($sql)->execute(array(
				':usersid' => $this->id,
				':fio' => $this->fio,
				':telephonenumber' => $this->telephonenumber,
				':homephone' => $this->homephone,
				':jpegphoto' => $this->jpegphoto,
				':post' => $this->post
			));
		} catch (PDOException $ex) {
			throw new DBException('Ошибка выполнения User.Update (2)', 0, $ex);
		}
	}

	/**
	 * Получает данные о пользователе из базы
	 * @param string $where
	 * @param array $params
	 * @return boolean
	 */
	function select($where, $params) {
		try {
			$row = DB::prepare(<<<SQL
SELECT	p.*,
		u.*,
		u.`id` sid
FROM	`users` u
	LEFT JOIN `users_profile` p
		ON p.`usersid` = u.`id`
WHERE	{$where}
SQL
					)->execute($params)->fetch();
			if ($row) {
				$this->id = $row['sid'];
				$this->randomid = $row['randomid'];
				$this->orgid = $row['orgid'];
				$this->login = $row['login'];
				$this->password = $row['password'];
				$this->salt = $row['salt'];
				$this->email = $row['email'];
				$this->mode = $row['mode'];
				$this->lastdt = $row['lastdt'];
				$this->active = $row['active'];
				$this->telephonenumber = $row['telephonenumber'];
				$this->jpegphoto = $row['jpegphoto'];
				$this->homephone = $row['homephone'];
				$this->fio = $row['fio'];
				$this->post = $row['post'];
				return true;
			}
		} catch (PDOException $ex) {
			throw new DBException('Ошибка при получении данных пользователя', 0, $ex);
		}
		return false;
	}

	/**
	 * Получить данные о пользователе по логину
	 * @param string $login
	 * @return boolean
	 */
	function getByLogin($login) {
		return $this->select('u.`login` = :login', array(':login' => $login));
	}

	/**
	 * Получить данные о пользователе по идентификатору
	 * @param integer $id
	 * @return boolean
	 */
	function getById($id) {
		return $this->select('u.`id` = :id', array(':id' => $id));
	}

	/**
	 * Получить данные о пользователе по идентификатору randomid
	 * @param string $randomid
	 * @return boolean
	 */
	function getByRandomId($randomid) {
		return $this->select('u.`randomid` = :randomid', array(':randomid' => $randomid));
	}

	/**
	 * Получить данные о пользователе по идентификатору. БЕЗ ПРОФИЛЯ
	 * @param type $randomid
	 * @return boolean
	 */
	function getByRandomIdNoProfile($randomid) {
		$sql = 'SELECT * FROM `users` WHERE `randomid` = :randomid';
		try {
			$row = DB::prepare($sql)->execute(array(':randomid' => $randomid))->fetch();
			if ($row) {
				$this->id = $row['id'];
				$this->randomid = $row['randomid'];
				$this->orgid = $row['orgid'];
				$this->login = $row['login'];
				$this->password = $row['password'];
				$this->salt = $row['salt'];
				$this->email = $row['email'];
				$this->mode = $row['mode'];
				$this->lastdt = $row['lastdt'];
				$this->active = $row['active'];
				return true;
			}
		} catch (PDOException $ex) {
			throw new DBException('Ошибка выполнения User.getByRandomIdNoProfile', 0, $ex);
		}
		return false;
	}

	/**
	 * Добавляем пользователя с текущими данными
	 * @param string $randomid
	 * @param string $orgid
	 * @param string $login
	 * @param string $pass Открытый пароль
	 * @param string $email
	 * @param string $mode
	 */
	function Add($randomid, $orgid, $login, $pass, $email, $mode) {
		$this->randomid = $randomid;
		$this->orgid = $orgid;
		$this->login = $login;
		// Хешируем пароль
		$this->salt = generateSalt();
		$this->password = sha1(sha1($pass) . $this->salt);
		$this->email = $email;
		$this->mode = $mode;
		$sql = <<<TXT
INSERT INTO `users`
		(`randomid`, `orgid`, `login`, `password`, `salt`, `email`, `mode`, `lastdt`, `active`)
VALUES	(:randomid, :orgid, :login, :password, :salt, :email, :mode, NOW(), 1)
TXT;
		try {
			DB::prepare($sql)->execute(array(
				':randomid' => $this->randomid,
				':orgid' => $this->orgid,
				':login' => $this->login,
				':password' => $this->password,
				':salt' => $this->salt,
				':email' => $this->email,
				':mode' => $this->mode
			));
		} catch (PDOException $ex) {
			throw new DBException('Ошибка выполнения User.Add', 0, $ex);
		}

		$zx = new BaseUser();

		if ($zx->getByRandomIdNoProfile($this->randomid)) {
			// добавляю профиль
			$sql = <<<TXT
INSERT INTO `users_profile`
		(`usersid`, `fio`, `telephonenumber`, `homephone`, `jpegphoto`, `post`)
VALUES	(:userid, :fio, :telephonenumber, :homephone, :jpegphoto, :post)
TXT;
			try {
				DB::prepare($sql)->execute(array(
					':userid' => $zx->id,
					':fio' => $this->fio,
					':telephonenumber' => $this->telephonenumber,
					':homephone' => $this->homephone,
					':jpegphoto' => $this->jpegphoto,
					':post' => $this->post
				));
			} catch (PDOException $ex) {
				throw new DBException('Ошибка выполнения User.Add', 0, $ex);
			}
		} else {
			die('Не найден пользователь по randomid User.Add');
		}
		unset($zx);
	}

	function isAdmin() {
		return ($this->mode == 1);
	}

}