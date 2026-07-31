<?php
/*
*******************************************************************
Category.php
Entity representing one row from the CATEGORY table.
*******************************************************************
*/

namespace Datalayer;

class Category
{
	public ?int $id = null;
	public ?string $name = null;
	public ?bool $expenseRelated = null;
	public ?bool $revenueRelated = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['CATEGORY_ID']) ? (int) $row['CATEGORY_ID'] : null;
		$entity->name = $row['CATEGORY_NAME'] ?? null;
		$entity->expenseRelated = isset($row['EXPENSE_RELATED']) ? (bool) $row['EXPENSE_RELATED'] : null;
		$entity->revenueRelated = isset($row['REVENUE_RELATED']) ? (bool) $row['REVENUE_RELATED'] : null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
