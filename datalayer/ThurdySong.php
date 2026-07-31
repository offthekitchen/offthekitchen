<?php
/*
*******************************************************************
ThurdySong.php
Entity representing one row from the thurdy_song table.
*******************************************************************
*/

namespace Datalayer;

class ThurdySong
{
	public ?int $songNumber = null;
	public ?string $songTitle = null;
	public ?string $songDesc = null;
	public ?string $songMp3 = null;
	public ?string $videoLink = null;
	public ?string $videoSubmittedBy = null;
	public ?string $videoDate = null;
	public ?string $liveVersion = null;
	public ?string $artImage = null;
	public ?string $dropImage = null;
	public ?string $dropLocation = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->songNumber = isset($row['SONG_NUMBER']) ? (int) $row['SONG_NUMBER'] : null;
		$entity->songTitle = $row['SONG_TITLE'] ?? null;
		$entity->songDesc = $row['SONG_DESC'] ?? null;
		$entity->songMp3 = $row['SONG_MP3'] ?? null;
		$entity->videoLink = $row['VIDEO_LINK'] ?? null;
		$entity->videoSubmittedBy = $row['VIDEO_SUBMITTED_BY'] ?? null;
		$entity->videoDate = $row['VIDEO_DATE'] ?? null;
		$entity->liveVersion = $row['LIVE_VERSION'] ?? null;
		$entity->artImage = $row['ART_IMAGE'] ?? null;
		$entity->dropImage = $row['DROP_IMAGE'] ?? null;
		$entity->dropLocation = $row['DROP_LOCATION'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
