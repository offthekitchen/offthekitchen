<?php
/*
*******************************************************************
TaxCategory.php
Entity representing one row from the TAX_CATEGORY table.
*******************************************************************
*/

namespace Datalayer;

class TaxCategory
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
		$entity->id = isset($row['TAX_CATEGORY_ID']) ? (int) $row['TAX_CATEGORY_ID'] : null;
		$entity->name = $row['TAX_CATEGORY_NAME'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
