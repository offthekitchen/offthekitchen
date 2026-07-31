<?php
/*
*******************************************************************
ReviewRepository.php
Query and persistence for the REVIEW table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ReviewRepository
{
	private const SELECT_COLUMNS = 'REVIEW_ID, REVIEW_TEXT, REVIEW_EXCERPT, REVIEW_AUTHOR,
		REVIEW_SOURCE, REVIEW_DATE, REVIEW_URL, INTERNAL_REVIEW_URL, ARTIST_ID, CD_ID,
		SONG_ID, RATING, PERFORMANCE_RELATED, GENERAL, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single review by primary key.
	 */
	public function findById(int $id): ?Review
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM REVIEW
		         WHERE REVIEW_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Review::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getReview behavior).
	 *
	 * Supported keys: id, text, excerpt, author, source, reviewDate, url,
	 * internalReviewUrl, artistId, cdId, songId, rating, performanceRelated,
	 * general, orderBy (date|rating)
	 *
	 * @return Review[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM REVIEW
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['text']) || !empty($criteria['excerpt'])) {
			$parts = [];
			if (!empty($criteria['text'])) {
				$parts[] = 'REVIEW_TEXT LIKE :text';
				$params[':text'] = '%' . $criteria['text'] . '%';
			}
			if (!empty($criteria['excerpt'])) {
				$parts[] = 'REVIEW_EXCERPT LIKE :excerpt';
				$params[':excerpt'] = '%' . $criteria['excerpt'] . '%';
			}
			$sql .= ' AND (' . implode(' OR ', $parts) . ')';
		}

		if (!empty($criteria['author'])) {
			$sql .= ' AND REVIEW_AUTHOR = :author';
			$params[':author'] = $criteria['author'];
		}

		if (!empty($criteria['source'])) {
			$sql .= ' AND REVIEW_SOURCE = :source';
			$params[':source'] = $criteria['source'];
		}

		if (!empty($criteria['reviewDate'])) {
			$sql .= ' AND REVIEW_DATE LIKE :reviewDate';
			$params[':reviewDate'] = $criteria['reviewDate'] . '%';
		}

		if (!empty($criteria['url'])) {
			$sql .= ' AND REVIEW_URL = :url';
			$params[':url'] = $criteria['url'];
		}

		if (!empty($criteria['internalReviewUrl'])) {
			$sql .= ' AND INTERNAL_REVIEW_URL = :internalReviewUrl';
			$params[':internalReviewUrl'] = 1;
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

		if (!empty($criteria['rating'])) {
			$sql .= ' AND RATING = :rating';
			$params[':rating'] = (int) $criteria['rating'];
		}

		if (!empty($criteria['performanceRelated'])) {
			$sql .= ' AND PERFORMANCE_RELATED = :performanceRelated';
			$params[':performanceRelated'] = 1;
		}

		if (!empty($criteria['general'])) {
			$sql .= ' AND GENERAL = :general';
			$params[':general'] = 1;
		}

		$orderBy = $criteria['orderBy'] ?? 'rating';
		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new REVIEW row. Sets $entity->id on success.
	 */
	public function insert(Review $entity): bool
	{
		$sql = 'INSERT INTO REVIEW (
		            REVIEW_TEXT, REVIEW_EXCERPT, REVIEW_AUTHOR, REVIEW_SOURCE,
		            REVIEW_DATE, REVIEW_URL, INTERNAL_REVIEW_URL, ARTIST_ID, CD_ID,
		            SONG_ID, RATING, PERFORMANCE_RELATED, GENERAL, LAST_UPDATE
		        ) VALUES (
		            :text, :excerpt, :author, :source,
		            :reviewDate, :url, :internalReviewUrl, :artistId, :cdId,
		            :songId, :rating, :performanceRelated, :general, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing REVIEW row.
	 */
	public function update(Review $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE REVIEW
		           SET REVIEW_TEXT = :text,
		               REVIEW_EXCERPT = :excerpt,
		               REVIEW_AUTHOR = :author,
		               REVIEW_SOURCE = :source,
		               REVIEW_DATE = :reviewDate,
		               REVIEW_URL = :url,
		               INTERNAL_REVIEW_URL = :internalReviewUrl,
		               ARTIST_ID = :artistId,
		               CD_ID = :cdId,
		               SONG_ID = :songId,
		               RATING = :rating,
		               PERFORMANCE_RELATED = :performanceRelated,
		               GENERAL = :general,
		               LAST_UPDATE = SYSDATE()
		         WHERE REVIEW_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a REVIEW row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM REVIEW WHERE REVIEW_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Review[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Review::fromRow($row);
		}
		return $records;
	}

	private function orderByClause(string $orderBy): string
	{
		return match ($orderBy) {
			'date' => 'ORDER BY REVIEW_DATE',
			default => 'ORDER BY RATING ASC',
		};
	}

	private function bindEntity(Review $entity): array
	{
		return [
			':text' => $entity->text,
			':excerpt' => $entity->excerpt,
			':author' => $entity->author,
			':source' => $entity->source,
			':reviewDate' => $entity->reviewDate,
			':url' => $entity->url,
			':internalReviewUrl' => $entity->internalReviewUrl ? 1 : 0,
			':artistId' => $entity->artistId,
			':cdId' => $entity->cdId,
			':songId' => $entity->songId,
			':rating' => $entity->rating,
			':performanceRelated' => $entity->performanceRelated ? 1 : 0,
			':general' => $entity->general ? 1 : 0,
		];
	}
}
