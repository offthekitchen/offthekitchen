<?php
/*
*******************************************************************
SongRepository.php
Query and persistence for the SONG table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class SongRepository
{
	private const SELECT_COLUMNS = 'SONG_ID, CD_ID, TRACK_NUMBER, SONG_NAME, SONG_IMAGE,
		SONG_THUMBNAIL, SONG_DESCRIPTION, PURCHASE_LINK, LYRICS_HTML, SAMPLE_MP3,
		RELEASE_DATE, RUN_TIME, ISRC, CATALOG_NUMBER, UPC, ARTIST_ID, BMI_NUMBER,
		ACTIVE, DISPLAY_ON_SITE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single song by primary key.
	 */
	public function findById(int $id): ?Song
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM SONG
		         WHERE SONG_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Song::fromRow($row) : null;
	}

	/**
	 * Find songs for an artist, optionally filtered by ACTIVE.
	 * Default order matches legacy TRACK_NUMBER ordering.
	 *
	 * @return Song[]
	 */
	public function findByArtist(int $artistId, ?bool $active = null, string $orderBy = 'track'): array
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM SONG
		         WHERE ARTIST_ID = :artistId';
		$params = [':artistId' => $artistId];

		if ($active !== null) {
			$sql .= ' AND ACTIVE = :active';
			$params[':active'] = $active ? 1 : 0;
		}

		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Fuzzy match on song name, optionally scoped to an artist.
	 *
	 * @return Song[]
	 */
	public function findByNameFuzzy(string $name, ?int $artistId = null, string $orderBy = 'track'): array
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM SONG
		         WHERE SONG_NAME LIKE :name';
		$params = [':name' => '%' . $name . '%'];

		if ($artistId !== null) {
			$sql .= ' AND ARTIST_ID = :artistId';
			$params[':artistId'] = $artistId;
		}

		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Find songs belonging to a CD (legacy getCDSongs behavior).
	 *
	 * @return Song[]
	 */
	public function findByCdId(int $cdId, string $orderBy = 'track'): array
	{
		return $this->find([
			'cdId' => $cdId,
			'orderBy' => $orderBy,
		]);
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getSong behavior).
	 *
	 * Supported keys: id, name, fuzzyName, cdId, trackNumber, artistId,
	 * active, displayOnSite, orderBy (name|track|update|release)
	 *
	 * @return Song[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM SONG
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND SONG_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND SONG_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		// CD_ID 0 (singles) is a valid filter — do not use empty()
		if (array_key_exists('cdId', $criteria)
			&& $criteria['cdId'] !== null
			&& $criteria['cdId'] !== ''
		) {
			$sql .= ' AND CD_ID = :cdId';
			$params[':cdId'] = (int) $criteria['cdId'];
		}

		if (!empty($criteria['trackNumber'])) {
			$sql .= ' AND TRACK_NUMBER = :trackNumber';
			$params[':trackNumber'] = (int) $criteria['trackNumber'];
		}

		if (!empty($criteria['artistId'])) {
			$sql .= ' AND ARTIST_ID = :artistId';
			$params[':artistId'] = (int) $criteria['artistId'];
		}

		if (array_key_exists('active', $criteria) && $criteria['active'] !== null) {
			$sql .= ' AND ACTIVE = :active';
			$params[':active'] = $criteria['active'] ? 1 : 0;
		}

		if (array_key_exists('displayOnSite', $criteria) && $criteria['displayOnSite'] !== null) {
			$sql .= ' AND DISPLAY_ON_SITE = :displayOnSite';
			$params[':displayOnSite'] = $criteria['displayOnSite'] ? 1 : 0;
		}

		$orderBy = $criteria['orderBy'] ?? 'track';
		$sql .= ' ' . $this->orderByClause($orderBy);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new SONG row. Sets $entity->id on success.
	 */
	public function insert(Song $entity): bool
	{
		$sql = 'INSERT INTO SONG (
		            SONG_NAME, CD_ID, TRACK_NUMBER, SONG_IMAGE, SONG_THUMBNAIL,
		            SONG_DESCRIPTION, PURCHASE_LINK, LYRICS_HTML, RELEASE_DATE,
		            RUN_TIME, ISRC, CATALOG_NUMBER, UPC, ARTIST_ID, SAMPLE_MP3,
		            BMI_NUMBER, ACTIVE, DISPLAY_ON_SITE, LAST_UPDATE
		        ) VALUES (
		            :name, :cdId, :trackNumber, :image, :thumbnail,
		            :description, :purchaseLink, :lyricsHtml, :releaseDate,
		            :runTime, :isrc, :catalogNumber, :upc, :artistId, :sampleMp3,
		            :bmiNumber, :active, :displayOnSite, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing SONG row.
	 */
	public function update(Song $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE SONG
		           SET SONG_NAME = :name,
		               CD_ID = :cdId,
		               TRACK_NUMBER = :trackNumber,
		               SONG_IMAGE = :image,
		               SONG_THUMBNAIL = :thumbnail,
		               SONG_DESCRIPTION = :description,
		               PURCHASE_LINK = :purchaseLink,
		               LYRICS_HTML = :lyricsHtml,
		               SAMPLE_MP3 = :sampleMp3,
		               RELEASE_DATE = :releaseDate,
		               RUN_TIME = :runTime,
		               ISRC = :isrc,
		               CATALOG_NUMBER = :catalogNumber,
		               UPC = :upc,
		               ARTIST_ID = :artistId,
		               BMI_NUMBER = :bmiNumber,
		               ACTIVE = :active,
		               DISPLAY_ON_SITE = :displayOnSite,
		               LAST_UPDATE = SYSDATE()
		         WHERE SONG_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a SONG row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM SONG WHERE SONG_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Song[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Song::fromRow($row);
		}
		return $records;
	}

	private function orderByClause(string $orderBy): string
	{
		return match ($orderBy) {
			'name' => 'ORDER BY SONG_NAME',
			'update' => 'ORDER BY LAST_UPDATE DESC',
			'release' => 'ORDER BY RELEASE_DATE DESC',
			default => 'ORDER BY TRACK_NUMBER',
		};
	}

	private function bindEntity(Song $entity): array
	{
		return [
			':name' => $entity->name,
			':cdId' => $entity->cdId,
			':trackNumber' => $entity->trackNumber,
			':image' => $entity->image,
			':thumbnail' => $entity->thumbnail,
			':description' => $entity->description,
			':purchaseLink' => $entity->purchaseLink,
			':lyricsHtml' => $entity->lyricsHtml,
			':releaseDate' => $entity->releaseDate,
			':runTime' => $entity->runTime,
			':isrc' => $entity->isrc,
			':catalogNumber' => $entity->catalogNumber,
			':upc' => $entity->upc,
			':artistId' => $entity->artistId,
			':sampleMp3' => $entity->sampleMp3,
			':bmiNumber' => $entity->bmiNumber,
			':active' => $entity->active ? 1 : 0,
			':displayOnSite' => $entity->displayOnSite ? 1 : 0,
		];
	}
}
