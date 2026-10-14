<?php
/*
*******************************************************************
Vendor.php
Entity representing one row from the VENDOR table.
*******************************************************************
*/

namespace Datalayer;

class Vendor
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
		$entity->id = isset($row['VENDOR_ID']) ? (int) $row['VENDOR_ID'] : null;
		$entity->name = $row['VENDOR_NAME'] ?? null;
		$entity->active = isset($row['ACTIVE']) ? (int) $row['ACTIVE'] : 0;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
