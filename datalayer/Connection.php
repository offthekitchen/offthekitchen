<?php
/*
*******************************************************************
Connection.php
Shared PDO connection for the new datalayer.
Uses DB_HOST, DB_LOGIN, DB_PASSWORD, and DB_NAME from site settings.
*******************************************************************
*/

namespace Datalayer;

use PDO;
use PDOException;

class Connection
{
	private static ?PDO $pdo = null;

	/**
	 * Returns a shared PDO connection (created once per request).
	 */
	public static function getPdo(): PDO
	{
		if (self::$pdo === null) {
			$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

			try {
				self::$pdo = new PDO($dsn, DB_LOGIN, DB_PASSWORD, [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false,
				]);
			} catch (PDOException $e) {
				error_log('DATALAYER DB001 - Failed to connect: ' . $e->getMessage());
				throw $e;
			}
		}

		return self::$pdo;
	}

	/**
	 * Closes the shared connection (mainly for tests / long-running scripts).
	 */
	public static function reset(): void
	{
		self::$pdo = null;
	}
}
