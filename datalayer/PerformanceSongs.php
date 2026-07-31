<?php
/*
*******************************************************************
PerformanceSongs.php
Entity representing one row from the PERFORMANCE_SONGS table.
*******************************************************************
*/

namespace Datalayer;

class PerformanceSongs
{
	public ?int $id = null;
	public ?string $title = null;
	public ?string $artist = null;
	public ?string $tuning = null;
	public ?string $capo = null;
	public ?string $effect = null;
	public ?string $notes = null;
	public ?int $rating = null;
	public ?string $tabs = null;
	public ?string $demo = null;
	public ?bool $learned = null;
	public ?bool $popular = null;
	public ?bool $clean = null;
	public ?bool $original = null;
	public ?string $estimatedTime = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['PERFORMANCE_SONG_ID']) ? (int) $row['PERFORMANCE_SONG_ID'] : null;
		$entity->title = $row['TITLE'] ?? null;
		$entity->artist = $row['ARTIST'] ?? null;
		$entity->tuning = $row['TUNING'] ?? null;
		$entity->capo = $row['CAPO'] ?? null;
		$entity->learned = isset($row['LEARNED']) ? (bool) $row['LEARNED'] : null;
		$entity->tabs = $row['TABS'] ?? null;
		$entity->demo = $row['DEMO'] ?? null;
		$entity->clean = isset($row['CLEAN']) ? (bool) $row['CLEAN'] : null;
		$entity->popular = isset($row['POPULAR']) ? (bool) $row['POPULAR'] : null;
		$entity->original = isset($row['ORIGINAL']) ? (bool) $row['ORIGINAL'] : null;
		$entity->rating = isset($row['RATING']) && $row['RATING'] !== null && $row['RATING'] !== ''
			? (int) $row['RATING']
			: null;
		$entity->estimatedTime = $row['ESTIMATED_TIME'] ?? null;
		$entity->effect = $row['EFFECT'] ?? null;
		$entity->notes = $row['NOTES'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
