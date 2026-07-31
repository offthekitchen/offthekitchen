<?php
/*
*******************************************************************
PerformanceSongsRepository.php
Query and persistence for the PERFORMANCE_SONGS table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class PerformanceSongsRepository
{
	private const SELECT_COLUMNS = 'PERFORMANCE_SONG_ID, TITLE, ARTIST, TUNING, CAPO,
		LEARNED, TABS, DEMO, CLEAN, POPULAR, ORIGINAL, RATING, ESTIMATED_TIME,
		EFFECT, NOTES, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single performance song by primary key.
	 */
	public function findById(int $id): ?PerformanceSongs
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PERFORMANCE_SONGS
		         WHERE PERFORMANCE_SONG_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? PerformanceSongs::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getPerformanceSongs behavior).
	 *
	 * Supported keys: id, title, fuzzyTitle, artist, fuzzyArtist, tuning, capo,
	 * learned, tabs, demo, clean, popular, original, rating, lowestRating,
	 * excludeUke, excludePiano, estimatedTime, notes, orderBy (rating|title)
	 *
	 * @return PerformanceSongs[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PERFORMANCE_SONGS
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['title'])) {
			if (!empty($criteria['fuzzyTitle'])) {
				$sql .= ' AND TITLE LIKE :title';
				$params[':title'] = '%' . $criteria['title'] . '%';
			} else {
				$sql .= ' AND TITLE = :title';
				$params[':title'] = $criteria['title'];
			}
		}

		if (!empty($criteria['artist'])) {
			if (!empty($criteria['fuzzyArtist'])) {
				$sql .= ' AND ARTIST LIKE :artist';
				$params[':artist'] = '%' . $criteria['artist'] . '%';
			} else {
				$sql .= ' AND ARTIST = :artist';
				$params[':artist'] = $criteria['artist'];
			}
		}

		if (!empty($criteria['tuning'])) {
			$sql .= ' AND TUNING = :tuning';
			$params[':tuning'] = $criteria['tuning'];
		}

		if (array_key_exists('learned', $criteria) && $criteria['learned'] !== null) {
			$sql .= ' AND LEARNED = :learned';
			$params[':learned'] = $criteria['learned'] ? 1 : 0;
		}

		if (!empty($criteria['tabs'])) {
			$sql .= ' AND TABS = :tabs';
			$params[':tabs'] = $criteria['tabs'];
		}

		if (!empty($criteria['demo'])) {
			$sql .= ' AND DEMO = :demo';
			$params[':demo'] = $criteria['demo'];
		}

		if (array_key_exists('clean', $criteria) && $criteria['clean'] !== null) {
			$sql .= ' AND CLEAN = :clean';
			$params[':clean'] = $criteria['clean'] ? 1 : 0;
		}

		if (array_key_exists('popular', $criteria) && $criteria['popular'] !== null) {
			$sql .= ' AND POPULAR = :popular';
			$params[':popular'] = $criteria['popular'] ? 1 : 0;
		}

		if (array_key_exists('original', $criteria) && $criteria['original'] !== null) {
			$sql .= ' AND ORIGINAL = :original';
			$params[':original'] = $criteria['original'] ? 1 : 0;
		}

		if (!empty($criteria['capo'])) {
			$sql .= ' AND CAPO = :capo';
			$params[':capo'] = $criteria['capo'];
		}

		if (!empty($criteria['rating'])) {
			$sql .= ' AND RATING = :rating';
			$params[':rating'] = (int) $criteria['rating'];
		}

		if (!empty($criteria['lowestRating'])) {
			$sql .= ' AND RATING <= :lowestRating';
			$params[':lowestRating'] = (int) $criteria['lowestRating'];
		}

		if (!empty($criteria['excludeUke'])) {
			$sql .= " AND TUNING <> 'UKULELE'";
		}

		if (!empty($criteria['excludePiano'])) {
			$sql .= " AND TUNING <> 'PIANO'";
		}

		if (!empty($criteria['estimatedTime'])) {
			$sql .= ' AND ESTIMATED_TIME = :estimatedTime';
			$params[':estimatedTime'] = $criteria['estimatedTime'];
		}

		if (!empty($criteria['notes'])) {
			$sql .= ' AND NOTES = :notes';
			$params[':notes'] = $criteria['notes'];
		}

		$orderBy = $criteria['orderBy'] ?? 'title';
		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new PERFORMANCE_SONGS row. Sets $entity->id on success.
	 */
	public function insert(PerformanceSongs $entity): bool
	{
		$sql = 'INSERT INTO PERFORMANCE_SONGS (
		            TITLE, ARTIST, TUNING, CAPO, LEARNED, TABS, DEMO, CLEAN,
		            POPULAR, ORIGINAL, RATING, ESTIMATED_TIME, EFFECT, NOTES, LAST_UPDATE
		        ) VALUES (
		            :title, :artist, :tuning, :capo, :learned, :tabs, :demo, :clean,
		            :popular, :original, :rating, :estimatedTime, :effect, :notes, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing PERFORMANCE_SONGS row.
	 */
	public function update(PerformanceSongs $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE PERFORMANCE_SONGS
		           SET TITLE = :title,
		               ARTIST = :artist,
		               TUNING = :tuning,
		               CAPO = :capo,
		               LEARNED = :learned,
		               TABS = :tabs,
		               DEMO = :demo,
		               CLEAN = :clean,
		               POPULAR = :popular,
		               ORIGINAL = :original,
		               RATING = :rating,
		               ESTIMATED_TIME = :estimatedTime,
		               EFFECT = :effect,
		               NOTES = :notes,
		               LAST_UPDATE = SYSDATE()
		         WHERE PERFORMANCE_SONG_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a PERFORMANCE_SONGS row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM PERFORMANCE_SONGS WHERE PERFORMANCE_SONG_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return PerformanceSongs[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = PerformanceSongs::fromRow($row);
		}
		return $records;
	}

	private function orderByClause(string $orderBy): string
	{
		return match ($orderBy) {
			'rating' => 'ORDER BY RATING, TITLE',
			default => 'ORDER BY TITLE',
		};
	}

	private function bindEntity(PerformanceSongs $entity): array
	{
		$rating = $entity->rating;
		if ($rating !== null && !is_numeric($rating)) {
			$rating = null;
		}

		return [
			':title' => $entity->title,
			':artist' => $entity->artist,
			':tuning' => $entity->tuning,
			':capo' => $entity->capo,
			':learned' => $entity->learned ? 1 : 0,
			':tabs' => $entity->tabs,
			':demo' => $entity->demo,
			':clean' => $entity->clean ? 1 : 0,
			':popular' => $entity->popular ? 1 : 0,
			':original' => $entity->original ? 1 : 0,
			':rating' => $rating,
			':estimatedTime' => $entity->estimatedTime,
			':effect' => $entity->effect,
			':notes' => $entity->notes,
		];
	}
}
