<?php
/*
*******************************************************************
PaymentRepository.php
Query and persistence for the PAYMENT table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class PaymentRepository
{
	private const SELECT_COLUMNS = 'PAYMENT_ID, PAYMENT_AMOUNT, PAYMENT_DATE, PAYMENT_DESCRIPTION,
		VENDOR_ID, CHECK_NUMBER, INVOICE, PURCHASE_ORDER, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single payment by primary key.
	 */
	public function findById(int $id): ?Payment
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PAYMENT
		         WHERE PAYMENT_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Payment::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance pages.
	 * If id is set, only the primary key is used (legacy getPayment behavior).
	 *
	 * Supported keys: id, paymentDate, paymentYear, startDate, endDate, description,
	 * fuzzyName, amount, vendorId, purchaseOrder, checkNumber, invoice
	 *
	 * @return Payment[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PAYMENT
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['paymentDate'])) {
			$sql .= ' AND PAYMENT_DATE LIKE :paymentDate';
			$params[':paymentDate'] = $criteria['paymentDate'] . '%';
		}

		if (!empty($criteria['paymentYear'])) {
			$sql .= ' AND YEAR(PAYMENT_DATE) = :paymentYear';
			$params[':paymentYear'] = (int) $criteria['paymentYear'];
		}

		if (!empty($criteria['startDate'])) {
			$sql .= ' AND PAYMENT_DATE >= :startDate';
			$params[':startDate'] = $criteria['startDate'];
		}

		if (!empty($criteria['endDate'])) {
			$sql .= ' AND PAYMENT_DATE <= :endDate';
			$params[':endDate'] = $criteria['endDate'];
		}

		if (!empty($criteria['description'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND PAYMENT_DESCRIPTION LIKE :description';
				$params[':description'] = '%' . $criteria['description'] . '%';
			} else {
				$sql .= ' AND PAYMENT_DESCRIPTION = :description';
				$params[':description'] = $criteria['description'];
			}
		}

		if (!empty($criteria['amount']) && $criteria['amount'] > 0) {
			$sql .= ' AND PAYMENT_AMOUNT = :amount';
			$params[':amount'] = $criteria['amount'];
		}

		if (!empty($criteria['vendorId'])) {
			$sql .= ' AND VENDOR_ID = :vendorId';
			$params[':vendorId'] = (int) $criteria['vendorId'];
		}

		if (!empty($criteria['purchaseOrder'])) {
			$sql .= ' AND PURCHASE_ORDER = :purchaseOrder';
			$params[':purchaseOrder'] = $criteria['purchaseOrder'];
		}

		if (!empty($criteria['checkNumber'])) {
			$sql .= ' AND CHECK_NUMBER = :checkNumber';
			$params[':checkNumber'] = (int) $criteria['checkNumber'];
		}

		if (!empty($criteria['invoice'])) {
			$sql .= ' AND INVOICE = :invoice';
			$params[':invoice'] = (int) $criteria['invoice'];
		}

		$sql .= ' ORDER BY PAYMENT_DATE';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new PAYMENT row. Sets $entity->id on success.
	 */
	public function insert(Payment $entity): bool
	{
		$sql = 'INSERT INTO PAYMENT (
		            PAYMENT_DATE, PAYMENT_AMOUNT, PAYMENT_DESCRIPTION, VENDOR_ID,
		            CHECK_NUMBER, INVOICE, PURCHASE_ORDER, LAST_UPDATE
		        ) VALUES (
		            :paymentDate, :amount, :description, :vendorId,
		            :checkNumber, :invoice, :purchaseOrder, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing PAYMENT row.
	 */
	public function update(Payment $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE PAYMENT
		           SET PAYMENT_DATE = :paymentDate,
		               PAYMENT_AMOUNT = :amount,
		               PAYMENT_DESCRIPTION = :description,
		               VENDOR_ID = :vendorId,
		               CHECK_NUMBER = :checkNumber,
		               INVOICE = :invoice,
		               PURCHASE_ORDER = :purchaseOrder,
		               LAST_UPDATE = SYSDATE()
		         WHERE PAYMENT_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a PAYMENT row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM PAYMENT WHERE PAYMENT_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Payment[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Payment::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(Payment $entity): array
	{
		return [
			':paymentDate' => $entity->paymentDate,
			':amount' => $entity->amount,
			':description' => $entity->description,
			':vendorId' => is_numeric($entity->vendorId) ? $entity->vendorId : null,
			':checkNumber' => is_numeric($entity->checkNumber) ? $entity->checkNumber : null,
			':invoice' => is_numeric($entity->invoice) ? $entity->invoice : null,
			':purchaseOrder' => $entity->purchaseOrder,
		];
	}
}
