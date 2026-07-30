<?php
/*
*******************************************************************
CD.php
Entity representing one row from the CD table.
*******************************************************************
*/

namespace Datalayer;

class CD
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $shortName = null;
	public ?string $image = null;
	public ?string $thumbnail = null;
	public ?string $description = null;
	public ?string $releaseDate = null;
	public ?string $upc = null;
	public ?string $runTime = null;
	public int $artistId = 0;
	public ?string $purchaseLink = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['CD_ID']) ? (int) $row['CD_ID'] : null;
		$entity->name = $row['CD_NAME'] ?? null;
		$entity->shortName = $row['CD_SHORT_NAME'] ?? null;
		$entity->image = $row['CD_IMAGE'] ?? null;
		$entity->thumbnail = $row['CD_THUMBNAIL'] ?? null;
		$entity->description = $row['CD_DESCRIPTION'] ?? null;
		$entity->releaseDate = $row['RELEASE_DATE'] ?? null;
		$entity->upc = $row['UPC'] ?? null;
		$entity->runTime = $row['RUN_TIME'] ?? null;
		$entity->artistId = isset($row['ARTIST_ID']) ? (int) $row['ARTIST_ID'] : 0;
		$entity->purchaseLink = $row['PURCHASE_LINK'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
