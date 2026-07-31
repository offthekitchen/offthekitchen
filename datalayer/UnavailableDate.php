<?php
/*
*******************************************************************
UnavailableDate.php
Entity representing one row from the UNAVAILABLE_DATES table.
*******************************************************************
*/

namespace Datalayer;

class UnavailableDate
{
	public ?int $id = null;
	public ?string $unavailableDate = null;
	public ?string $reason = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['UNAVAILABLE_ID']) ? (int) $row['UNAVAILABLE_ID'] : null;
		$entity->unavailableDate = $row['UNAVAILABLE_DATE'] ?? null;
		$entity->reason = $row['REASON'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
