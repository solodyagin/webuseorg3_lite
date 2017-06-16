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

class User extends BaseUser {

	use Singleton;

	// Пользователь вошёл ?
	private $is_logged = false;

	/**
	 * Аутентификация SQL
	 * @param string $login
	 * @param string $password
	 * @return boolean
	 */
	function loginByDB($login, $password) {
		$this->is_logged = false;
		$sql = <<<TXT
SELECT	p.*, u.*, u.`id` sid
FROM	`users` u
	LEFT JOIN `users_profile` p
		ON p.`usersid` = u.`id`
WHERE	u.`login` = :login AND
		u.`password` = SHA1(CONCAT(SHA1(:pass), u.`salt`))
TXT;
		try {
			$row = DB::prepare($sql)->execute(array(':login' => $login, ':pass' => $password))->fetch();
			if ($row) {
				$this->is_logged = true;
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
			}
		} catch (PDOException $ex) {
			throw new DBException('Ошибка при получении данных пользователя', 0, $ex);
		}
		// Устанавливаем Cookie
		if ($this->is_logged) {
			setcookie('user_randomid_w3', $this->randomid, strtotime('+30 days'), '/');
		}
		return $this->is_logged;
	}

	/**
	 * Аутентифицирует по кукам
	 * @return boolean
	 */
	function loginByCookie() {
		$this->randomid = filter_input(INPUT_COOKIE, 'user_randomid_w3');
		$this->is_logged = !empty($this->randomid) && $this->getByRandomId($this->randomid);
		if ($this->is_logged) {
			$this->UpdateLastdt($this->id); // Обновляем дату последнего входа пользователя
			setcookie('user_randomid_w3', $this->randomid, strtotime('+30 days'), '/'); // Устанавливаем Cookie
		} else {
			setcookie('user_randomid_w3', '', 1, '/'); // Удаляем cookie
		}
		return $this->is_logged;
	}

	function logout() {
		$this->is_logged = false;
		$user->id = '';
		$user->randomid = '';
		// Удаляем cookie
		setcookie('user_randomid_w3', '', 1, '/');
//		foreach ($_COOKIE as $key => $value) {
//			setcookie($key, '', 1, '/');
//		}
	}

	/**
	 * Пользователь аутентифицирован?
	 * @return boolean
	 */
	function isLogged() {
		return ($this->is_logged);
	}

}