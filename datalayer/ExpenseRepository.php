<?php
/*
*******************************************************************
ExpenseRepository.php
Query and persistence for the EXPENSE table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ExpenseRepository
{
	private const SELECT_COLUMNS = 'EXPENSE_ID, EXPENSE_DATE, EXPENSE_AMOUNT, EXPENSE_DESCRIPTION,
		TOUR_ID, PRODUCT_ID, VENDOR_ID, TAX_CATEGORY_ID, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single expense by primary key.
	 */
	public function findById(int $id): ?Expense
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM EXPENSE
		         WHERE EXPENSE_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Expense::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance pages.
	 * If id is set, only the primary key is used (legacy getExpense behavior).
	 *
	 * Supported keys: id, expenseDate, expenseYear, startDate, endDate, description,
	 * fuzzyName, expenseAmount, tourId, excludeTourId, productId, vendorId,
	 * taxCategoryId, categoryIds, performanceRelated, performanceCategoryId
	 *
	 * @return Expense[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM EXPENSE
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['expenseDate'])) {
			$sql .= ' AND EXPENSE_DATE LIKE :expenseDate';
			$params[':expenseDate'] = $criteria['expenseDate'] . '%';
		}

		if (!empty($criteria['expenseYear'])) {
			$sql .= ' AND YEAR(EXPENSE_DATE) = :expenseYear';
			$params[':expenseYear'] = (int) $criteria['expenseYear'];
		}

		if (!empty($criteria['startDate'])) {
			$sql .= ' AND EXPENSE_DATE >= :startDate';
			$params[':startDate'] = $criteria['startDate'];
		}

		if (!empty($criteria['endDate'])) {
			$sql .= ' AND EXPENSE_DATE <= :endDate';
			$params[':endDate'] = $criteria['endDate'];
		}

		if (!empty($criteria['description'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND EXPENSE_DESCRIPTION LIKE :description';
				$params[':description'] = '%' . $criteria['description'] . '%';
			} else {
				$sql .= ' AND EXPENSE_DESCRIPTION = :description';
				$params[':description'] = $criteria['description'];
			}
		}

		if (!empty($criteria['expenseAmount']) && $criteria['expenseAmount'] > 0) {
			$sql .= ' AND EXPENSE_AMOUNT = :expenseAmount';
			$params[':expenseAmount'] = $criteria['expenseAmount'];
		}

		if (!empty($criteria['tourId'])) {
			$sql .= ' AND TOUR_ID = :tourId';
			$params[':tourId'] = (int) $criteria['tourId'];
		}

		if (!empty($criteria['excludeTourId'])) {
			$sql .= ' AND (TOUR_ID IS NULL OR TOUR_ID <> :excludeTourId)';
			$params[':excludeTourId'] = (int) $criteria['excludeTourId'];
		}

		if (!empty($criteria['performanceRelated']) && !empty($criteria['performanceCategoryId'])) {
			$sql .= ' AND EXISTS(SELECT * FROM EXPENSE_CATEGORY_XREF
			              WHERE EXPENSE_CATEGORY_XREF.EXPENSE_ID = EXPENSE.EXPENSE_ID
			                AND EXPENSE_CATEGORY_XREF.CATEGORY_ID = :performanceCategoryId)';
			$params[':performanceCategoryId'] = (int) $criteria['performanceCategoryId'];
		}

		if (array_key_exists('productId', $criteria)
			&& ($criteria['productId'] === 0 || $criteria['productId'] === '0')
		) {
			$sql .= ' AND (PRODUCT_ID IS NULL OR PRODUCT_ID = \'\')';
		} elseif (array_key_exists('productId', $criteria)
			&& $criteria['productId'] !== null
			&& $criteria['productId'] !== ''
			&& (int) $criteria['productId'] > 0
		) {
			$sql .= ' AND PRODUCT_ID = :productId';
			$params[':productId'] = (int) $criteria['productId'];
		}

		if (!empty($criteria['vendorId'])) {
			$sql .= ' AND VENDOR_ID = :vendorId';
			$params[':vendorId'] = (int) $criteria['vendorId'];
		}

		if (!empty($criteria['taxCategoryId'])) {
			$sql .= ' AND TAX_CATEGORY_ID = :taxCategoryId';
			$params[':taxCategoryId'] = (int) $criteria['taxCategoryId'];
		}

		if (!empty($criteria['categoryIds']) && is_array($criteria['categoryIds'])) {
			foreach ($criteria['categoryIds'] as $index => $categoryId) {
				if (empty($categoryId)) {
					continue;
				}
				$param = ':categoryId' . $index;
				$sql .= ' AND EXISTS(SELECT * FROM EXPENSE_CATEGORY_XREF
				              WHERE EXPENSE_CATEGORY_XREF.EXPENSE_ID = EXPENSE.EXPENSE_ID
				                AND EXPENSE_CATEGORY_XREF.CATEGORY_ID = ' . $param . ')';
				$params[$param] = (int) $categoryId;
			}
		}

		$sql .= ' ORDER BY EXPENSE_DATE';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new EXPENSE row. Sets $entity->id on success.
	 */
	public function insert(Expense $entity): bool
	{
		$sql = 'INSERT INTO EXPENSE (
		            EXPENSE_DATE, EXPENSE_AMOUNT, EXPENSE_DESCRIPTION, TOUR_ID,
		            PRODUCT_ID, VENDOR_ID, TAX_CATEGORY_ID, LAST_UPDATE
		        ) VALUES (
		            :expenseDate, :expenseAmount, :description, :tourId,
		            :productId, :vendorId, :taxCategoryId, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing EXPENSE row.
	 */
	public function update(Expense $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE EXPENSE
		           SET EXPENSE_DATE = :expenseDate,
		               EXPENSE_AMOUNT = :expenseAmount,
		               EXPENSE_DESCRIPTION = :description,
		               TOUR_ID = :tourId,
		               PRODUCT_ID = :productId,
		               VENDOR_ID = :vendorId,
		               TAX_CATEGORY_ID = :taxCategoryId,
		               LAST_UPDATE = SYSDATE()
		         WHERE EXPENSE_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete an EXPENSE row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM EXPENSE WHERE EXPENSE_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Expense[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Expense::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(Expense $entity): array
	{
		return [
			':expenseDate' => $entity->expenseDate,
			':expenseAmount' => $entity->expenseAmount,
			':description' => $entity->description,
			':tourId' => is_numeric($entity->tourId) ? $entity->tourId : null,
			':productId' => is_numeric($entity->productId) ? $entity->productId : null,
			':vendorId' => is_numeric($entity->vendorId) ? $entity->vendorId : null,
			':taxCategoryId' => is_numeric($entity->taxCategoryId) ? $entity->taxCategoryId : null,
		];
	}
}
