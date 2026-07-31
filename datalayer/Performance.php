<?php
/*
*******************************************************************
Performance.php
Entity representing one row from the PERFORMANCE table.
*******************************************************************
*/

namespace Datalayer;

class Performance
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $website = null;
	public ?string $performanceDate = null;
	public ?string $performanceTime = null;
	public ?string $location = null;
	public ?string $locationWebsite = null;
	public ?string $locationAddr1 = null;
	public ?string $locationAddr2 = null;
	public ?string $locationCity = null;
	public ?string $locationState = null;
	public ?string $locationZip = null;
	public ?string $description = null;
	public ?string $admission = null;
	public ?int $tourId = null;
	public ?int $artistId = null;
	public ?string $contactName = null;
	public ?string $contactEmail = null;
	public ?string $contactPhone = null;
	public ?string $photoAlbum = null;
	public ?string $videoUrl = null;
	public ?string $contract = null;
	public ?string $airfare = null;
	public ?string $hotel = null;
	public ?string $rentalCar = null;
	public ?bool $preRecorded = null;
	public ?bool $adultShow = null;
	public ?bool $coloradoSessions = null;
	public ?string $bookedDate = null;
	public ?float $bookedAmount = null;
	public ?string $notes = null;
	public ?string $lastUpdate = null;
	public ?bool $booked = null;
	public ?int $performanceYear = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['PERFORMANCE_ID']) ? (int) $row['PERFORMANCE_ID'] : null;
		$entity->name = $row['PERFORMANCE_NAME'] ?? null;
		$entity->website = $row['PERFORMANCE_WEBSITE'] ?? null;
		$entity->performanceDate = $row['PERFORMANCE_DATE'] ?? null;
		$entity->performanceTime = $row['PERFORMANCE_TIME'] ?? null;
		$entity->location = $row['LOCATION'] ?? null;
		$entity->locationWebsite = $row['LOCATION_WEBSITE'] ?? null;
		$entity->locationAddr1 = $row['LOCATION_ADDR1'] ?? null;
		$entity->locationAddr2 = $row['LOCATION_ADDR2'] ?? null;
		$entity->locationCity = $row['LOCATION_CITY'] ?? null;
		$entity->locationState = $row['LOCATION_STATE'] ?? null;
		$entity->locationZip = $row['LOCATION_ZIP'] ?? null;
		$entity->description = $row['DESCRIPTION'] ?? null;
		$entity->admission = $row['ADMISSION'] ?? null;
		$entity->tourId = isset($row['TOUR_ID']) && $row['TOUR_ID'] !== null && $row['TOUR_ID'] !== ''
			? (int) $row['TOUR_ID']
			: null;
		$entity->artistId = isset($row['ARTIST_ID']) && $row['ARTIST_ID'] !== null && $row['ARTIST_ID'] !== ''
			? (int) $row['ARTIST_ID']
			: null;
		$entity->contactName = $row['CONTACT_NAME'] ?? null;
		$entity->contactEmail = $row['CONTACT_EMAIL'] ?? null;
		$entity->contactPhone = $row['CONTACT_PHONE'] ?? null;
		$entity->photoAlbum = $row['PHOTO_ALBUM'] ?? null;
		$entity->videoUrl = $row['VIDEO_URL'] ?? null;
		$entity->contract = $row['CONTRACT'] ?? null;
		$entity->airfare = $row['AIRFARE'] ?? null;
		$entity->hotel = $row['HOTEL'] ?? null;
		$entity->rentalCar = $row['RENTAL_CAR'] ?? null;
		$entity->preRecorded = isset($row['PRE_RECORDED']) ? (bool) $row['PRE_RECORDED'] : null;
		$entity->adultShow = isset($row['ADULT_SHOW']) ? (bool) $row['ADULT_SHOW'] : null;
		$entity->coloradoSessions = isset($row['COLORADO_SESSIONS']) ? (bool) $row['COLORADO_SESSIONS'] : null;
		$entity->bookedDate = $row['BOOKED_DATE'] ?? null;
		$entity->bookedAmount = isset($row['BOOKED_AMOUNT']) && $row['BOOKED_AMOUNT'] !== null && $row['BOOKED_AMOUNT'] !== ''
			? (float) $row['BOOKED_AMOUNT']
			: null;
		$entity->notes = $row['NOTES'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;

		$entity->booked = !empty($entity->bookedDate) && $entity->bookedDate > '0000-00-00';
		$entity->performanceYear = !empty($entity->performanceDate)
			? (int) substr($entity->performanceDate, 0, 4)
			: null;

		return $entity;
	}
}
