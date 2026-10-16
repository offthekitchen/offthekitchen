<?php
/*
*******************************************************************
RevenueType.php
Entity representing one row from the REVENUE_TYPE table.
*******************************************************************
*/

namespace Datalayer;

class RevenueType
{
	public ?int $id = null;
	public ?string $name = null;
	/** @var int|null 1 = active, 0 = inactive */
	public ?int $active = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['REVENUE_TYPE_ID']) ? (int) $row['REVENUE_TYPE_ID'] : null;
		$entity->name = $row['REVENUE_TYPE_NAME'] ?? null;
		$entity->active = isset($row['ACTIVE']) ? (int) $row['ACTIVE'] : 0;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
