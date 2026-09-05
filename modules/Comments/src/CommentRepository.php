<?php

declare(strict_types=1);

namespace Modules\Comments\src;

use PDO;
use RuntimeException;

final class CommentRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function approved(string $type, int $contentId): array
    {
        $statement = $this->database->prepare(
            "SELECT c.id,c.parent_id,c.user_id,c.guest_name,c.body,c.edited_at,c.created_at,u.username "
            . "FROM comments c LEFT JOIN users u ON u.id=c.user_id WHERE c.content_type=:type AND c.content_id=:content AND c.status='approved' ORDER BY c.created_at,c.id"
        );
        $statement->execute(['type' => $type, 'content' => $contentId]);
        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        if ($data['parent_id'] !== null) {
            $parent = $this->find((int) $data['parent_id']);
            if ($parent === null || $parent['status'] !== 'approved' || $parent['content_type'] !== $data['content_type'] || (int) $parent['content_id'] !== $data['content_id']) {
                throw new RuntimeException('The reply target is invalid.');
            }
            if ($this->depth((int) $parent['id']) >= 5) throw new RuntimeException('Maximum reply depth reached.');
        }
        $statement = $this->database->prepare(
            'INSERT INTO comments (content_type,content_id,parent_id,user_id,guest_name,body,status,ip_hash,created_at,updated_at) '
            . 'VALUES (:content_type,:content_id,:parent_id,:user_id,:guest_name,:body,:status,:ip_hash,UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $statement->execute($data);
        return (int) $this->database->lastInsertId();
    }

    public function report(int $commentId, ?int $userId, string $reporterKey, string $reason): int
    {
        $comment = $this->find($commentId);
        if ($comment === null || $comment['status'] !== 'approved') throw new RuntimeException('Comment not found.');
        try {
            $statement = $this->database->prepare(
                'INSERT INTO comment_reports (comment_id,reporter_user_id,reporter_key,reason,status,created_at) '
                . "VALUES (:comment,:user,:reporter,:reason,'open',UTC_TIMESTAMP())"
            );
            $statement->execute(['comment' => $commentId, 'user' => $userId, 'reporter' => $reporterKey, 'reason' => $reason]);
            return (int) $this->database->lastInsertId();
        } catch (\PDOException $error) {
            if ($error->getCode() === '23000') throw new RuntimeException('You already reported this comment.', 0, $error);
            throw $error;
        }
    }

    public function edit(int $id, int $userId, string $body): void
    {
        $statement = $this->database->prepare(
            "UPDATE comments SET body=:body,edited_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND user_id=:user "
            . "AND status IN ('pending','approved') AND created_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)"
        );
        $statement->execute(['body' => $body, 'id' => $id, 'user' => $userId]);
        if ($statement->rowCount() !== 1) throw new RuntimeException('This comment can no longer be edited.');
    }

    public function moderate(int $id, string $status): void
    {
        $statement = $this->database->prepare('UPDATE comments SET status=:status,updated_at=UTC_TIMESTAMP() WHERE id=:id AND status<>:current_status');
        $statement->bindValue(':status', $status);
        $statement->bindValue(':current_status', $status);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        if ($statement->rowCount() !== 1 && $this->find($id) === null) throw new RuntimeException('Comment not found.');
    }

    public function resolveReport(int $id): void
    {
        $statement = $this->database->prepare("UPDATE comment_reports SET status='resolved',resolved_at=UTC_TIMESTAMP() WHERE id=:id AND status='open'");
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) throw new RuntimeException('Open report not found.');
    }

    public function moderationQueue(): array
    {
        return $this->database->query(
            "SELECT c.id,c.content_type,c.content_id,c.body,c.status,c.created_at,COALESCE(u.username,c.guest_name,'Deleted user') AS author,"
            . "(SELECT COUNT(*) FROM comment_reports r WHERE r.comment_id=c.id AND r.status='open') AS open_reports "
            . "FROM comments c LEFT JOIN users u ON u.id=c.user_id WHERE c.status IN ('pending','approved','spam') ORDER BY (c.status='pending') DESC,c.created_at DESC LIMIT 200"
        )->fetchAll();
    }

    public function openReports(): array
    {
        return $this->database->query(
            "SELECT r.id,r.comment_id,r.reason,r.created_at,c.body,COALESCE(u.username,'Guest') AS reporter "
            . "FROM comment_reports r LEFT JOIN users u ON u.id=r.reporter_user_id INNER JOIN comments c ON c.id=r.comment_id WHERE r.status='open' ORDER BY r.created_at DESC"
        )->fetchAll();
    }

    private function find(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM comments WHERE id=:id');
        $statement->execute(['id' => $id]);
        $comment = $statement->fetch();
        return is_array($comment) ? $comment : null;
    }

    private function depth(int $id): int
    {
        $depth = 0; $seen = [];
        while ($id > 0 && ! isset($seen[$id])) {
            $seen[$id] = true;
            $comment = $this->find($id);
            if ($comment === null || $comment['parent_id'] === null) break;
            $id = (int) $comment['parent_id']; $depth++;
        }
        return $depth;
    }
}
