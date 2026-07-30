<?php
/*
*******************************************************************
SearchTerms.php
Entity representing one row from the SEARCH_TERMS table.
*******************************************************************
*/

namespace Datalayer;

class SearchTerms
{
	public ?int $id = null;
	public int $website = 0;
	public ?string $pageName = null;
	public ?string $pageUrl = null;
	public ?string $searchTerms = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['SEARCH_TERMS_ID']) ? (int) $row['SEARCH_TERMS_ID'] : null;
		$entity->website = isset($row['WEBSITE']) ? (int) $row['WEBSITE'] : 0;
		$entity->pageName = $row['PAGE_NAME'] ?? null;
		$entity->pageUrl = $row['PAGE_URL'] ?? null;
		$entity->searchTerms = $row['SEARCH_TERMS'] ?? null;
		return $entity;
	}
}
