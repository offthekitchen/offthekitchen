<?php
/*
*******************************************************************
TourRepository.php
Query and persistence for the TOUR table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class TourRepository
{
	private const SELECT_COLUMNS = 'TOUR_ID, TOUR_NAME, TOUR_START_DATE, TOUR_END_DATE, NOTES, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single tour by primary key.
	 */
	public function findById(int $id): ?Tour
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM TOUR
		         WHERE TOUR_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Tour::fromRow($row) : null;
	}

	/**
	 * Most recent completed tour (legacy getLastTour behavior).
	 */
	public function findLastCompleted(): ?Tour
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM TOUR
		         WHERE TOUR_END_DATE <= CURRENT_DATE
		         ORDER BY TOUR_END_DATE DESC
		         LIMIT 1';

		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$row = $stmt->fetch();

		return $row ? Tour::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance.
	 * If id is set, only the primary key is used (legacy getTour behavior).
	 *
	 * Supported keys: id, name, fuzzyName, includeDate, notes
	 *
	 * @return Tour[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM TOUR
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND TOUR_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND TOUR_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['includeDate'])) {
			// Native PDO prepares disallow reusing the same named placeholder twice.
			$sql .= ' AND TOUR_START_DATE <= :includeDateStart';
			$sql .= ' AND TOUR_END_DATE >= :includeDateEnd';
			$params[':includeDateStart'] = $criteria['includeDate'];
			$params[':includeDateEnd'] = $criteria['includeDate'];
		}

		if (!empty($criteria['notes'])) {
			$sql .= ' AND NOTES LIKE :notes';
			$params[':notes'] = '%' . $criteria['notes'] . '%';
		}

		$sql .= ' ORDER BY TOUR_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new TOUR row. Sets $entity->id on success.
	 */
	public function insert(Tour $entity): bool
	{
		$sql = 'INSERT INTO TOUR (
		            TOUR_NAME, TOUR_START_DATE, TOUR_END_DATE, NOTES, LAST_UPDATE
		        ) VALUES (
		            :name, :startDate, :endDate, :notes, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing TOUR row.
	 */
	public function update(Tour $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE TOUR
		           SET TOUR_NAME = :name,
		               TOUR_START_DATE = :startDate,
		               TOUR_END_DATE = :endDate,
		               NOTES = :notes,
		               LAST_UPDATE = SYSDATE()
		         WHERE TOUR_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a TOUR row by primary key.
	 * Callers should check for related expenses and performances first if needed.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM TOUR WHERE TOUR_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Tour[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Tour::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(Tour $entity): array
	{
		return [
			':name' => $entity->name,
			':startDate' => $entity->startDate,
			':endDate' => $entity->endDate,
			':notes' => $entity->notes,
		];
	}
}
