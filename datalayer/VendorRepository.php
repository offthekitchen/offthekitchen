<?php
/*
*******************************************************************
VendorRepository.php
Query and persistence for the VENDOR table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class VendorRepository
{
	private const SELECT_COLUMNS = 'VENDOR_ID, VENDOR_NAME, ACTIVE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single vendor by primary key.
	 */
	public function findById(int $id): ?Vendor
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM VENDOR
		         WHERE VENDOR_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Vendor::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance.
	 * If id is set, only the primary key is used (legacy getVendor behavior).
	 *
	 * Supported keys: id, name, fuzzyName, active, includeId
	 * - active: when set, filter ACTIVE = that value (0 or 1)
	 * - includeId: with active, also include this VENDOR_ID (e.g. current inactive selection)
	 *
	 * @return Vendor[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM VENDOR
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND VENDOR_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND VENDOR_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (array_key_exists('active', $criteria) && $criteria['active'] !== null && $criteria['active'] !== '') {
			$includeId = isset($criteria['includeId']) ? (int) $criteria['includeId'] : 0;
			if ($includeId > 0) {
				$sql .= ' AND (ACTIVE = :active OR VENDOR_ID = :includeId)';
				$params[':active'] = (int) $criteria['active'];
				$params[':includeId'] = $includeId;
			} else {
				$sql .= ' AND ACTIVE = :active';
				$params[':active'] = (int) $criteria['active'];
			}
		}

		$sql .= ' ORDER BY VENDOR_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new VENDOR row. Sets $entity->id on success.
	 */
	public function insert(Vendor $entity): bool
	{
		$sql = 'INSERT INTO VENDOR (VENDOR_NAME, ACTIVE, LAST_UPDATE)
		        VALUES (:name, :active, SYSDATE())';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([
			':name' => $entity->name,
			':active' => (int) ($entity->active ?? 0),
		]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing VENDOR row.
	 */
	public function update(Vendor $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE VENDOR
		           SET VENDOR_NAME = :name,
		               ACTIVE = :active,
		               LAST_UPDATE = SYSDATE()
		         WHERE VENDOR_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':name' => $entity->name,
			':active' => (int) ($entity->active ?? 0),
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete a VENDOR row by primary key.
	 * Callers should check for related expenses and payments first if needed.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM VENDOR WHERE VENDOR_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Vendor[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Vendor::fromRow($row);
		}
		return $records;
	}
}
