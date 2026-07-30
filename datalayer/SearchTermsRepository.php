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
		$sql = 'SELECT SEARCH_TERMS_ID, WEBSITE, PAGE_NAME, PAGE_URL, SEARCH_TERMS
		          FROM SEARCH_TERMS
		         WHERE WEBSITE = :website
		           AND SEARCH_TERMS LIKE :terms
		         ORDER BY PAGE_NAME';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':website' => $website,
			':terms' => '%' . $terms . '%',
		]);

		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = SearchTerms::fromRow($row);
		}
		return $records;
	}

	/**
	 * Find a single row by primary key.
	 */
	public function findById(int $id): ?SearchTerms
	{
		$sql = 'SELECT SEARCH_TERMS_ID, WEBSITE, PAGE_NAME, PAGE_URL, SEARCH_TERMS
		          FROM SEARCH_TERMS
		         WHERE SEARCH_TERMS_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? SearchTerms::fromRow($row) : null;
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
