<?php
/*
*******************************************************************
CDRepository.php
Query and persistence for the CD table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class CDRepository
{
	private const SELECT_COLUMNS = 'CD_ID, CD_NAME, CD_SHORT_NAME, CD_DESCRIPTION, CD_IMAGE,
		CD_THUMBNAIL, RELEASE_DATE, RUN_TIME, UPC, ARTIST_ID, PURCHASE_LINK, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single CD by primary key.
	 */
	public function findById(int $id): ?CD
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM CD
		         WHERE CD_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? CD::fromRow($row) : null;
	}

	/**
	 * Find CDs for an artist.
	 * By default excludes the singles placeholder CD (legacy bIncludeSingles = false).
	 *
	 * @return CD[]
	 */
	public function findByArtist(
		int $artistId,
		bool $includeSingles = false,
		string $orderBy = 'release'
	): array {
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM CD
		         WHERE ARTIST_ID = :artistId';
		$params = [':artistId' => $artistId];

		if (!$includeSingles) {
			$sql .= ' AND CD_ID <> :singlesCdId';
			$params[':singlesCdId'] = $this->singlesCdId();
		}

		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Fuzzy match on CD name, optionally scoped to an artist.
	 *
	 * @return CD[]
	 */
	public function findByNameFuzzy(
		string $name,
		?int $artistId = null,
		bool $includeSingles = false,
		string $orderBy = 'release'
	): array {
		$criteria = [
			'name' => $name,
			'fuzzyName' => true,
			'includeSingles' => $includeSingles,
			'orderBy' => $orderBy,
		];
		if ($artistId !== null) {
			$criteria['artistId'] = $artistId;
		}
		return $this->find($criteria);
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getCD behavior).
	 *
	 * Supported keys: id, name, fuzzyName, shortName, artistId,
	 * includeSingles, upc, orderBy (name|update|release)
	 *
	 * @return CD[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM CD
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND CD_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND CD_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['shortName'])) {
			$sql .= ' AND CD_SHORT_NAME = :shortName';
			$params[':shortName'] = $criteria['shortName'];
		}

		if (!empty($criteria['artistId'])) {
			$sql .= ' AND ARTIST_ID = :artistId';
			$params[':artistId'] = (int) $criteria['artistId'];
		}

		if (!empty($criteria['upc'])) {
			$sql .= ' AND UPC LIKE :upc';
			$params[':upc'] = '%' . $criteria['upc'] . '%';
		}

		$includeSingles = !empty($criteria['includeSingles']);
		if (!$includeSingles) {
			$sql .= ' AND CD_ID <> :singlesCdId';
			$params[':singlesCdId'] = $this->singlesCdId();
		}

		$orderBy = $criteria['orderBy'] ?? 'release';
		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new CD row. Sets $entity->id on success.
	 */
	public function insert(CD $entity): bool
	{
		$sql = 'INSERT INTO CD (
		            CD_NAME, CD_SHORT_NAME, CD_DESCRIPTION, CD_IMAGE, CD_THUMBNAIL,
		            RELEASE_DATE, RUN_TIME, UPC, ARTIST_ID, PURCHASE_LINK, LAST_UPDATE
		        ) VALUES (
		            :name, :shortName, :description, :image, :thumbnail,
		            :releaseDate, :runTime, :upc, :artistId, :purchaseLink, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing CD row.
	 */
	public function update(CD $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE CD
		           SET CD_NAME = :name,
		               CD_SHORT_NAME = :shortName,
		               CD_DESCRIPTION = :description,
		               CD_IMAGE = :image,
		               CD_THUMBNAIL = :thumbnail,
		               RELEASE_DATE = :releaseDate,
		               RUN_TIME = :runTime,
		               UPC = :upc,
		               ARTIST_ID = :artistId,
		               PURCHASE_LINK = :purchaseLink,
		               LAST_UPDATE = SYSDATE()
		         WHERE CD_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a CD row by primary key.
	 * Callers should check for related songs first if needed.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM CD WHERE CD_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return CD[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = CD::fromRow($row);
		}
		return $records;
	}

	private function orderByClause(string $orderBy): string
	{
		return match ($orderBy) {
			'name' => 'ORDER BY CD_NAME',
			'update' => 'ORDER BY LAST_UPDATE DESC',
			default => 'ORDER BY RELEASE_DATE DESC',
		};
	}

	private function singlesCdId(): int
	{
		return defined('SINGLES_CD_ID') ? (int) SINGLES_CD_ID : 0;
	}

	private function bindEntity(CD $entity): array
	{
		return [
			':name' => $entity->name,
			':shortName' => $entity->shortName,
			':description' => $entity->description,
			':image' => $entity->image,
			':thumbnail' => $entity->thumbnail,
			':releaseDate' => $entity->releaseDate,
			':runTime' => $entity->runTime,
			':upc' => $entity->upc,
			':artistId' => $entity->artistId,
			':purchaseLink' => $entity->purchaseLink,
		];
	}
}
