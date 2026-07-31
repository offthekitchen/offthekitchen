<?php
/*
*******************************************************************
Expense.php
Entity representing one row from the EXPENSE table.
*******************************************************************
*/

namespace Datalayer;

class Expense
{
	public ?int $id = null;
	public ?string $expenseDate = null;
	public ?float $expenseAmount = null;
	public ?string $description = null;
	public ?int $tourId = null;
	public ?int $productId = null;
	public ?int $vendorId = null;
	public ?int $taxCategoryId = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['EXPENSE_ID']) ? (int) $row['EXPENSE_ID'] : null;
		$entity->expenseDate = $row['EXPENSE_DATE'] ?? null;
		$entity->expenseAmount = isset($row['EXPENSE_AMOUNT']) && $row['EXPENSE_AMOUNT'] !== null && $row['EXPENSE_AMOUNT'] !== ''
			? (float) $row['EXPENSE_AMOUNT']
			: null;
		$entity->description = $row['EXPENSE_DESCRIPTION'] ?? null;
		$entity->tourId = isset($row['TOUR_ID']) && $row['TOUR_ID'] !== null && $row['TOUR_ID'] !== ''
			? (int) $row['TOUR_ID']
			: null;
		$entity->productId = isset($row['PRODUCT_ID']) && $row['PRODUCT_ID'] !== null && $row['PRODUCT_ID'] !== ''
			? (int) $row['PRODUCT_ID']
			: null;
		$entity->vendorId = isset($row['VENDOR_ID']) && $row['VENDOR_ID'] !== null && $row['VENDOR_ID'] !== ''
			? (int) $row['VENDOR_ID']
			: null;
		$entity->taxCategoryId = isset($row['TAX_CATEGORY_ID']) && $row['TAX_CATEGORY_ID'] !== null && $row['TAX_CATEGORY_ID'] !== ''
			? (int) $row['TAX_CATEGORY_ID']
			: null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
