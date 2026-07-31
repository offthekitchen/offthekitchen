<?php
/*
*******************************************************************
Award.php
Entity representing one row from the AWARD table.
*******************************************************************
*/

namespace Datalayer;

class Award
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $description = null;
	public ?string $awardDate = null;
	public ?string $url = null;
	public ?int $artistId = null;
	public ?int $cdId = null;
	public ?int $songId = null;
	public ?bool $performanceRelated = null;
	public ?string $image = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['AWARD_ID']) ? (int) $row['AWARD_ID'] : null;
		$entity->name = $row['AWARD_NAME'] ?? null;
		$entity->description = $row['AWARD_DESCRIPTION'] ?? null;
		$entity->awardDate = $row['AWARD_DATE'] ?? null;
		$entity->url = $row['AWARD_URL'] ?? null;
		$entity->artistId = isset($row['ARTIST_ID']) && $row['ARTIST_ID'] !== null && $row['ARTIST_ID'] !== ''
			? (int) $row['ARTIST_ID']
			: null;
		$entity->cdId = isset($row['CD_ID']) && $row['CD_ID'] !== null && $row['CD_ID'] !== ''
			? (int) $row['CD_ID']
			: null;
		$entity->songId = isset($row['SONG_ID']) && $row['SONG_ID'] !== null && $row['SONG_ID'] !== ''
			? (int) $row['SONG_ID']
			: null;
		$entity->performanceRelated = isset($row['PERFORMANCE_RELATED']) ? (bool) $row['PERFORMANCE_RELATED'] : null;
		$entity->image = $row['AWARD_IMAGE'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
