<?php
/*
*******************************************************************
ThurdySongRepository.php
Query and persistence for the thurdy_song table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ThurdySongRepository
{
	private const SELECT_COLUMNS = 'SONG_NUMBER, SONG_TITLE, SONG_DESC, SONG_MP3,
		VIDEO_LINK, VIDEO_SUBMITTED_BY, VIDEO_DATE, LIVE_VERSION, ART_IMAGE,
		DROP_IMAGE, DROP_LOCATION, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single thurdy song by primary key (SONG_NUMBER).
	 */
	public function findById(int $id): ?ThurdySong
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_song
		         WHERE SONG_NUMBER = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? ThurdySong::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id or songNumber is set, only the primary key is used (legacy getThurdySong behavior).
	 *
	 * Supported keys: id, songNumber, songTitle, fuzzyName, songDesc, songMp3,
	 * videoLink, videoSubmittedBy, videoDate, liveVersion, artImage, dropImage,
	 * dropLocation, orderBy
	 *
	 * @return ThurdySong[]
	 */
	public function find(array $criteria = []): array
	{
		$songNumber = isset($criteria['songNumber']) ? (int) $criteria['songNumber'] : 0;
		if ($songNumber <= 0 && !empty($criteria['id'])) {
			$songNumber = (int) $criteria['id'];
		}

		if ($songNumber > 0) {
			$entity = $this->findById($songNumber);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_song
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['songTitle'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND SONG_TITLE LIKE :songTitle';
				$params[':songTitle'] = '%' . $criteria['songTitle'] . '%';
			} else {
				$sql .= ' AND SONG_TITLE = :songTitle';
				$params[':songTitle'] = $criteria['songTitle'];
			}
		}

		if (!empty($criteria['songDesc'])) {
			$sql .= ' AND SONG_DESC = :songDesc';
			$params[':songDesc'] = $criteria['songDesc'];
		}

		if (!empty($criteria['songMp3'])) {
			$sql .= ' AND SONG_MP3 = :songMp3';
			$params[':songMp3'] = $criteria['songMp3'];
		}

		if (!empty($criteria['videoLink'])) {
			$sql .= ' AND VIDEO_LINK = :videoLink';
			$params[':videoLink'] = $criteria['videoLink'];
		}

		if (!empty($criteria['videoSubmittedBy'])) {
			$sql .= ' AND VIDEO_SUBMITTED_BY = :videoSubmittedBy';
			$params[':videoSubmittedBy'] = $criteria['videoSubmittedBy'];
		}

		if (!empty($criteria['videoDate'])) {
			$sql .= ' AND VIDEO_DATE = :videoDate';
			$params[':videoDate'] = $criteria['videoDate'];
		}

		if (!empty($criteria['liveVersion'])) {
			$sql .= ' AND LIVE_VERSION = :liveVersion';
			$params[':liveVersion'] = $criteria['liveVersion'];
		}

		if (!empty($criteria['artImage'])) {
			$sql .= ' AND ART_IMAGE = :artImage';
			$params[':artImage'] = $criteria['artImage'];
		}

		if (!empty($criteria['dropImage'])) {
			$sql .= ' AND DROP_IMAGE = :dropImage';
			$params[':dropImage'] = $criteria['dropImage'];
		}

		if (!empty($criteria['dropLocation'])) {
			$sql .= ' AND DROP_LOCATION = :dropLocation';
			$params[':dropLocation'] = $criteria['dropLocation'];
		}

		if (!empty($criteria['orderBy']) && preg_match('/^[A-Za-z0-9_,\s]+$/', $criteria['orderBy'])) {
			$sql .= ' ORDER BY ' . $criteria['orderBy'];
		} else {
			$sql .= ' ORDER BY SONG_TITLE';
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new thurdy_song row.
	 * Legacy supplies SONG_NUMBER explicitly; lastInsertId is used as fallback.
	 */
	public function insert(ThurdySong $entity): bool
	{
		$sql = 'INSERT INTO thurdy_song (
		            SONG_NUMBER, SONG_TITLE, SONG_DESC, SONG_MP3, VIDEO_LINK,
		            VIDEO_SUBMITTED_BY, VIDEO_DATE, LIVE_VERSION, ART_IMAGE,
		            DROP_IMAGE, DROP_LOCATION, LAST_UPDATE
		        ) VALUES (
		            :songNumber, :songTitle, :songDesc, :songMp3, :videoLink,
		            :videoSubmittedBy, :videoDate, :liveVersion, :artImage,
		            :dropImage, :dropLocation, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok && empty($entity->songNumber)) {
			$entity->songNumber = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing thurdy_song row.
	 */
	public function update(ThurdySong $entity): bool
	{
		if (empty($entity->songNumber)) {
			return false;
		}

		$sql = 'UPDATE thurdy_song
		           SET SONG_TITLE = :songTitle,
		               SONG_DESC = :songDesc,
		               SONG_MP3 = :songMp3,
		               VIDEO_LINK = :videoLink,
		               VIDEO_SUBMITTED_BY = :videoSubmittedBy,
		               VIDEO_DATE = :videoDate,
		               LIVE_VERSION = :liveVersion,
		               ART_IMAGE = :artImage,
		               DROP_IMAGE = :dropImage,
		               DROP_LOCATION = :dropLocation,
		               LAST_UPDATE = SYSDATE()
		         WHERE SONG_NUMBER = :songNumber';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($this->bindEntity($entity));
	}

	/**
	 * Delete a thurdy_song row by primary key (SONG_NUMBER).
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM thurdy_song WHERE SONG_NUMBER = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return ThurdySong[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = ThurdySong::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(ThurdySong $entity): array
	{
		return [
			':songNumber' => $entity->songNumber,
			':songTitle' => $entity->songTitle,
			':songDesc' => $entity->songDesc,
			':songMp3' => $entity->songMp3,
			':videoLink' => $entity->videoLink,
			':videoSubmittedBy' => $entity->videoSubmittedBy,
			':videoDate' => $entity->videoDate,
			':liveVersion' => $entity->liveVersion,
			':artImage' => $entity->artImage,
			':dropImage' => $entity->dropImage,
			':dropLocation' => $entity->dropLocation,
		];
	}
}
