<?php
/*
*******************************************************************
Artist.php
Entity representing one row from the ARTIST table.
*******************************************************************
*/

namespace Datalayer;

class Artist
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $image = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['ARTIST_ID']) ? (int) $row['ARTIST_ID'] : null;
		$entity->name = $row['ARTIST_NAME'] ?? null;
		$entity->image = $row['ARTIST_IMAGE'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
