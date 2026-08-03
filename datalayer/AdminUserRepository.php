<?php
/*
*******************************************************************
AdminUserRepository.php
Query and persistence for the ADMIN_USER table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class AdminUserRepository
{
	private const SELECT_COLUMNS = 'ADMIN_USER_ID, USERNAME, PASSWORD_HASH, IS_ACTIVE, CREATED_AT, LAST_LOGIN';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	public function findById(int $id): ?AdminUser
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM ADMIN_USER
		         WHERE ADMIN_USER_ID = :id';
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();
		return $row ? AdminUser::fromRow($row) : null;
	}

	public function findByUsername(string $username): ?AdminUser
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM ADMIN_USER
		         WHERE USERNAME = :username';
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':username' => $username]);
		$row = $stmt->fetch();
		return $row ? AdminUser::fromRow($row) : null;
	}

	/**
	 * @return AdminUser[]
	 */
	public function findAll(): array
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM ADMIN_USER
		         ORDER BY USERNAME';
		$stmt = $this->db->query($sql);
		return $this->hydrateAll($stmt);
	}

	public function countAll(): int
	{
		return (int) $this->db->query('SELECT COUNT(*) FROM ADMIN_USER')->fetchColumn();
	}

	public function insert(AdminUser $entity): bool
	{
		$sql = 'INSERT INTO ADMIN_USER (USERNAME, PASSWORD_HASH, IS_ACTIVE, CREATED_AT)
		        VALUES (:username, :passwordHash, :isActive, NOW())';
		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([
			':username' => $entity->username,
			':passwordHash' => $entity->passwordHash,
			':isActive' => $entity->isActive ? 1 : 0,
		]);
		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	public function update(AdminUser $entity): bool
	{
		$sql = 'UPDATE ADMIN_USER
		           SET USERNAME = :username,
		               PASSWORD_HASH = :passwordHash,
		               IS_ACTIVE = :isActive
		         WHERE ADMIN_USER_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':username' => $entity->username,
			':passwordHash' => $entity->passwordHash,
			':isActive' => $entity->isActive ? 1 : 0,
			':id' => $entity->id,
		]);
	}

	public function updatePassword(int $id, string $passwordHash): bool
	{
		$sql = 'UPDATE ADMIN_USER
		           SET PASSWORD_HASH = :passwordHash
		         WHERE ADMIN_USER_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':passwordHash' => $passwordHash,
			':id' => $id,
		]);
	}

	public function setActive(int $id, bool $isActive): bool
	{
		$sql = 'UPDATE ADMIN_USER
		           SET IS_ACTIVE = :isActive
		         WHERE ADMIN_USER_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':isActive' => $isActive ? 1 : 0,
			':id' => $id,
		]);
	}

	public function recordLogin(int $id): bool
	{
		$sql = 'UPDATE ADMIN_USER
		           SET LAST_LOGIN = NOW()
		         WHERE ADMIN_USER_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM ADMIN_USER WHERE ADMIN_USER_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return AdminUser[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$entities = [];
		while ($row = $stmt->fetch()) {
			$entities[] = AdminUser::fromRow($row);
		}
		return $entities;
	}
}
