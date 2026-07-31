<?php
/*
*******************************************************************
RadioStationRepository.php
Query and persistence for the RADIO_STATION table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class RadioStationRepository
{
	private const SELECT_COLUMNS = 'STATION_ID, STATION_NAME, FREQUENCY, CITY, STATE,
		SHOW_NAME, URL, SHOW_HOST, TIME_SLOT, REQUEST_EMAIL, REQUEST_PHONE,
		ACTIVE, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single radio station by primary key.
	 */
	public function findById(int $id): ?RadioStation
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM RADIO_STATION
		         WHERE STATION_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? RadioStation::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance pages.
	 * If id is set, only the primary key is used (legacy getRadioStation behavior).
	 *
	 * Supported keys: id, name, fuzzyName, frequency, city, state, showName, url,
	 * showHost, timeSlot, requestEmail, requestPhone, active
	 *
	 * @return RadioStation[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM RADIO_STATION
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['name'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND STATION_NAME LIKE :name';
				$params[':name'] = '%' . $criteria['name'] . '%';
			} else {
				$sql .= ' AND STATION_NAME = :name';
				$params[':name'] = $criteria['name'];
			}
		}

		if (!empty($criteria['frequency'])) {
			$sql .= ' AND FREQUENCY = :frequency';
			$params[':frequency'] = $criteria['frequency'];
		}

		if (!empty($criteria['city'])) {
			$sql .= ' AND CITY = :city';
			$params[':city'] = $criteria['city'];
		}

		if (!empty($criteria['state'])) {
			$sql .= ' AND STATE = :state';
			$params[':state'] = $criteria['state'];
		}

		if (!empty($criteria['showName'])) {
			$sql .= ' AND SHOW_NAME = :showName';
			$params[':showName'] = $criteria['showName'];
		}

		if (!empty($criteria['url'])) {
			$sql .= ' AND URL = :url';
			$params[':url'] = $criteria['url'];
		}

		if (!empty($criteria['showHost'])) {
			$sql .= ' AND SHOW_HOST = :showHost';
			$params[':showHost'] = $criteria['showHost'];
		}

		if (!empty($criteria['timeSlot'])) {
			$sql .= ' AND TIME_SLOT = :timeSlot';
			$params[':timeSlot'] = $criteria['timeSlot'];
		}

		if (!empty($criteria['requestEmail'])) {
			$sql .= ' AND REQUEST_EMAIL = :requestEmail';
			$params[':requestEmail'] = $criteria['requestEmail'];
		}

		if (!empty($criteria['requestPhone'])) {
			$sql .= ' AND REQUEST_PHONE = :requestPhone';
			$params[':requestPhone'] = $criteria['requestPhone'];
		}

		if (array_key_exists('active', $criteria) && $criteria['active'] !== null) {
			$sql .= ' AND ACTIVE = :active';
			$params[':active'] = $criteria['active'] ? 1 : 0;
		}

		$sql .= ' ORDER BY STATION_ID';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new RADIO_STATION row. Sets $entity->id on success.
	 */
	public function insert(RadioStation $entity): bool
	{
		$sql = 'INSERT INTO RADIO_STATION (
		            STATION_NAME, FREQUENCY, CITY, STATE, SHOW_NAME, URL,
		            SHOW_HOST, TIME_SLOT, REQUEST_EMAIL, REQUEST_PHONE,
		            ACTIVE, LAST_UPDATE
		        ) VALUES (
		            :name, :frequency, :city, :state, :showName, :url,
		            :showHost, :timeSlot, :requestEmail, :requestPhone,
		            :active, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing RADIO_STATION row.
	 */
	public function update(RadioStation $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE RADIO_STATION
		           SET STATION_NAME = :name,
		               FREQUENCY = :frequency,
		               CITY = :city,
		               STATE = :state,
		               SHOW_NAME = :showName,
		               URL = :url,
		               SHOW_HOST = :showHost,
		               TIME_SLOT = :timeSlot,
		               REQUEST_EMAIL = :requestEmail,
		               REQUEST_PHONE = :requestPhone,
		               ACTIVE = :active,
		               LAST_UPDATE = SYSDATE()
		         WHERE STATION_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a RADIO_STATION row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM RADIO_STATION WHERE STATION_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return RadioStation[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = RadioStation::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(RadioStation $entity): array
	{
		return [
			':name' => $entity->name,
			':frequency' => $entity->frequency,
			':city' => $entity->city,
			':state' => $entity->state,
			':showName' => $entity->showName,
			':url' => $entity->url,
			':showHost' => $entity->showHost,
			':timeSlot' => $entity->timeSlot,
			':requestEmail' => $entity->requestEmail,
			':requestPhone' => $entity->requestPhone,
			':active' => $entity->active ? 1 : 0,
		];
	}
}
