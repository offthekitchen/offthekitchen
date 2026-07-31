<?php
/*
*******************************************************************
Payment.php
Entity representing one row from the PAYMENT table.
*******************************************************************
*/

namespace Datalayer;

class Payment
{
	public ?int $id = null;
	public ?float $amount = null;
	public ?string $paymentDate = null;
	public ?string $description = null;
	public ?int $vendorId = null;
	public ?int $checkNumber = null;
	public ?int $invoice = null;
	public ?string $purchaseOrder = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['PAYMENT_ID']) ? (int) $row['PAYMENT_ID'] : null;
		$entity->amount = isset($row['PAYMENT_AMOUNT']) && $row['PAYMENT_AMOUNT'] !== null && $row['PAYMENT_AMOUNT'] !== ''
			? (float) $row['PAYMENT_AMOUNT']
			: null;
		$entity->paymentDate = $row['PAYMENT_DATE'] ?? null;
		$entity->description = $row['PAYMENT_DESCRIPTION'] ?? null;
		$entity->vendorId = isset($row['VENDOR_ID']) && $row['VENDOR_ID'] !== null && $row['VENDOR_ID'] !== ''
			? (int) $row['VENDOR_ID']
			: null;
		$entity->checkNumber = isset($row['CHECK_NUMBER']) && $row['CHECK_NUMBER'] !== null && $row['CHECK_NUMBER'] !== ''
			? (int) $row['CHECK_NUMBER']
			: null;
		$entity->invoice = isset($row['INVOICE']) && $row['INVOICE'] !== null && $row['INVOICE'] !== ''
			? (int) $row['INVOICE']
			: null;
		$entity->purchaseOrder = $row['PURCHASE_ORDER'] ?? null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
