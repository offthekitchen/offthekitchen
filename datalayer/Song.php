<?php
/*
*******************************************************************
Song.php
Entity representing one row from the SONG table.
*******************************************************************
*/

namespace Datalayer;

class Song
{
	public ?int $id = null;
	public ?int $cdId = null;
	public int $trackNumber = 0;
	public ?string $name = null;
	public ?string $image = null;
	public ?string $thumbnail = null;
	public ?string $description = null;
	public ?string $purchaseLink = null;
	public ?string $lyricsHtml = null;
	public ?string $sampleMp3 = null;
	public ?string $releaseDate = null;
	public ?string $runTime = null;
	public ?string $isrc = null;
	public ?string $catalogNumber = null;
	public ?string $upc = null;
	public ?bool $active = null;
	public ?bool $displayOnSite = null;
	public int $bmiNumber = 0;
	public ?int $artistId = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['SONG_ID']) ? (int) $row['SONG_ID'] : null;
		$entity->cdId = isset($row['CD_ID']) && $row['CD_ID'] !== null && $row['CD_ID'] !== ''
			? (int) $row['CD_ID']
			: null;
		$entity->trackNumber = isset($row['TRACK_NUMBER']) ? (int) $row['TRACK_NUMBER'] : 0;
		$entity->name = $row['SONG_NAME'] ?? null;
		$entity->image = $row['SONG_IMAGE'] ?? null;
		$entity->thumbnail = $row['SONG_THUMBNAIL'] ?? null;
		$entity->description = $row['SONG_DESCRIPTION'] ?? null;
		$entity->purchaseLink = $row['PURCHASE_LINK'] ?? null;
		$entity->lyricsHtml = $row['LYRICS_HTML'] ?? null;
		$entity->sampleMp3 = $row['SAMPLE_MP3'] ?? null;
		$entity->releaseDate = $row['RELEASE_DATE'] ?? null;
		$entity->runTime = $row['RUN_TIME'] ?? null;
		$entity->isrc = $row['ISRC'] ?? null;
		$entity->catalogNumber = $row['CATALOG_NUMBER'] ?? null;
		$entity->upc = $row['UPC'] ?? null;
		$entity->active = isset($row['ACTIVE']) ? (bool) $row['ACTIVE'] : null;
		$entity->displayOnSite = isset($row['DISPLAY_ON_SITE']) ? (bool) $row['DISPLAY_ON_SITE'] : null;
		$entity->bmiNumber = isset($row['BMI_NUMBER']) ? (int) $row['BMI_NUMBER'] : 0;
		$entity->artistId = isset($row['ARTIST_ID']) && $row['ARTIST_ID'] !== null && $row['ARTIST_ID'] !== ''
			? (int) $row['ARTIST_ID']
			: null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
