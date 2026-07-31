<?php
/*
*******************************************************************
ProductType.php
Entity representing one row from the PRODUCT_TYPE table.
*******************************************************************
*/

namespace Datalayer;

class ProductType
{
	public ?int $id = null;
	public ?string $name = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['PRODUCT_TYPE_ID']) ? (int) $row['PRODUCT_TYPE_ID'] : null;
		$entity->name = $row['PRODUCT_TYPE_NAME'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
