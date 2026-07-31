<?php
/*
*******************************************************************
RadioStation.php
Entity representing one row from the RADIO_STATION table.
*******************************************************************
*/

namespace Datalayer;

class RadioStation
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $frequency = null;
	public ?string $city = null;
	public ?string $state = null;
	public ?string $showName = null;
	public ?string $url = null;
	public ?string $showHost = null;
	public ?string $timeSlot = null;
	public ?string $requestEmail = null;
	public ?string $requestPhone = null;
	public ?bool $active = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['STATION_ID']) ? (int) $row['STATION_ID'] : null;
		$entity->name = $row['STATION_NAME'] ?? null;
		$entity->frequency = $row['FREQUENCY'] ?? null;
		$entity->city = $row['CITY'] ?? null;
		$entity->state = $row['STATE'] ?? null;
		$entity->showName = $row['SHOW_NAME'] ?? null;
		$entity->url = $row['URL'] ?? null;
		$entity->showHost = $row['SHOW_HOST'] ?? null;
		$entity->timeSlot = $row['TIME_SLOT'] ?? null;
		$entity->requestEmail = $row['REQUEST_EMAIL'] ?? null;
		$entity->requestPhone = $row['REQUEST_PHONE'] ?? null;
		$entity->active = isset($row['ACTIVE']) ? (bool) $row['ACTIVE'] : null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
