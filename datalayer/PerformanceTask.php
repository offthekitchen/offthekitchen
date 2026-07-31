<?php
/*
*******************************************************************
PerformanceTask.php
Entity representing one row from the PERFORMANCE_TASK table.
*******************************************************************
*/

namespace Datalayer;

class PerformanceTask
{
	public ?int $id = null;
	public ?int $performanceId = null;
	public ?int $tourId = null;
	public ?string $description = null;
	public ?bool $complete = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['PERFORMANCE_TASK_ID']) ? (int) $row['PERFORMANCE_TASK_ID'] : null;
		$entity->description = $row['DESCRIPTION'] ?? null;
		$entity->performanceId = isset($row['PERFORMANCE_ID']) && $row['PERFORMANCE_ID'] !== null && $row['PERFORMANCE_ID'] !== ''
			? (int) $row['PERFORMANCE_ID']
			: null;
		$entity->tourId = isset($row['TOUR_ID']) && $row['TOUR_ID'] !== null && $row['TOUR_ID'] !== ''
			? (int) $row['TOUR_ID']
			: null;
		$entity->complete = isset($row['COMPLETE']) ? (bool) $row['COMPLETE'] : null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
