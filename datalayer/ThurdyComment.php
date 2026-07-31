<?php
/*
*******************************************************************
ThurdyComment.php
Entity representing one row from the thurdy_comment table.
*******************************************************************
*/

namespace Datalayer;

class ThurdyComment
{
	public ?int $id = null;
	public ?string $comment = null;
	public ?string $answer = null;
	public ?string $commentDate = null;
	public ?bool $approved = null;
	public ?string $lastUpdate = null;

	/**
	 * Hydrate an entity from a database row (associative array).
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = isset($row['COMMENT_ID']) ? (int) $row['COMMENT_ID'] : null;
		$entity->comment = $row['COMMENT'] ?? null;
		$entity->answer = $row['ANSWER'] ?? null;
		$entity->commentDate = $row['COMMENT_DATE'] ?? null;
		$entity->approved = isset($row['APPROVED']) ? (bool) $row['APPROVED'] : null;
		$entity->lastUpdate = $row['LAST_UPDATE'] ?? null;
		return $entity;
	}
}
