<?php
/*
*******************************************************************
SearchTermsRepository.php
Query and persistence for the SEARCH_TERMS table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class SearchTermsRepository
{
	private const SELECT_COLUMNS = 'SEARCH_TERMS_ID, WEBSITE, PAGE_NAME, PAGE_URL, SEARCH_TERMS';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find rows matching a website and a search-terms fragment (LIKE %terms%).
	 * Mirrors the Travel Bugs search.php usage of the legacy class.
	 *
	 * @return SearchTerms[]
	 */
	public function findByWebsiteAndTerms(int $website, string $terms): array
	{
		return $this->find([
			'website' => $website,
			'searchTerms' => $terms,
		]);
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getSearchTerms behavior).
	 *
	 * Supported keys: id, website, pageName, pageUrl, searchTerms, fuzzyPageName
	 *
	 * @return SearchTerms[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM SEARCH_TERMS
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['website'])) {
			$sql .= ' AND WEBSITE = :website';
			$params[':website'] = (int) $criteria['website'];
		}

		if (!empty($criteria['pageName'])) {
			if (!empty($criteria['fuzzyPageName'])) {
				$sql .= ' AND PAGE_NAME LIKE :pageName';
				$params[':pageName'] = '%' . $criteria['pageName'] . '%';
			} else {
				$sql .= ' AND PAGE_NAME = :pageName';
				$params[':pageName'] = $criteria['pageName'];
			}
		}

		if (!empty($criteria['pageUrl'])) {
			$sql .= ' AND PAGE_URL LIKE :pageUrl';
			$params[':pageUrl'] = '%' . $criteria['pageUrl'] . '%';
		}

		if (!empty($criteria['searchTerms'])) {
			$sql .= ' AND SEARCH_TERMS LIKE :searchTerms';
			$params[':searchTerms'] = '%' . $criteria['searchTerms'] . '%';
		}

		$sql .= ' ORDER BY PAGE_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Find a single row by primary key.
	 */
	public function findById(int $id): ?SearchTerms
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM SEARCH_TERMS
		         WHERE SEARCH_TERMS_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? SearchTerms::fromRow($row) : null;
	}

	/**
	 * @return SearchTerms[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = SearchTerms::fromRow($row);
		}
		return $records;
	}

	/**
	 * Insert a new SEARCH_TERMS row. Sets $entity->id on success.
	 */
	public function insert(SearchTerms $entity): bool
	{
		$sql = 'INSERT INTO SEARCH_TERMS (WEBSITE, PAGE_NAME, PAGE_URL, SEARCH_TERMS)
		        VALUES (:website, :pageName, :pageUrl, :searchTerms)';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute([
			':website' => $entity->website,
			':pageName' => $entity->pageName,
			':pageUrl' => $entity->pageUrl,
			':searchTerms' => $entity->searchTerms,
		]);

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing SEARCH_TERMS row.
	 */
	public function update(SearchTerms $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE SEARCH_TERMS
		           SET WEBSITE = :website,
		               PAGE_NAME = :pageName,
		               PAGE_URL = :pageUrl,
		               SEARCH_TERMS = :searchTerms
		         WHERE SEARCH_TERMS_ID = :id';

		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			':website' => $entity->website,
			':pageName' => $entity->pageName,
			':pageUrl' => $entity->pageUrl,
			':searchTerms' => $entity->searchTerms,
			':id' => $entity->id,
		]);
	}

	/**
	 * Delete a SEARCH_TERMS row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM SEARCH_TERMS WHERE SEARCH_TERMS_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}
}
