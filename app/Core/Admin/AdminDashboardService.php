<?php

declare(strict_types=1);

namespace NovaNuke\Core\Admin;

use NovaNuke\Core\Modules\ModuleManager;
use PDO;

final class AdminDashboardService
{
    public function __construct(
        private readonly PDO $database,
        private readonly ModuleManager $modules,
        private readonly DashboardContentMerger $contentMerger = new DashboardContentMerger(),
    ) {
    }

    /** @param array<string,bool> $permissions
     *  @return array<string,mixed>
     */
    public function build(array $permissions): array
    {
        $inventory = $this->modules->inventory();
        $enabled = static fn (string $slug): bool => (bool) ($inventory[$slug]['enabled'] ?? false);
        $cards = [];
        $recentUsers = [];
        $recentActivity = [];
        $contentGroups = [];

        if ($permissions['users.view'] ?? false) {
            $cards[] = ['label' => 'Registered users', 'value' => $this->count("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"), 'url' => '/admin/users'];
            $cards[] = ['label' => 'Active users', 'value' => $this->count("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL"), 'url' => '/admin/users'];
            $recentUsers = $this->database->query(
                'SELECT id, username, status, created_at FROM users WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5'
            )->fetchAll();
        }
        if (($permissions['news.edit'] ?? false) && $enabled('news') && $this->tableExists('news_articles')) {
            $cards[] = ['label' => 'Published news', 'value' => $this->count("SELECT COUNT(*) FROM news_articles WHERE status = 'published' AND deleted_at IS NULL"), 'url' => '/admin/news'];
            $contentGroups[] = $this->content('news_articles', 'title', 'News', '/admin/news/%d/edit');
        }
        if (($permissions['pages.edit'] ?? false) && $enabled('pages') && $this->tableExists('pages')) {
            $cards[] = ['label' => 'Published pages', 'value' => $this->count("SELECT COUNT(*) FROM pages WHERE status = 'published' AND deleted_at IS NULL"), 'url' => '/admin/pages'];
            $contentGroups[] = $this->content('pages', 'title', 'Page', '/admin/pages/%d/edit');
        }
        if (($permissions['downloads.manage'] ?? false) && $enabled('downloads') && $this->tableExists('downloads')) {
            $cards[] = ['label' => 'Published downloads', 'value' => $this->count("SELECT COUNT(*) FROM downloads WHERE status = 'published' AND deleted_at IS NULL"), 'url' => '/admin/downloads'];
            $contentGroups[] = $this->content('downloads', 'name', 'Download', '/admin/downloads/%d/edit');
        }
        if (($permissions['comments.moderate'] ?? false) && $enabled('comments') && $this->tableExists('comments')) {
            $cards[] = ['label' => 'Pending comments', 'value' => $this->count("SELECT COUNT(*) FROM comments WHERE status = 'pending'"), 'url' => '/admin/comments'];
        }
        if ($permissions['logs.view'] ?? false) {
            $recentActivity = $this->database->query(
                'SELECT al.id, al.action, al.subject_type, al.created_at, u.username AS actor_username '
                . 'FROM activity_logs al LEFT JOIN users u ON u.id = al.actor_user_id ORDER BY al.id DESC LIMIT 8'
            )->fetchAll();
        }

        $moduleStatus = null;
        if ($permissions['modules.manage'] ?? false) {
            $moduleStatus = [
                'detected' => count($inventory),
                'enabled' => count(array_filter($inventory, static fn (array $module): bool => $module['enabled'])),
                'issues' => count(array_filter($inventory, static fn (array $module): bool => ! $module['compatible'] || $module['last_error'] !== null)),
            ];
        }

        return [
            'cards' => $cards,
            'recent_users' => $recentUsers,
            'recent_activity' => $recentActivity,
            'recent_content' => $this->contentMerger->merge($contentGroups),
            'module_status' => $moduleStatus,
        ];
    }

    private function count(string $sql): int
    {
        return (int) $this->database->query($sql)->fetchColumn();
    }

    private function tableExists(string $table): bool
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $statement->execute(['table' => $table]);
        return (int) $statement->fetchColumn() > 0;
    }

    /** @return list<array<string,mixed>> */
    private function content(string $table, string $titleColumn, string $type, string $url): array
    {
        $rows = $this->database->query(
            "SELECT id, `{$titleColumn}` AS title, status, created_at FROM `{$table}` WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5"
        )->fetchAll();
        foreach ($rows as &$row) {
            $row['type'] = $type;
            $row['admin_url'] = sprintf($url, (int) $row['id']);
        }
        unset($row);
        return $rows;
    }
}
