<?php
/*
*******************************************************************
ThurdyDropRepository.php
Query and persistence for the thurdy_drop table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ThurdyDropRepository
{
	private const SELECT_COLUMNS = 'DROP_ID, DROP_LOCATION, DROP_DESC, DROP_DATE, DROP_IMAGE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single drop by primary key.
	 */
	public function findById(int $id): ?ThurdyDrop
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_drop
		         WHERE DROP_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? ThurdyDrop::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getThurdyDrop behavior).
	 *
	 * Supported keys: id, dropLocation, fuzzyName, dropDesc, dropDate, dropImage,
	 * orderBy (date|id)
	 *
	 * @return ThurdyDrop[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_drop
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['dropLocation'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND DROP_LOCATION LIKE :dropLocation';
				$params[':dropLocation'] = '%' . $criteria['dropLocation'] . '%';
			} else {
				$sql .= ' AND DROP_LOCATION = :dropLocation';
				$params[':dropLocation'] = $criteria['dropLocation'];
			}
		}

		if (!empty($criteria['dropDesc'])) {
			$sql .= ' AND DROP_DESC = :dropDesc';
			$params[':dropDesc'] = $criteria['dropDesc'];
		}

		if (!empty($criteria['dropDate'])) {
			$sql .= ' AND DROP_DATE = :dropDate';
			$params[':dropDate'] = $criteria['dropDate'];
		}

		if (!empty($criteria['dropImage'])) {
			$sql .= ' AND DROP_IMAGE = :dropImage';
			$params[':dropImage'] = $criteria['dropImage'];
		}

		$orderBy = $criteria['orderBy'] ?? 'id';
		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new thurdy_drop row. Sets $entity->id on success.
	 */
	public function insert(ThurdyDrop $entity): bool
	{
		$sql = 'INSERT INTO thurdy_drop (
		            DROP_LOCATION, DROP_DESC, DROP_DATE, DROP_IMAGE, LAST_UPDATE
		        ) VALUES (
		            :dropLocation, :dropDesc, :dropDate, :dropImage, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing thurdy_drop row.
	 */
	public function update(ThurdyDrop $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE thurdy_drop
		           SET DROP_LOCATION = :dropLocation,
		               DROP_DESC = :dropDesc,
		               DROP_DATE = :dropDate,
		               DROP_IMAGE = :dropImage,
		               LAST_UPDATE = SYSDATE()
		         WHERE DROP_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a thurdy_drop row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM thurdy_drop WHERE DROP_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return ThurdyDrop[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = ThurdyDrop::fromRow($row);
		}
		return $records;
	}

	private function orderByClause(string $orderBy): string
	{
		return match ($orderBy) {
			'date' => 'ORDER BY DROP_DATE',
			default => 'ORDER BY DROP_ID',
		};
	}

	private function bindEntity(ThurdyDrop $entity): array
	{
		return [
			':dropLocation' => $entity->dropLocation,
			':dropDesc' => $entity->dropDesc,
			':dropDate' => $entity->dropDate,
			':dropImage' => $entity->dropImage,
		];
	}
}
