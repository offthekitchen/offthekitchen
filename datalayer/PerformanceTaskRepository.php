<?php
/*
*******************************************************************
PerformanceTaskRepository.php
Query and persistence for the PERFORMANCE_TASK table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class PerformanceTaskRepository
{
	private const SELECT_COLUMNS = 'PERFORMANCE_TASK_ID, DESCRIPTION, PERFORMANCE_ID,
		TOUR_ID, COMPLETE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single performance task by primary key.
	 */
	public function findById(int $id): ?PerformanceTask
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PERFORMANCE_TASK
		         WHERE PERFORMANCE_TASK_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? PerformanceTask::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getPerformanceTask behavior).
	 *
	 * Supported keys: id, description, fuzzyDescription, tourId, performanceId, complete
	 *
	 * @return PerformanceTask[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PERFORMANCE_TASK
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['description'])) {
			if (!empty($criteria['fuzzyDescription'])) {
				$sql .= ' AND DESCRIPTION LIKE :description';
				$params[':description'] = '%' . $criteria['description'] . '%';
			} else {
				$sql .= ' AND DESCRIPTION = :description';
				$params[':description'] = $criteria['description'];
			}
		}

		if (!empty($criteria['tourId'])) {
			$sql .= ' AND TOUR_ID = :tourId';
			$params[':tourId'] = (int) $criteria['tourId'];
		}

		if (!empty($criteria['performanceId'])) {
			$sql .= ' AND PERFORMANCE_ID = :performanceId';
			$params[':performanceId'] = (int) $criteria['performanceId'];
		}

		if (!empty($criteria['complete'])) {
			$sql .= ' AND COMPLETE = :complete';
			$params[':complete'] = $criteria['complete'] ? 1 : 0;
		}

		$sql .= ' ORDER BY LAST_UPDATE';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new PERFORMANCE_TASK row. Sets $entity->id on success.
	 */
	public function insert(PerformanceTask $entity): bool
	{
		$sql = 'INSERT INTO PERFORMANCE_TASK (
		            DESCRIPTION, PERFORMANCE_ID, TOUR_ID, COMPLETE, LAST_UPDATE
		        ) VALUES (
		            :description, :performanceId, :tourId, :complete, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing PERFORMANCE_TASK row.
	 */
	public function update(PerformanceTask $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE PERFORMANCE_TASK
		           SET DESCRIPTION = :description,
		               PERFORMANCE_ID = :performanceId,
		               TOUR_ID = :tourId,
		               COMPLETE = :complete,
		               LAST_UPDATE = SYSDATE()
		         WHERE PERFORMANCE_TASK_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a PERFORMANCE_TASK row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM PERFORMANCE_TASK WHERE PERFORMANCE_TASK_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return PerformanceTask[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = PerformanceTask::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(PerformanceTask $entity): array
	{
		return [
			':description' => $entity->description,
			':performanceId' => !empty($entity->performanceId) ? $entity->performanceId : null,
			':tourId' => !empty($entity->tourId) ? $entity->tourId : null,
			':complete' => $entity->complete ? 1 : 0,
		];
	}
}
