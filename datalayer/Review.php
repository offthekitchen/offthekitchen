<?php
/*
*******************************************************************
Review.php
Entity representing one row from the REVIEW table.
*******************************************************************
*/

namespace Datalayer;

class Review
{
	public ?int $id = null;
	public ?string $text = null;
	public ?string $excerpt = null;
	public ?string $author = null;
	public ?string $source = null;
	public ?string $reviewDate = null;
	public ?string $url = null;
	public ?bool $internalReviewUrl = null;
	public ?int $artistId = null;
	public ?int $cdId = null;
	public ?int $songId = null;
	public ?int $rating = null;
	public ?bool $performanceRelated = null;
	public ?bool $general = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['REVIEW_ID']) ? (int) $row['REVIEW_ID'] : null;
		$entity->text = $row['REVIEW_TEXT'] ?? null;
		$entity->excerpt = $row['REVIEW_EXCERPT'] ?? null;
		$entity->author = $row['REVIEW_AUTHOR'] ?? null;
		$entity->source = $row['REVIEW_SOURCE'] ?? null;
		$entity->reviewDate = $row['REVIEW_DATE'] ?? null;
		$entity->url = $row['REVIEW_URL'] ?? null;
		$entity->internalReviewUrl = isset($row['INTERNAL_REVIEW_URL']) ? (bool) $row['INTERNAL_REVIEW_URL'] : null;
		$entity->artistId = isset($row['ARTIST_ID']) && $row['ARTIST_ID'] !== null && $row['ARTIST_ID'] !== ''
			? (int) $row['ARTIST_ID']
			: null;
		$entity->cdId = isset($row['CD_ID']) && $row['CD_ID'] !== null && $row['CD_ID'] !== ''
			? (int) $row['CD_ID']
			: null;
		$entity->songId = isset($row['SONG_ID']) && $row['SONG_ID'] !== null && $row['SONG_ID'] !== ''
			? (int) $row['SONG_ID']
			: null;
		$entity->rating = isset($row['RATING']) && $row['RATING'] !== null && $row['RATING'] !== ''
			? (int) $row['RATING']
			: null;
		$entity->performanceRelated = isset($row['PERFORMANCE_RELATED']) ? (bool) $row['PERFORMANCE_RELATED'] : null;
		$entity->general = isset($row['GENERAL']) ? (bool) $row['GENERAL'] : null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
