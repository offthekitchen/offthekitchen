<?php
/*
*******************************************************************
RevenueRepository.php
Query and persistence for the REVENUE table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class RevenueRepository
{
	private const SELECT_COLUMNS = 'REVENUE_ID, PAID_DATE, REVENUE_AMOUNT, REVENUE_DESCRIPTION,
		REVENUE_DATE, PAYMENT_ID, REVENUE_TYPE_ID, PERFORMANCE_ID, PRODUCT_ID,
		PRODUCT_QTY, COLORADO_REVENUE, EL_PASO_REVENUE, CHARITABLE, RESALE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single revenue record by primary key.
	 */
	public function findById(int $id): ?Revenue
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM REVENUE
		         WHERE REVENUE_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Revenue::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance pages.
	 * If id is set, only the primary key is used (legacy getRevenue behavior).
	 *
	 * Supported keys: id, revenueDate, paidDate, revenueYear, paidYear, startDate,
	 * endDate, description, fuzzyName, amount, paymentId, excludePaymentId,
	 * revenueTypeId, performanceId, excludePerformanceId, performanceRelated,
	 * coloradoRevenue, elPasoRevenue, charitable, resale, productId, productQty,
	 * artistId, categoryIds, includeProductInfo
	 *
	 * @return Revenue[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$includeProductInfo = !empty($criteria['includeProductInfo']);

		$columns = implode(', ', array_map(
			static fn(string $col): string => 'REVENUE.' . trim($col),
			explode(',', self::SELECT_COLUMNS)
		));
		$sql = 'SELECT ' . $columns;
		if ($includeProductInfo) {
			$sql .= ', PRODUCT.PRODUCT_NAME';
		}
		$sql .= ' FROM REVENUE';
		if ($includeProductInfo) {
			$sql .= ' LEFT OUTER JOIN PRODUCT ON (REVENUE.PRODUCT_ID = PRODUCT.PRODUCT_ID)';
		}
		$sql .= ' WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['revenueDate'])) {
			$sql .= ' AND REVENUE.REVENUE_DATE LIKE :revenueDate';
			$params[':revenueDate'] = $criteria['revenueDate'] . '%';
		}

		if (!empty($criteria['paidDate'])) {
			$sql .= ' AND REVENUE.PAID_DATE LIKE :paidDate';
			$params[':paidDate'] = $criteria['paidDate'] . '%';
		}

		if (!empty($criteria['revenueYear'])) {
			$sql .= ' AND YEAR(REVENUE.REVENUE_DATE) = :revenueYear';
			$params[':revenueYear'] = (int) $criteria['revenueYear'];
		}

		if (!empty($criteria['paidYear'])) {
			$sql .= ' AND YEAR(REVENUE.PAID_DATE) = :paidYear';
			$params[':paidYear'] = (int) $criteria['paidYear'];
		}

		if (!empty($criteria['startDate'])) {
			$sql .= ' AND REVENUE.PAID_DATE >= :startDate';
			$params[':startDate'] = $criteria['startDate'];
		}

		if (!empty($criteria['endDate'])) {
			$sql .= ' AND REVENUE.PAID_DATE <= :endDate';
			$params[':endDate'] = $criteria['endDate'];
		}

		if (!empty($criteria['description'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND REVENUE.REVENUE_DESCRIPTION LIKE :description';
				$params[':description'] = '%' . $criteria['description'] . '%';
			} else {
				$sql .= ' AND REVENUE.REVENUE_DESCRIPTION = :description';
				$params[':description'] = $criteria['description'];
			}
		}

		if (!empty($criteria['amount']) && $criteria['amount'] > 0) {
			$sql .= ' AND REVENUE.REVENUE_AMOUNT = :amount';
			$params[':amount'] = $criteria['amount'];
		}

		if (!empty($criteria['paymentId'])) {
			$sql .= ' AND REVENUE.PAYMENT_ID = :paymentId';
			$params[':paymentId'] = (int) $criteria['paymentId'];
		}

		if (!empty($criteria['excludePaymentId'])) {
			$sql .= ' AND (REVENUE.PAYMENT_ID IS NULL OR REVENUE.PAYMENT_ID <> :excludePaymentId)';
			$params[':excludePaymentId'] = (int) $criteria['excludePaymentId'];
		}

		if (!empty($criteria['revenueTypeId'])) {
			$sql .= ' AND REVENUE.REVENUE_TYPE_ID = :revenueTypeId';
			$params[':revenueTypeId'] = (int) $criteria['revenueTypeId'];
		}

		if (!empty($criteria['performanceId'])) {
			$sql .= ' AND REVENUE.PERFORMANCE_ID = :performanceId';
			$params[':performanceId'] = (int) $criteria['performanceId'];
		}

		if (!empty($criteria['excludePerformanceId'])) {
			$sql .= ' AND (REVENUE.PERFORMANCE_ID IS NULL OR REVENUE.PERFORMANCE_ID <> :excludePerformanceId)';
			$params[':excludePerformanceId'] = (int) $criteria['excludePerformanceId'];
		}

		if (!empty($criteria['performanceRelated'])) {
			$sql .= ' AND REVENUE.PERFORMANCE_ID IS NOT NULL AND REVENUE.PERFORMANCE_ID > 0';
		}

		if (array_key_exists('coloradoRevenue', $criteria) && $criteria['coloradoRevenue'] !== null) {
			$sql .= ' AND REVENUE.COLORADO_REVENUE = :coloradoRevenue';
			$params[':coloradoRevenue'] = $criteria['coloradoRevenue'] ? 1 : 0;
		}

		if (array_key_exists('elPasoRevenue', $criteria) && $criteria['elPasoRevenue'] !== null) {
			$sql .= ' AND REVENUE.EL_PASO_REVENUE = :elPasoRevenue';
			$params[':elPasoRevenue'] = $criteria['elPasoRevenue'] ? 1 : 0;
		}

		if (array_key_exists('charitable', $criteria) && $criteria['charitable'] !== null) {
			$sql .= ' AND REVENUE.CHARITABLE = :charitable';
			$params[':charitable'] = $criteria['charitable'] ? 1 : 0;
		}

		if (array_key_exists('resale', $criteria) && $criteria['resale'] !== null) {
			$sql .= ' AND REVENUE.RESALE = :resale';
			$params[':resale'] = $criteria['resale'] ? 1 : 0;
		}

		if (!empty($criteria['productId']) && (int) $criteria['productId'] > 0) {
			$sql .= ' AND REVENUE.PRODUCT_ID = :productId';
			$params[':productId'] = (int) $criteria['productId'];
		} elseif (array_key_exists('productId', $criteria)
			&& ($criteria['productId'] === 0 || $criteria['productId'] === '0')
		) {
			$sql .= ' AND (REVENUE.PRODUCT_ID IS NULL OR REVENUE.PRODUCT_ID = \'\')';
		}

		if (!empty($criteria['productQty'])) {
			$sql .= ' AND REVENUE.PRODUCT_QTY = :productQty';
			$params[':productQty'] = (int) $criteria['productQty'];
		}

		if (!empty($criteria['artistId'])) {
			$sql .= ' AND EXISTS(SELECT * FROM PRODUCT
			              WHERE PRODUCT.PRODUCT_ID = REVENUE.PRODUCT_ID
			                AND PRODUCT.ARTIST_ID = :artistId)';
			$params[':artistId'] = (int) $criteria['artistId'];
		}

		if (!empty($criteria['categoryIds']) && is_array($criteria['categoryIds'])) {
			foreach ($criteria['categoryIds'] as $index => $categoryId) {
				if (empty($categoryId)) {
					continue;
				}
				$param = ':categoryId' . $index;
				$sql .= ' AND EXISTS(SELECT * FROM REVENUE_CATEGORY_XREF
				              WHERE REVENUE_CATEGORY_XREF.REVENUE_ID = REVENUE.REVENUE_ID
				                AND REVENUE_CATEGORY_XREF.CATEGORY_ID = ' . $param . ')';
				$params[$param] = (int) $categoryId;
			}
		}

		$sql .= ' ORDER BY REVENUE.REVENUE_DATE';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new REVENUE row. Sets $entity->id on success.
	 */
	public function insert(Revenue $entity): bool
	{
		$sql = 'INSERT INTO REVENUE (
		            REVENUE_DATE, PAID_DATE, REVENUE_AMOUNT, REVENUE_DESCRIPTION,
		            PAYMENT_ID, REVENUE_TYPE_ID, PERFORMANCE_ID, PRODUCT_ID,
		            PRODUCT_QTY, COLORADO_REVENUE, EL_PASO_REVENUE, CHARITABLE,
		            RESALE, LAST_UPDATE
		        ) VALUES (
		            :revenueDate, :paidDate, :amount, :description,
		            :paymentId, :revenueTypeId, :performanceId, :productId,
		            :productQty, :coloradoRevenue, :elPasoRevenue, :charitable,
		            :resale, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity, true));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing REVENUE row.
	 */
	public function update(Revenue $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE REVENUE
		           SET REVENUE_DATE = :revenueDate,
		               PAID_DATE = :paidDate,
		               REVENUE_AMOUNT = :amount,
		               REVENUE_DESCRIPTION = :description,
		               PAYMENT_ID = :paymentId,
		               REVENUE_TYPE_ID = :revenueTypeId,
		               PERFORMANCE_ID = :performanceId,
		               PRODUCT_ID = :productId,
		               PRODUCT_QTY = :productQty,
		               COLORADO_REVENUE = :coloradoRevenue,
		               EL_PASO_REVENUE = :elPasoRevenue,
		               CHARITABLE = :charitable,
		               RESALE = :resale,
		               LAST_UPDATE = SYSDATE()
		         WHERE REVENUE_ID = :id';

		$params = $this->bindEntity($entity, false);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a REVENUE row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM REVENUE WHERE REVENUE_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Revenue[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Revenue::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(Revenue $entity, bool $isInsert): array
	{
		$productId = is_numeric($entity->productId) ? $entity->productId : ($isInsert ? 0 : null);

		return [
			':revenueDate' => $entity->revenueDate,
			':paidDate' => $entity->paidDate,
			':amount' => $entity->amount,
			':description' => $entity->description,
			':paymentId' => is_numeric($entity->paymentId) ? $entity->paymentId : null,
			':revenueTypeId' => is_numeric($entity->revenueTypeId) ? $entity->revenueTypeId : null,
			':performanceId' => is_numeric($entity->performanceId) ? $entity->performanceId : null,
			':productId' => $productId,
			':productQty' => is_numeric($entity->productQty) ? $entity->productQty : null,
			':coloradoRevenue' => $entity->coloradoRevenue ? 1 : 0,
			':elPasoRevenue' => $entity->elPasoRevenue ? 1 : 0,
			':charitable' => $entity->charitable ? 1 : 0,
			':resale' => $entity->resale ? 1 : 0,
		];
	}
}
