<?php
/*
*******************************************************************
AwardRepository.php
Query and persistence for the AWARD table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class AwardRepository
{
	private const SELECT_COLUMNS = 'AWARD_ID, AWARD_NAME, AWARD_DESCRIPTION, AWARD_DATE,
		AWARD_URL, ARTIST_ID, CD_ID, SONG_ID, PERFORMANCE_RELATED, AWARD_IMAGE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single award by primary key.
	 */
	public function findById(int $id): ?Award
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM AWARD
		         WHERE AWARD_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Award::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getAward behavior).
	 *
	 * Supported keys: id, name, fuzzyName, description, awardDate, url,
	 * artistId, cdId, songId, performanceRelated, orderBy (name|date|update)
	 *
	 * @return Award[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM AWARD
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND AWARD_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND AWARD_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['description'])) {
			$sql .= ' AND AWARD_DESCRIPTION LIKE :description';
			$params[':description'] = '%' . $criteria['description'] . '%';
		}

		if (!empty($criteria['awardDate'])) {
			$sql .= ' AND AWARD_DATE LIKE :awardDate';
			$params[':awardDate'] = '%' . $criteria['awardDate'] . '%';
		}

		if (!empty($criteria['url'])) {
			$sql .= ' AND AWARD_URL = :url';
			$params[':url'] = $criteria['url'];
		}

		if (!empty($criteria['artistId'])) {
			$sql .= ' AND ARTIST_ID = :artistId';
			$params[':artistId'] = (int) $criteria['artistId'];
		}

		if (!empty($criteria['cdId'])) {
			$sql .= ' AND CD_ID = :cdId';
			$params[':cdId'] = (int) $criteria['cdId'];
		}

		if (!empty($criteria['songId'])) {
			$sql .= ' AND SONG_ID = :songId';
			$params[':songId'] = (int) $criteria['songId'];
		}

		if (!empty($criteria['performanceRelated'])) {
			$sql .= ' AND PERFORMANCE_RELATED = :performanceRelated';
			$params[':performanceRelated'] = $criteria['performanceRelated'] ? 1 : 0;
		}

		$orderBy = $criteria['orderBy'] ?? 'date';
		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new AWARD row. Sets $entity->id on success.
	 */
	public function insert(Award $entity): bool
	{
		$sql = 'INSERT INTO AWARD (
		            AWARD_NAME, AWARD_DESCRIPTION, AWARD_DATE, AWARD_URL,
		            ARTIST_ID, CD_ID, SONG_ID, PERFORMANCE_RELATED, AWARD_IMAGE, LAST_UPDATE
		        ) VALUES (
		            :name, :description, :awardDate, :url,
		            :artistId, :cdId, :songId, :performanceRelated, :image, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing AWARD row.
	 */
	public function update(Award $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE AWARD
		           SET AWARD_NAME = :name,
		               AWARD_DESCRIPTION = :description,
		               AWARD_DATE = :awardDate,
		               AWARD_URL = :url,
		               ARTIST_ID = :artistId,
		               CD_ID = :cdId,
		               SONG_ID = :songId,
		               PERFORMANCE_RELATED = :performanceRelated,
		               AWARD_IMAGE = :image,
		               LAST_UPDATE = SYSDATE()
		         WHERE AWARD_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete an AWARD row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM AWARD WHERE AWARD_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Award[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Award::fromRow($row);
		}
		return $records;
	}

	private function orderByClause(string $orderBy): string
	{
		return match ($orderBy) {
			'name' => 'ORDER BY AWARD_NAME',
			'update' => 'ORDER BY LAST_UPDATE DESC',
			default => 'ORDER BY AWARD_DATE DESC',
		};
	}

	private function bindEntity(Award $entity): array
	{
		return [
			':name' => $entity->name,
			':description' => $entity->description,
			':awardDate' => $entity->awardDate,
			':url' => $entity->url,
			':artistId' => $entity->artistId,
			':cdId' => $entity->cdId,
			':songId' => $entity->songId,
			':performanceRelated' => $entity->performanceRelated ? 1 : 0,
			':image' => $entity->image,
		];
	}
}
