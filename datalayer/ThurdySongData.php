<?php
/*
*******************************************************************
ThurdySongData.php
Entity representing one row from the thurdy_song_data table.
*******************************************************************
*/

namespace Datalayer;

class ThurdySongData
{
	public ?int $id = null;
	public ?int $songId = null;
	public ?string $videoLink = null;
	public ?string $videoSubmittedBy = null;
	public ?string $liveVersion = null;
	public ?int $dropId = null;
	public ?string $artImage = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['THURDY_SONG_DATA_ID']) ? (int) $row['THURDY_SONG_DATA_ID'] : null;
		$entity->songId = isset($row['SONG_ID']) ? (int) $row['SONG_ID'] : null;
		$entity->videoLink = $row['VIDEO_LINK'] ?? null;
		$entity->videoSubmittedBy = $row['VIDEO_SUBMITTED_BY'] ?? null;
		$entity->liveVersion = $row['LIVE_VERSION'] ?? null;
		$entity->dropId = isset($row['DROP_ID']) && $row['DROP_ID'] !== null && $row['DROP_ID'] !== ''
			? (int) $row['DROP_ID']
			: null;
		$entity->artImage = $row['ART_IMAGE'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATED'] ?? null;
		return $entity;
	}
}
