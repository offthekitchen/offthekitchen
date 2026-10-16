<?php
/*
*******************************************************************
CategoryRepository.php
Query and persistence for the CATEGORY table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class CategoryRepository
{
	private const SELECT_COLUMNS = 'CATEGORY_ID, CATEGORY_NAME, EXPENSE_RELATED,
		REVENUE_RELATED, ACTIVE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single category by primary key.
	 */
	public function findById(int $id): ?Category
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM CATEGORY
		         WHERE CATEGORY_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Category::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getCategory behavior).
	 *
	 * Supported keys: id, name, fuzzyName, expenseRelated, revenueRelated,
	 * active, includeId, includeIds
	 *
	 * @return Category[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM CATEGORY
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND CATEGORY_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND CATEGORY_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['expenseRelated'])) {
			$sql .= ' AND EXPENSE_RELATED = :expenseRelated';
			$params[':expenseRelated'] = $criteria['expenseRelated'] ? 1 : 0;
		}

		if (!empty($criteria['revenueRelated'])) {
			$sql .= ' AND REVENUE_RELATED = :revenueRelated';
			$params[':revenueRelated'] = $criteria['revenueRelated'] ? 1 : 0;
		}

		if (array_key_exists('active', $criteria) && $criteria['active'] !== null && $criteria['active'] !== '') {
			$includeIds = [];
			if (!empty($criteria['includeIds']) && is_array($criteria['includeIds'])) {
				foreach ($criteria['includeIds'] as $includeId) {
					if ((int) $includeId > 0) {
						$includeIds[] = (int) $includeId;
					}
				}
			}
			if (!empty($criteria['includeId']) && (int) $criteria['includeId'] > 0) {
				$includeIds[] = (int) $criteria['includeId'];
			}
			$includeIds = array_values(array_unique($includeIds));

			if (!empty($includeIds)) {
				$placeholders = [];
				foreach ($includeIds as $index => $includeId) {
					$param = ':includeId' . $index;
					$placeholders[] = $param;
					$params[$param] = $includeId;
				}
				$sql .= ' AND (ACTIVE = :active OR CATEGORY_ID IN (' . implode(', ', $placeholders) . '))';
				$params[':active'] = (int) $criteria['active'];
			} else {
				$sql .= ' AND ACTIVE = :active';
				$params[':active'] = (int) $criteria['active'];
			}
		}

		$sql .= ' ORDER BY CATEGORY_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new CATEGORY row. Sets $entity->id on success.
	 */
	public function insert(Category $entity): bool
	{
		$sql = 'INSERT INTO CATEGORY (
		            CATEGORY_NAME, EXPENSE_RELATED, REVENUE_RELATED, ACTIVE, LAST_UPDATE
		        ) VALUES (
		            :name, :expenseRelated, :revenueRelated, :active, NOW()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([
			':name' => $entity->name,
			':expenseRelated' => $entity->expenseRelated ? 1 : 0,
			':revenueRelated' => $entity->revenueRelated ? 1 : 0,
			':active' => (int) ($entity->active ?? 0),
		]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing CATEGORY row.
	 */
	public function update(Category $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE CATEGORY
		           SET CATEGORY_NAME = :name,
		               EXPENSE_RELATED = :expenseRelated,
		               REVENUE_RELATED = :revenueRelated,
		               ACTIVE = :active,
		               LAST_UPDATE = NOW()
		         WHERE CATEGORY_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':name' => $entity->name,
			':expenseRelated' => $entity->expenseRelated ? 1 : 0,
			':revenueRelated' => $entity->revenueRelated ? 1 : 0,
			':active' => (int) ($entity->active ?? 0),
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete a CATEGORY row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM CATEGORY WHERE CATEGORY_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Category[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Category::fromRow($row);
		}
		return $records;
	}
}
