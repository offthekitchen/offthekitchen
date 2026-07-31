<?php
/*
*******************************************************************
Mileage.php
Entity representing one row from the MILEAGE table.
*******************************************************************
*/

namespace Datalayer;

class Mileage
{
	public ?int $id = null;
	public ?int $mileage = null;
	public ?string $reason = null;
	public ?string $mileageDate = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['MILEAGE_ID']) ? (int) $row['MILEAGE_ID'] : null;
		$entity->mileage = isset($row['MILEAGE']) && $row['MILEAGE'] !== null && $row['MILEAGE'] !== ''
			? (int) $row['MILEAGE']
			: null;
		$entity->reason = $row['REASON'] ?? null;
		$entity->mileageDate = $row['MILEAGE_DATE'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
