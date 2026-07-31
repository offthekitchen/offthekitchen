<?php
/*
*******************************************************************
ThurdyCommentRepository.php
Query and persistence for the thurdy_comment table.
*******************************************************************
*/

namespace Datalayer;

use PDO;

class ThurdyCommentRepository
{
	private const SELECT_COLUMNS = 'COMMENT_ID, COMMENT, ANSWER, COMMENT_DATE, APPROVED, LAST_UPDATE';

	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Connection::getPdo();
	}

	/**
	 * Find a single comment by primary key.
	 */
	public function findById(int $id): ?ThurdyComment
	{
		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_comment
		         WHERE COMMENT_ID = :id';

		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();

		return $row ? ThurdyComment::fromRow($row) : null;
	}

	/**
	 * Flexible search used by admin maintenance and public pages.
	 * If id is set, only the primary key is used (legacy getThurdyComment behavior).
	 *
	 * Supported keys: id, comment, answer, fuzzyName, commentDate, approved, limit
	 *
	 * @return ThurdyComment[]
	 */
	public function find(array $criteria = []): array
	{
		$id = isset($criteria['id']) ? (int) $criteria['id'] : 0;

		if ($id > 0) {
			$entity = $this->findById($id);
			return $entity ? [$entity] : [];
		}

		$sql = 'SELECT ' . self::SELECT_COLUMNS . '
		          FROM thurdy_comment
		         WHERE 1 = 1';
		$params = [];

		if (!empty($criteria['comment'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND COMMENT LIKE :comment';
				$params[':comment'] = '%' . $criteria['comment'] . '%';
			} else {
				$sql .= ' AND COMMENT = :comment';
				$params[':comment'] = $criteria['comment'];
			}
		}

		if (!empty($criteria['answer'])) {
			if (!empty($criteria['fuzzyName'])) {
				$sql .= ' AND ANSWER LIKE :answer';
				$params[':answer'] = '%' . $criteria['answer'] . '%';
			} else {
				$sql .= ' AND ANSWER = :answer';
				$params[':answer'] = $criteria['answer'];
			}
		}

		if (!empty($criteria['commentDate'])) {
			$sql .= ' AND COMMENT_DATE = :commentDate';
			$params[':commentDate'] = $criteria['commentDate'];
		}

		if (array_key_exists('approved', $criteria) && $criteria['approved'] !== null) {
			$sql .= ' AND APPROVED = :approved';
			$params[':approved'] = $criteria['approved'] ? 1 : 0;
		}

		$sql .= ' ORDER BY COMMENT_DATE DESC';

		if (!empty($criteria['limit'])) {
			$sql .= ' LIMIT ' . (int) $criteria['limit'];
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $this->hydrateAll($stmt);
	}

	/**
	 * Insert a new thurdy_comment row. Sets $entity->id on success.
	 */
	public function insert(ThurdyComment $entity): bool
	{
		$sql = 'INSERT INTO thurdy_comment (
		            COMMENT, ANSWER, COMMENT_DATE, APPROVED, LAST_UPDATE
		        ) VALUES (
		            :comment, :answer, :commentDate, :approved, SYSDATE()
		        )';

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($this->bindEntity($entity));

		if ($ok) {
			$entity->id = (int) $this->db->lastInsertId();
		}
		return $ok;
	}

	/**
	 * Update an existing thurdy_comment row.
	 */
	public function update(ThurdyComment $entity): bool
	{
		if (empty($entity->id)) {
			return false;
		}

		$sql = 'UPDATE thurdy_comment
		           SET COMMENT = :comment,
		               ANSWER = :answer,
		               COMMENT_DATE = :commentDate,
		               APPROVED = :approved,
		               LAST_UPDATE = SYSDATE()
		         WHERE COMMENT_ID = :id';

		$params = $this->bindEntity($entity);
		$params[':id'] = $entity->id;

		$stmt = $this->db->prepare($sql);
		return $stmt->execute($params);
	}

	/**
	 * Delete a thurdy_comment row by primary key.
	 */
	public function delete(int $id): bool
	{
		$sql = 'DELETE FROM thurdy_comment WHERE COMMENT_ID = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * @return ThurdyComment[]
	 */
	private function hydrateAll(\PDOStatement $stmt): array
	{
		$records = [];
		while ($row = $stmt->fetch()) {
			$records[] = ThurdyComment::fromRow($row);
		}
		return $records;
	}

	private function bindEntity(ThurdyComment $entity): array
	{
		return [
			':comment' => $entity->comment,
			':answer' => !empty($entity->answer) ? $entity->answer : null,
			':commentDate' => !empty($entity->commentDate) ? $entity->commentDate : null,
			':approved' => $entity->approved ? 1 : 0,
		];
	}
}
