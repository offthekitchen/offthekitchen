<?php
/*
*******************************************************************
BugRepository.php
Query and persistence for the BUG table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class BugRepository
{
	private const SELECT_COLUMNS = 'BUG_ID, BUG_NAME, NICKNAME, POSITION, CACHE_NAME,
		CACHE_ID, TRACKING_NUMBER, TRACKABLE_ID, REFERENCE_NUMBER, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single bug by primary key.
	 */
	public function findById(int $id): ?Bug
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM BUG
		         WHERE BUG_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Bug::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getBug behavior).
	 *
	 * Supported keys: id, name, fuzzyName, nickName, position, cacheName,
	 * cacheId, trackingNumber, trackableId, referenceNumber
	 *
	 * @return Bug[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM BUG
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND BUG_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND BUG_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['nickName'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND NICKNAME LIKE :nickName';
				$params[':nickName'] = '%' . $criteria['nickName'] . '%';
			} else {
				$sql .= ' AND NICKNAME = :nickName';
				$params[':nickName'] = $criteria['nickName'];
			}
		}

		if (!empty($criteria['position'])) {
			$sql .= ' AND POSITION = :position';
			$params[':position'] = $criteria['position'];
		}

		if (!empty($criteria['cacheName'])) {
			$sql .= ' AND CACHE_NAME = :cacheName';
			$params[':cacheName'] = $criteria['cacheName'];
		}

		if (!empty($criteria['cacheId'])) {
			$sql .= ' AND CACHE_ID = :cacheId';
			$params[':cacheId'] = $criteria['cacheId'];
		}

		if (!empty($criteria['trackingNumber'])) {
			$sql .= ' AND TRACKING_NUMBER = :trackingNumber';
			$params[':trackingNumber'] = $criteria['trackingNumber'];
		}

		if (!empty($criteria['trackableId'])) {
			$sql .= ' AND TRACKABLE_ID = :trackableId';
			$params[':trackableId'] = (int) $criteria['trackableId'];
		}

		if (!empty($criteria['referenceNumber'])) {
			$sql .= ' AND REFERENCE_NUMBER = :referenceNumber';
			$params[':referenceNumber'] = $criteria['referenceNumber'];
		}

		$sql .= ' ORDER BY BUG_NAME DESC';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new BUG row. Sets $entity->id on success.
	 */
	public function insert(Bug $entity): bool
	{
		$sql = 'INSERT INTO BUG (
		            BUG_NAME, NICKNAME, POSITION, CACHE_NAME, CACHE_ID,
		            TRACKING_NUMBER, TRACKABLE_ID, REFERENCE_NUMBER, LAST_UPDATE
		        ) VALUES (
		            :name, :nickName, :position, :cacheName, :cacheId,
		            :trackingNumber, :trackableId, :referenceNumber, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing BUG row.
	 */
	public function update(Bug $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE BUG
		           SET BUG_NAME = :name,
		               NICKNAME = :nickName,
		               POSITION = :position,
		               CACHE_NAME = :cacheName,
		               CACHE_ID = :cacheId,
		               TRACKING_NUMBER = :trackingNumber,
		               TRACKABLE_ID = :trackableId,
		               REFERENCE_NUMBER = :referenceNumber,
		               LAST_UPDATE = SYSDATE()
		         WHERE BUG_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a BUG row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM BUG WHERE BUG_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Bug[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Bug::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(Bug $entity): array
	{
		return [
			':name' => $entity->name,
			':nickName' => $entity->nickName,
			':position' => $entity->position,
			':cacheName' => $entity->cacheName,
			':cacheId' => $entity->cacheId,
			':trackingNumber' => $entity->trackingNumber,
			':trackableId' => $entity->trackableId ?? 0,
			':referenceNumber' => $entity->referenceNumber,
		];
	}
}
