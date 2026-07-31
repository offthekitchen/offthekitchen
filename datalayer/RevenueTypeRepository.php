<?php
/*
*******************************************************************
RevenueTypeRepository.php
Query and persistence for the REVENUE_TYPE table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class RevenueTypeRepository
{
	private const SELECT_COLUMNS = 'REVENUE_TYPE_ID, REVENUE_TYPE_NAME, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single revenue type by primary key.
	 */
	public function findById(int $id): ?RevenueType
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM REVENUE_TYPE
		         WHERE REVENUE_TYPE_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? RevenueType::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance.
	 * If id is set, only the primary key is used (legacy getRevenueType behavior).
	 *
	 * Supported keys: id, name, fuzzyName
	 *
	 * @return RevenueType[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM REVENUE_TYPE
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND REVENUE_TYPE_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND REVENUE_TYPE_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		$sql .= ' ORDER BY REVENUE_TYPE_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new REVENUE_TYPE row. Sets $entity->id on success.
	 */
	public function insert(RevenueType $entity): bool
	{
		$sql = 'INSERT INTO REVENUE_TYPE (REVENUE_TYPE_NAME, LAST_UPDATE)
		        VALUES (:name, SYSDATE())';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([':name' => $entity->name]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing REVENUE_TYPE row.
	 */
	public function update(RevenueType $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE REVENUE_TYPE
		           SET REVENUE_TYPE_NAME = :name,
		               LAST_UPDATE = SYSDATE()
		         WHERE REVENUE_TYPE_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':name' => $entity->name,
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete a REVENUE_TYPE row by primary key.
	 * Callers should check for related revenue first if needed.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM REVENUE_TYPE WHERE REVENUE_TYPE_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return RevenueType[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = RevenueType::fromRow($row);
		}
		return $records;
	}
}
