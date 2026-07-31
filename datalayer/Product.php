<?php
/*
*******************************************************************
Product.php
Entity representing one row from the PRODUCT table.
*******************************************************************
*/

namespace Datalayer;

class Product
{
	public ?int $id = null;
	public ?string $name = null;
	public ?int $artistId = null;
	public ?string $image = null;
	public ?string $thumbnail = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['PRODUCT_ID']) ? (int) $row['PRODUCT_ID'] : null;
		$entity->name = $row['PRODUCT_NAME'] ?? null;
		$entity->artistId = isset($row['ARTIST_ID']) && $row['ARTIST_ID'] !== null && $row['ARTIST_ID'] !== ''
			? (int) $row['ARTIST_ID']
			: null;
		$entity->image = $row['IMAGE'] ?? null;
		$entity->thumbnail = $row['THUMBNAIL'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
