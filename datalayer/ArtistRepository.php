<?php
/*
*******************************************************************
ArtistRepository.php
Query and persistence for the ARTIST table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ArtistRepository
{
	private const SELECT_COLUMNS = 'ARTIST_ID, ARTIST_NAME, ARTIST_IMAGE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single artist by primary key.
	 */
	public function findById(int $id): ?Artist
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM ARTIST
		         WHERE ARTIST_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Artist::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getArtist behavior).
	 *
	 * Supported keys: id, name, fuzzyName, image
	 *
	 * @return Artist[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM ARTIST
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND ARTIST_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND ARTIST_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['image'])) {
			$sql .= ' AND ARTIST_IMAGE = :image';
			$params[':image'] = $criteria['image'];
		}

		$sql .= ' ORDER BY LAST_UPDATE DESC';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new ARTIST row. Sets $entity->id on success.
	 */
	public function insert(Artist $entity): bool
	{
		$sql = 'INSERT INTO ARTIST (ARTIST_NAME, ARTIST_IMAGE, LAST_UPDATE)
		        VALUES (:name, :image, SYSDATE())';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([
			':name' => $entity->name,
			':image' => $entity->image,
		]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing ARTIST row.
	 */
	public function update(Artist $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE ARTIST
		           SET ARTIST_NAME = :name,
		               ARTIST_IMAGE = :image,
		               LAST_UPDATE = SYSDATE()
		         WHERE ARTIST_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':name' => $entity->name,
			':image' => $entity->image,
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete an ARTIST row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM ARTIST WHERE ARTIST_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Artist[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Artist::fromRow($row);
		}
		return $records;
	}
}
