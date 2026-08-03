<?php
/*
*******************************************************************
AdminUser.php
Entity representing one row from the ADMIN_USER table.
*******************************************************************
*/

namespace Datalayer;

class AdminUser
{
	public ?int $id = null;
	public ?string $username = null;
	public ?string $passwordHash = null;
	public ?bool $isActive = null;
	public ?string $createdAt = null;
	public ?string $lastLogin = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['ADMIN_USER_ID']) ? (int) $row['ADMIN_USER_ID'] : null;
		$entity->username = $row['USERNAME'] ?? null;
		$entity->passwordHash = $row['PASSWORD_HASH'] ?? null;
		$entity->isActive = isset($row['IS_ACTIVE']) ? (bool) $row['IS_ACTIVE'] : null;
		$entity->createdAt = $row['CREATED_AT'] ?? null;
		$entity->lastLogin = $row['LAST_LOGIN'] ?? null;
		return $entity;
	}
}
