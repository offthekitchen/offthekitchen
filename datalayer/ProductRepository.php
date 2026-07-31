<?php
/*
*******************************************************************
ProductRepository.php
Query and persistence for the PRODUCT table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ProductRepository
{
	private const SELECT_COLUMNS = 'PRODUCT_ID, PRODUCT_NAME, ARTIST_ID, IMAGE, THUMBNAIL, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single product by primary key.
	 */
	public function findById(int $id): ?Product
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PRODUCT
		         WHERE PRODUCT_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Product::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance pages.
	 * If id is set, only the primary key is used (legacy getProduct behavior).
	 *
	 * Supported keys: id, name, fuzzyName, artistId, image, thumbnail, orderBy
	 *
	 * @return Product[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PRODUCT
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND PRODUCT_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND PRODUCT_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['artistId'])) {
			$sql .= ' AND ARTIST_ID = :artistId';
			$params[':artistId'] = (int) $criteria['artistId'];
		}

		if (!empty($criteria['image'])) {
			$sql .= ' AND IMAGE = :image';
			$params[':image'] = $criteria['image'];
		}

		if (!empty($criteria['thumbnail'])) {
			$sql .= ' AND THUMBNAIL = :thumbnail';
			$params[':thumbnail'] = $criteria['thumbnail'];
		}

		$sql .= ' ORDER BY PRODUCT_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new PRODUCT row. Sets $entity->id on success.
	 */
	public function insert(Product $entity): bool
	{
		$sql = 'INSERT INTO PRODUCT (
		            PRODUCT_NAME, ARTIST_ID, IMAGE, THUMBNAIL, LAST_UPDATE
		        ) VALUES (
		            :name, :artistId, :image, :thumbnail, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing PRODUCT row.
	 */
	public function update(Product $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE PRODUCT
		           SET PRODUCT_NAME = :name,
		               ARTIST_ID = :artistId,
		               IMAGE = :image,
		               THUMBNAIL = :thumbnail,
		               LAST_UPDATE = SYSDATE()
		         WHERE PRODUCT_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a PRODUCT row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM PRODUCT WHERE PRODUCT_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Product[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Product::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(Product $entity): array
	{
		return [
			':name' => $entity->name,
			':artistId' => is_numeric($entity->artistId) ? $entity->artistId : null,
			':image' => $entity->image,
			':thumbnail' => $entity->thumbnail,
		];
	}
}
