<?php
/*
*******************************************************************
MileageRepository.php
Query and persistence for the MILEAGE table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class MileageRepository
{
	private const SELECT_COLUMNS = 'MILEAGE_ID, MILEAGE, REASON, MILEAGE_DATE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single mileage record by primary key.
	 */
	public function findById(int $id): ?Mileage
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM MILEAGE
		         WHERE MILEAGE_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Mileage::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getMileage behavior).
	 *
	 * Supported keys: id, mileage, fuzzyMileage, reason, mileageDate, mileageYear
	 *
	 * @return Mileage[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM MILEAGE
		         WHERE 1 = 1';
		$params = [];

		if (array_key_exists('mileage', $criteria)
			&& $criteria['mileage'] !== null
			&& $criteria['mileage'] !== ''
		) {
			if (!empty($criteria['fuzzyMileage'])) {
				$sql .= ' AND MILEAGE LIKE :mileage';
				$params[':mileage'] = '%' . $criteria['mileage'] . '%';
			} else {
				$sql .= ' AND MILEAGE = :mileage';
				$params[':mileage'] = (int) $criteria['mileage'];
			}
		}

		if (!empty($criteria['reason'])) {
			$sql .= ' AND REASON = :reason';
			$params[':reason'] = $criteria['reason'];
		}

		if (!empty($criteria['mileageDate'])) {
			$sql .= ' AND MILEAGE_DATE LIKE :mileageDate';
			$params[':mileageDate'] = '%' . $criteria['mileageDate'] . '%';
		}

		if (!empty($criteria['mileageYear'])) {
			$sql .= ' AND YEAR(MILEAGE_DATE) = :mileageYear';
			$params[':mileageYear'] = (int) $criteria['mileageYear'];
		}

		$sql .= ' ORDER BY MILEAGE_DATE';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new MILEAGE row. Sets $entity->id on success.
	 */
	public function insert(Mileage $entity): bool
	{
		$sql = 'INSERT INTO MILEAGE (MILEAGE, REASON, MILEAGE_DATE, LAST_UPDATE)
		        VALUES (:mileage, :reason, :mileageDate, SYSDATE())';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([
			':mileage' => $entity->mileage,
			':reason' => $entity->reason,
			':mileageDate' => $entity->mileageDate,
		]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing MILEAGE row.
	 */
	public function update(Mileage $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE MILEAGE
		           SET MILEAGE = :mileage,
		               REASON = :reason,
		               MILEAGE_DATE = :mileageDate,
		               LAST_UPDATE = SYSDATE()
		         WHERE MILEAGE_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':mileage' => $entity->mileage,
			':reason' => $entity->reason,
			':mileageDate' => $entity->mileageDate,
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete a MILEAGE row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM MILEAGE WHERE MILEAGE_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Mileage[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Mileage::fromRow($row);
		}
		return $records;
	}
}
