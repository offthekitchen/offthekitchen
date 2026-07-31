<?php
/*
*******************************************************************
PerformanceRepository.php
Query and persistence for the PERFORMANCE table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class PerformanceRepository
{
	private const SELECT_COLUMNS = 'PERFORMANCE_ID, PERFORMANCE_NAME, PERFORMANCE_WEBSITE,
		PERFORMANCE_DATE, PERFORMANCE_TIME, LOCATION, LOCATION_WEBSITE, LOCATION_ADDR1,
		LOCATION_ADDR2, LOCATION_CITY, LOCATION_STATE, LOCATION_ZIP, DESCRIPTION,
		ADMISSION, TOUR_ID, ARTIST_ID, CONTACT_NAME, CONTACT_EMAIL, CONTACT_PHONE,
		PHOTO_ALBUM, VIDEO_URL, CONTRACT, AIRFARE, HOTEL, RENTAL_CAR, PRE_RECORDED,
		ADULT_SHOW, COLORADO_SESSIONS, BOOKED_DATE, BOOKED_AMOUNT, NOTES, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single performance by primary key.
	 */
	public function findById(int $id): ?Performance
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PERFORMANCE
		         WHERE PERFORMANCE_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? Performance::fromRow($row) : null;
	}

	/**
	 * Upcoming performances for an artist (Travel Bugs home page usage).
	 *
	 * @return Performance[]
	 */
	public function findUpcomingByArtist(int $artistId): array
	{
		return $this->find([
			'artistId' => $artistId,
			'future' => true,
		]);
	}

	/**
	 * Next booked upcoming performance, optionally filtered by adult-show flag.
	 */
	public function findNext(?bool $adultShow = false): ?Performance
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PERFORMANCE
		         WHERE PERFORMANCE_DATE >= CURRENT_DATE
		           AND BOOKED_DATE > \'0000-00-00\'
		           AND ADULT_SHOW = :adultShow
		         ORDER BY PERFORMANCE_DATE ASC, PERFORMANCE_TIME ASC
		         LIMIT 1';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':adultShow' => $adultShow ? 1 : 0]);
		$row = $stmt->fetch();

		return $row ? Performance::fromRow($row) : null;
	}

	/**
	 * Distinct past performance locations (legacy getDistinctPerformanceLocations).
	 * @return array<int, array{LOCATION:string,LOCATION_CITY:string,LOCATION_STATE:string}>
	 */
	public function findDistinctLocations(bool $adultShow = true): array
	{
		$sql = 'SELECT DISTINCT LOCATION, LOCATION_CITY, LOCATION_STATE
		          FROM PERFORMANCE
		         WHERE PERFORMANCE_DATE < CURRENT_DATE
		           AND ADULT_SHOW = :adultShow
		         ORDER BY LOCATION';
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':adultShow' => $adultShow ? 1 : 0]);
		$locations = [];
		while ($row = $stmt->fetch()) {
			$locations[] = $row;
		}
		return $locations;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getPerformance behavior).
	 *
	 * Supported keys include: id, name, fuzzyName, artistId, tourId, excludeTourId,
	 * performanceDate, future, performanceYear, startDate, endDate, location,
	 * fuzzyLocation, locationCity, fuzzyCity, locationState, notes, preRecorded,
	 * adultShow, coloradoSessions, booked, orderBy
	 *
	 * @return Performance[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM PERFORMANCE
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND PERFORMANCE_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND PERFORMANCE_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['performanceDate']) && $criteria['performanceDate'] !== '0000-00-00') {
			$sql .= ' AND PERFORMANCE_DATE LIKE :performanceDate';
			$params[':performanceDate'] = $criteria['performanceDate'] . '%';
		} elseif (array_key_exists('future', $criteria) && $criteria['future'] !== null) {
			if ($criteria['future']) {
				$sql .= ' AND PERFORMANCE_DATE > NOW()';
			} else {
				$sql .= ' AND PERFORMANCE_DATE < NOW()';
			}
		}

		if (!empty($criteria['performanceYear'])) {
			$sql .= ' AND YEAR(PERFORMANCE_DATE) = :performanceYear';
			$params[':performanceYear'] = (int) $criteria['performanceYear'];
		}

		if (!empty($criteria['startDate'])) {
			$sql .= ' AND PERFORMANCE_DATE >= :startDate';
			$params[':startDate'] = $criteria['startDate'];
		}

		if (!empty($criteria['endDate'])) {
			$sql .= ' AND PERFORMANCE_DATE <= :endDate';
			$params[':endDate'] = $criteria['endDate'];
		}

		if (!empty($criteria['performanceTime'])) {
			$sql .= ' AND PERFORMANCE_TIME = :performanceTime';
			$params[':performanceTime'] = $criteria['performanceTime'];
		}

		if (!empty($criteria['location'])) {
			if (!empty($criteria['fuzzyLocation'])) {
				$sql .= ' AND LOCATION LIKE :location';
				$params[':location'] = '%' . $criteria['location'] . '%';
			} else {
				$sql .= ' AND LOCATION = :location';
				$params[':location'] = $criteria['location'];
			}
		}

		if (!empty($criteria['locationCity'])) {
			if (!empty($criteria['fuzzyCity'])) {
				$sql .= ' AND LOCATION_CITY LIKE :locationCity';
				$params[':locationCity'] = '%' . $criteria['locationCity'] . '%';
			} else {
				$sql .= ' AND LOCATION_CITY = :locationCity';
				$params[':locationCity'] = $criteria['locationCity'];
			}
		}

		if (!empty($criteria['locationState'])) {
			$sql .= ' AND LOCATION_STATE = :locationState';
			$params[':locationState'] = $criteria['locationState'];
		}

		if (!empty($criteria['locationZip'])) {
			$sql .= ' AND LOCATION_ZIP = :locationZip';
			$params[':locationZip'] = $criteria['locationZip'];
		}

		if (!empty($criteria['tourId'])) {
			$sql .= ' AND TOUR_ID = :tourId';
			$params[':tourId'] = (int) $criteria['tourId'];
		}

		if (!empty($criteria['excludeTourId'])) {
			$sql .= ' AND (TOUR_ID IS NULL OR TOUR_ID <> :excludeTourId)';
			$params[':excludeTourId'] = (int) $criteria['excludeTourId'];
		}

		if (!empty($criteria['artistId'])) {
			$sql .= ' AND ARTIST_ID = :artistId';
			$params[':artistId'] = (int) $criteria['artistId'];
		}

		if (!empty($criteria['notes'])) {
			$sql .= ' AND NOTES LIKE :notes';
			$params[':notes'] = '%' . $criteria['notes'] . '%';
		}

		if (array_key_exists('preRecorded', $criteria) && $criteria['preRecorded'] !== null) {
			$sql .= ' AND PRE_RECORDED = :preRecorded';
			$params[':preRecorded'] = $criteria['preRecorded'] ? 1 : 0;
		}

		if (array_key_exists('adultShow', $criteria) && $criteria['adultShow'] !== null) {
			$sql .= ' AND ADULT_SHOW = :adultShow';
			$params[':adultShow'] = $criteria['adultShow'] ? 1 : 0;
		}

		if (array_key_exists('coloradoSessions', $criteria) && $criteria['coloradoSessions'] !== null) {
			$sql .= ' AND COLORADO_SESSIONS = :coloradoSessions';
			$params[':coloradoSessions'] = $criteria['coloradoSessions'] ? 1 : 0;
		}

		if (array_key_exists('booked', $criteria) && $criteria['booked'] !== null) {
			if ($criteria['booked']) {
				$sql .= ' AND BOOKED_DATE > \'0000-00-00\'';
			} else {
				$sql .= ' AND (BOOKED_DATE IS NULL OR BOOKED_DATE <= \'0000-00-00\')';
			}
		}

		$orderBy = $criteria['orderBy'] ?? null;
		if (!empty($orderBy) && preg_match('/^[A-Za-z0-9_,\s]+$/', $orderBy)) {
			$sql .= ' ORDER BY ' . $orderBy . ', PERFORMANCE_TIME, PERFORMANCE_DATE';
		} else {
			$sql .= ' ORDER BY PERFORMANCE_DATE, PERFORMANCE_TIME';
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new PERFORMANCE row. Sets $entity->id on success.
	 */
	public function insert(Performance $entity): bool
	{
		$sql = 'INSERT INTO PERFORMANCE (
		            PERFORMANCE_NAME, PERFORMANCE_WEBSITE, PERFORMANCE_DATE, PERFORMANCE_TIME,
		            LOCATION, LOCATION_WEBSITE, LOCATION_ADDR1, LOCATION_ADDR2, LOCATION_CITY,
		            LOCATION_STATE, LOCATION_ZIP, DESCRIPTION, ADMISSION, TOUR_ID, ARTIST_ID,
		            CONTACT_NAME, CONTACT_EMAIL, CONTACT_PHONE, PHOTO_ALBUM, VIDEO_URL,
		            CONTRACT, AIRFARE, HOTEL, RENTAL_CAR, PRE_RECORDED, ADULT_SHOW,
		            COLORADO_SESSIONS, BOOKED_DATE, BOOKED_AMOUNT, NOTES, LAST_UPDATE
		        ) VALUES (
		            :name, :website, :performanceDate, :performanceTime,
		            :location, :locationWebsite, :locationAddr1, :locationAddr2, :locationCity,
		            :locationState, :locationZip, :description, :admission, :tourId, :artistId,
		            :contactName, :contactEmail, :contactPhone, :photoAlbum, :videoUrl,
		            :contract, :airfare, :hotel, :rentalCar, :preRecorded, :adultShow,
		            :coloradoSessions, :bookedDate, :bookedAmount, :notes, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing PERFORMANCE row.
	 */
	public function update(Performance $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE PERFORMANCE
		           SET PERFORMANCE_NAME = :name,
		               PERFORMANCE_WEBSITE = :website,
		               PERFORMANCE_DATE = :performanceDate,
		               PERFORMANCE_TIME = :performanceTime,
		               LOCATION = :location,
		               LOCATION_WEBSITE = :locationWebsite,
		               LOCATION_ADDR1 = :locationAddr1,
		               LOCATION_ADDR2 = :locationAddr2,
		               LOCATION_CITY = :locationCity,
		               LOCATION_STATE = :locationState,
		               LOCATION_ZIP = :locationZip,
		               DESCRIPTION = :description,
		               ADMISSION = :admission,
		               TOUR_ID = :tourId,
		               ARTIST_ID = :artistId,
		               CONTACT_NAME = :contactName,
		               CONTACT_EMAIL = :contactEmail,
		               CONTACT_PHONE = :contactPhone,
		               PHOTO_ALBUM = :photoAlbum,
		               VIDEO_URL = :videoUrl,
		               CONTRACT = :contract,
		               AIRFARE = :airfare,
		               HOTEL = :hotel,
		               RENTAL_CAR = :rentalCar,
		               PRE_RECORDED = :preRecorded,
		               ADULT_SHOW = :adultShow,
		               COLORADO_SESSIONS = :coloradoSessions,
		               BOOKED_DATE = :bookedDate,
		               BOOKED_AMOUNT = :bookedAmount,
		               NOTES = :notes,
		               LAST_UPDATE = SYSDATE()
		         WHERE PERFORMANCE_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a PERFORMANCE row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM PERFORMANCE WHERE PERFORMANCE_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return Performance[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = Performance::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(Performance $entity): array
	{
		$bookedDate = $entity->bookedDate;
		if (empty($bookedDate) || $bookedDate === '0000-00-00') {
			$bookedDate = null;
		}

		$bookedAmount = $entity->bookedAmount;
		if ($bookedAmount === null || $bookedAmount === '' || !is_numeric($bookedAmount)) {
			$bookedAmount = null;
		}

		return [
			':name' => $entity->name,
			':website' => $entity->website,
			':performanceDate' => $entity->performanceDate,
			':performanceTime' => $entity->performanceTime,
			':location' => $entity->location,
			':locationWebsite' => $entity->locationWebsite,
			':locationAddr1' => $entity->locationAddr1,
			':locationAddr2' => $entity->locationAddr2,
			':locationCity' => $entity->locationCity,
			':locationState' => $entity->locationState,
			':locationZip' => $entity->locationZip,
			':description' => $entity->description,
			':admission' => $entity->admission,
			':tourId' => $entity->tourId,
			':artistId' => $entity->artistId,
			':contactName' => $entity->contactName,
			':contactEmail' => $entity->contactEmail,
			':contactPhone' => $entity->contactPhone,
			':photoAlbum' => $entity->photoAlbum,
			':videoUrl' => $entity->videoUrl,
			':contract' => $entity->contract,
			':airfare' => $entity->airfare,
			':hotel' => $entity->hotel,
			':rentalCar' => $entity->rentalCar,
			':preRecorded' => $entity->preRecorded ? 1 : 0,
			':adultShow' => $entity->adultShow ? 1 : 0,
			':coloradoSessions' => $entity->coloradoSessions ? 1 : 0,
			':bookedDate' => $bookedDate,
			':bookedAmount' => $bookedAmount,
			':notes' => $entity->notes,
		];
	}
}
