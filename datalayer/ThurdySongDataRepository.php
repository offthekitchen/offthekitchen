<?php
/*
*******************************************************************
ThurdySongDataRepository.php
Query and persistence for the thurdy_song_data table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ThurdySongDataRepository
{
	private const SELECT_COLUMNS = 'THURDY_SONG_DATA_ID, SONG_ID, VIDEO_LINK,
		VIDEO_SUBMITTED_BY, LIVE_VERSION, DROP_ID, ART_IMAGE, LAST_UPDATED';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single thurdy_song_data row by primary key.
	 */
	public function findById(int $id): ?ThurdySongData
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_song_data
		         WHERE THURDY_SONG_DATA_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? ThurdySongData::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getThurdySongData behavior).
	 *
	 * Supported keys: id, songId, videoLink, videoSubmittedBy, liveVersion,
	 * dropId, artImage, orderBy
	 *
	 * @return ThurdySongData[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_song_data
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['songId'])) {
			$sql .= ' AND SONG_ID = :songId';
			$params[':songId'] = (int) $criteria['songId'];
		}

		if (!empty($criteria['videoLink'])) {
			$sql .= ' AND VIDEO_LINK = :videoLink';
			$params[':videoLink'] = $criteria['videoLink'];
		}

		if (!empty($criteria['videoSubmittedBy'])) {
			$sql .= ' AND VIDEO_SUBMITTED_BY = :videoSubmittedBy';
			$params[':videoSubmittedBy'] = $criteria['videoSubmittedBy'];
		}

		if (!empty($criteria['liveVersion'])) {
			$sql .= ' AND LIVE_VERSION = :liveVersion';
			$params[':liveVersion'] = $criteria['liveVersion'];
		}

		if (!empty($criteria['dropId'])) {
			$sql .= ' AND DROP_ID = :dropId';
			$params[':dropId'] = (int) $criteria['dropId'];
		}

		if (!empty($criteria['artImage'])) {
			$sql .= ' AND ART_IMAGE = :artImage';
			$params[':artImage'] = $criteria['artImage'];
		}

		if (!empty($criteria['orderBy']) && preg_match('/^[A-Za-z0-9_,\s]+$/', $criteria['orderBy'])) {
			$sql .= ' ORDER BY ' . $criteria['orderBy'];
		} else {
			$sql .= ' ORDER BY SONG_ID';
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new thurdy_song_data row. Sets $entity->id on success.
	 */
	public function insert(ThurdySongData $entity): bool
	{
		$sql = 'INSERT INTO thurdy_song_data (
		            SONG_ID, VIDEO_LINK, VIDEO_SUBMITTED_BY, LIVE_VERSION,
		            DROP_ID, ART_IMAGE, LAST_UPDATED
		        ) VALUES (
		            :songId, :videoLink, :videoSubmittedBy, :liveVersion,
		            :dropId, :artImage, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing thurdy_song_data row.
	 */
	public function update(ThurdySongData $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE thurdy_song_data
		           SET SONG_ID = :songId,
		               VIDEO_LINK = :videoLink,
		               VIDEO_SUBMITTED_BY = :videoSubmittedBy,
		               LIVE_VERSION = :liveVersion,
		               DROP_ID = :dropId,
		               ART_IMAGE = :artImage,
		               LAST_UPDATED = SYSDATE()
		         WHERE THURDY_SONG_DATA_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a thurdy_song_data row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM thurdy_song_data WHERE THURDY_SONG_DATA_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return ThurdySongData[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = ThurdySongData::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(ThurdySongData $entity): array
	{
		return [
			':songId' => $entity->songId,
			':videoLink' => $entity->videoLink,
			':videoSubmittedBy' => $entity->videoSubmittedBy,
			':liveVersion' => $entity->liveVersion,
			':dropId' => $entity->dropId,
			':artImage' => $entity->artImage,
		];
	}
}
