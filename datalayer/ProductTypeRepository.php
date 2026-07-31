<?php
/*
*******************************************************************
ProductTypeRepository.php
Query and persistence for the PRODUCT_TYPE table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ProductTypeRepository
{
	private const SELECT_COLUMNS = 'PRODUCT_TYPE_ID, PRODUCT_TYPE_NAME, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single product type by primary key.
	 */
	public function findById(int $id): ?ProductType
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PRODUCT_TYPE
		         WHERE PRODUCT_TYPE_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? ProductType::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance pages.
	 * If id is set, only the primary key is used (legacy getProductType behavior).
	 *
	 * Supported keys: id, name, fuzzyName
	 *
	 * @return ProductType[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PRODUCT_TYPE
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND PRODUCT_TYPE_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND PRODUCT_TYPE_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		$sql .= ' ORDER BY PRODUCT_TYPE_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new PRODUCT_TYPE row. Sets $entity->id on success.
	 */
	public function insert(ProductType $entity): bool
	{
		$sql = 'INSERT INTO PRODUCT_TYPE (
		            PRODUCT_TYPE_NAME, LAST_UPDATE
		        ) VALUES (
		            :name, NOW()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([':name' => $entity->name]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing PRODUCT_TYPE row.
	 */
	public function update(ProductType $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE PRODUCT_TYPE
		           SET PRODUCT_TYPE_NAME = :name,
		               LAST_UPDATE = NOW()
		         WHERE PRODUCT_TYPE_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':name' => $entity->name,
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete a PRODUCT_TYPE row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM PRODUCT_TYPE WHERE PRODUCT_TYPE_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return ProductType[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = ProductType::fromRow($row);
		}
		return $records;
	}
}
