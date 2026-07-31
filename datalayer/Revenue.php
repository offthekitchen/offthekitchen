<?php
/*
*******************************************************************
Revenue.php
Entity representing one row from the REVENUE table.
*******************************************************************
*/

namespace Datalayer;

class Revenue
{
	public ?int $id = null;
	public ?string $paidDate = null;
	public ?float $amount = null;
	public ?string $revenueDate = null;
	public ?string $description = null;
	public ?int $paymentId = null;
	public ?int $revenueTypeId = null;
	public ?int $productId = null;
	public ?int $productQty = null;
	public ?int $performanceId = null;
	public ?bool $coloradoRevenue = null;
	public ?bool $elPasoRevenue = null;
	public ?bool $charitable = null;
	public ?bool $resale = null;
	public ?string $productName = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['REVENUE_ID']) ? (int) $row['REVENUE_ID'] : null;
		$entity->paidDate = $row['PAID_DATE'] ?? null;
		$entity->amount = isset($row['REVENUE_AMOUNT']) && $row['REVENUE_AMOUNT'] !== null && $row['REVENUE_AMOUNT'] !== ''
			? (float) $row['REVENUE_AMOUNT']
			: null;
		$entity->revenueDate = $row['REVENUE_DATE'] ?? null;
		$entity->description = $row['REVENUE_DESCRIPTION'] ?? null;
		$entity->paymentId = isset($row['PAYMENT_ID']) && $row['PAYMENT_ID'] !== null && $row['PAYMENT_ID'] !== ''
			? (int) $row['PAYMENT_ID']
			: null;
		$entity->revenueTypeId = isset($row['REVENUE_TYPE_ID']) && $row['REVENUE_TYPE_ID'] !== null && $row['REVENUE_TYPE_ID'] !== ''
			? (int) $row['REVENUE_TYPE_ID']
			: null;
		$entity->productId = isset($row['PRODUCT_ID']) && $row['PRODUCT_ID'] !== null && $row['PRODUCT_ID'] !== ''
			? (int) $row['PRODUCT_ID']
			: null;
		$entity->productQty = isset($row['PRODUCT_QTY']) && $row['PRODUCT_QTY'] !== null && $row['PRODUCT_QTY'] !== ''
			? (int) $row['PRODUCT_QTY']
			: null;
		$entity->performanceId = isset($row['PERFORMANCE_ID']) && $row['PERFORMANCE_ID'] !== null && $row['PERFORMANCE_ID'] !== ''
			? (int) $row['PERFORMANCE_ID']
			: null;
		$entity->coloradoRevenue = isset($row['COLORADO_REVENUE']) ? (bool) $row['COLORADO_REVENUE'] : null;
		$entity->elPasoRevenue = isset($row['EL_PASO_REVENUE']) ? (bool) $row['EL_PASO_REVENUE'] : null;
		$entity->charitable = isset($row['CHARITABLE']) ? (bool) $row['CHARITABLE'] : null;
		$entity->resale = isset($row['RESALE']) ? (bool) $row['RESALE'] : null;
		if (isset($row['PRODUCT_NAME'])) {
			$entity->productName = $row['PRODUCT_NAME'];
		}
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
