<?php
/*
*******************************************************************
UnavailableDateRepository.php
Query and persistence for the UNAVAILABLE_DATES table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class UnavailableDateRepository
{
	private const SELECT_COLUMNS = 'UNAVAILABLE_ID, UNAVAILABLE_DATE, REASON, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single unavailable date by primary key.
	 */
	public function findById(int $id): ?UnavailableDate
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM UNAVAILABLE_DATES
		         WHERE UNAVAILABLE_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? UnavailableDate::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance.
	 * If id is set, only the primary key is used (legacy getUnavailableDate behavior).
	 *
	 * Supported keys: id, reason, fuzzyReason, unavailableDate, year, bookedDate
	 *
	 * @return UnavailableDate[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM UNAVAILABLE_DATES
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['reason'])) {
			if (!empty($criteria['fuzzyReason'])) {
				$sql .= ' AND REASON LIKE :reason';
				$params[':reason'] = '%' . $criteria['reason'] . '%';
			} else {
				$sql .= ' AND REASON = :reason';
				$params[':reason'] = $criteria['reason'];
			}
		}

		if (!empty($criteria['unavailableDate'])) {
			$sql .= ' AND UNAVAILABLE_DATE LIKE :unavailableDate';
			$params[':unavailableDate'] = $criteria['unavailableDate'] . '%';
		}

		if (!empty($criteria['year'])) {
			$sql .= ' AND YEAR(UNAVAILABLE_DATE) = :year';
			$params[':year'] = (int) $criteria['year'];
		}

		if (array_key_exists('bookedDate', $criteria) && $criteria['bookedDate'] !== null) {
			if ($criteria['bookedDate']) {
				$sql .= ' AND EXISTS (
				            SELECT 1 FROM PERFORMANCE
				             WHERE PERFORMANCE.PERFORMANCE_DATE = UNAVAILABLE_DATES.UNAVAILABLE_DATE
				               AND PERFORMANCE.BOOKED_DATE IS NOT NULL
				          )';
			} else {
				$sql .= ' AND NOT EXISTS (
				            SELECT 1 FROM PERFORMANCE
				             WHERE PERFORMANCE.PERFORMANCE_DATE = UNAVAILABLE_DATES.UNAVAILABLE_DATE
				          )';
			}
		}

		$sql .= ' ORDER BY UNAVAILABLE_DATE ASC';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new UNAVAILABLE_DATES row. Sets $entity->id on success.
	 * Legacy insertUnavailableDate also supported date-range batch inserts;
	 * callers needing that should loop or add a dedicated helper.
	 */
	public function insert(UnavailableDate $entity): bool
	{
		$sql = 'INSERT INTO UNAVAILABLE_DATES (UNAVAILABLE_DATE, REASON, LAST_UPDATE)
		        VALUES (:unavailableDate, :reason, SYSDATE())';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([
			':unavailableDate' => $entity->unavailableDate,
			':reason' => $entity->reason,
		]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing UNAVAILABLE_DATES row.
	 */
	public function update(UnavailableDate $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE UNAVAILABLE_DATES
		           SET UNAVAILABLE_DATE = :unavailableDate,
		               REASON = :reason,
		               LAST_UPDATE = SYSDATE()
		         WHERE UNAVAILABLE_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':unavailableDate' => $entity->unavailableDate,
			':reason' => $entity->reason,
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete an UNAVAILABLE_DATES row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM UNAVAILABLE_DATES WHERE UNAVAILABLE_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return UnavailableDate[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = UnavailableDate::fromRow($row);
		}
		return $records;
	}
}
