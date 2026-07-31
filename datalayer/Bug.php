<?php
/*
*******************************************************************
Bug.php
Entity representing one row from the BUG table.
*******************************************************************
*/

namespace Datalayer;

class Bug
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $nickName = null;
	public ?string $position = null;
	public ?string $cacheName = null;
	public ?string $cacheId = null;
	public ?string $trackingNumber = null;
	public ?int $trackableId = null;
	public ?string $referenceNumber = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['BUG_ID']) ? (int) $row['BUG_ID'] : null;
		$entity->name = $row['BUG_NAME'] ?? null;
		$entity->nickName = $row['NICKNAME'] ?? null;
		$entity->position = $row['POSITION'] ?? null;
		$entity->cacheName = $row['CACHE_NAME'] ?? null;
		$entity->cacheId = $row['CACHE_ID'] ?? null;
		$entity->trackingNumber = $row['TRACKING_NUMBER'] ?? null;
		$entity->trackableId = isset($row['TRACKABLE_ID']) && $row['TRACKABLE_ID'] !== null && $row['TRACKABLE_ID'] !== ''
			? (int) $row['TRACKABLE_ID']
			: null;
		$entity->referenceNumber = $row['REFERENCE_NUMBER'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
