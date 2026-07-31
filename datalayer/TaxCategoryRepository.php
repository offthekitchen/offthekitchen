<?php
/*
*******************************************************************
TaxCategoryRepository.php
Query and persistence for the TAX_CATEGORY table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class TaxCategoryRepository
{
	private const SELECT_COLUMNS = 'TAX_CATEGORY_ID, TAX_CATEGORY_NAME, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single tax category by primary key.
	 */
	public function findById(int $id): ?TaxCategory
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM TAX_CATEGORY
		         WHERE TAX_CATEGORY_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? TaxCategory::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance.
	 * If id is set, only the primary key is used (legacy getTaxCategory behavior).
	 *
	 * Supported keys: id, name, fuzzyName
	 *
	 * @return TaxCategory[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM TAX_CATEGORY
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND TAX_CATEGORY_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND TAX_CATEGORY_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		$sql .= ' ORDER BY TAX_CATEGORY_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new TAX_CATEGORY row. Sets $entity->id on success.
	 */
	public function insert(TaxCategory $entity): bool
	{
		$sql = 'INSERT INTO TAX_CATEGORY (TAX_CATEGORY_NAME, LAST_UPDATE)
		        VALUES (:name, SYSDATE())';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([':name' => $entity->name]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing TAX_CATEGORY row.
	 */
	public function update(TaxCategory $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE TAX_CATEGORY
		           SET TAX_CATEGORY_NAME = :name,
		               LAST_UPDATE = SYSDATE()
		         WHERE TAX_CATEGORY_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':name' => $entity->name,
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete a TAX_CATEGORY row by primary key.
	 * Callers should check for related expenses first if needed.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM TAX_CATEGORY WHERE TAX_CATEGORY_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return TaxCategory[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = TaxCategory::fromRow($row);
		}
		return $records;
	}
}
