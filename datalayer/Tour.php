<?php
/*
*******************************************************************
Tour.php
Entity representing one row from the TOUR table.
*******************************************************************
*/

namespace Datalayer;

class Tour
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $startDate = null;
	public ?string $endDate = null;
	public ?string $notes = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['TOUR_ID']) ? (int) $row['TOUR_ID'] : null;
		$entity->name = $row['TOUR_NAME'] ?? null;
		$entity->startDate = $row['TOUR_START_DATE'] ?? null;
		$entity->endDate = $row['TOUR_END_DATE'] ?? null;
		$entity->notes = $row['NOTES'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
