<?php
/*
*******************************************************************
ThurdyDrop.php
Entity representing one row from the thurdy_drop table.
*******************************************************************
*/

namespace Datalayer;

class ThurdyDrop
{
	public ?int $id = null;
	public ?string $dropLocation = null;
	public ?string $dropDesc = null;
	public ?string $dropDate = null;
	public ?string $dropImage = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['DROP_ID']) ? (int) $row['DROP_ID'] : null;
		$entity->dropLocation = $row['DROP_LOCATION'] ?? null;
		$entity->dropDesc = $row['DROP_DESC'] ?? null;
		$entity->dropDate = $row['DROP_DATE'] ?? null;
		$entity->dropImage = $row['DROP_IMAGE'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
