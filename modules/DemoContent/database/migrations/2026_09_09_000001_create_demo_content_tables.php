<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec(<<<'SQL'
CREATE TABLE demo_content_datasets (
    dataset_id VARCHAR(100) PRIMARY KEY,
    status VARCHAR(20) NOT NULL,
    counts JSON NULL,
    modules JSON NULL,
    installed_by BIGINT UNSIGNED NULL,
    installed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT demo_content_installer_fk FOREIGN KEY (installed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        $database->exec(<<<'SQL'
CREATE TABLE demo_content_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dataset_id VARCHAR(100) NOT NULL,
    resource_type VARCHAR(80) NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY demo_content_item_unique (dataset_id,resource_type,resource_id),
    KEY demo_content_items_dataset_index (dataset_id,id),
    CONSTRAINT demo_content_items_dataset_fk FOREIGN KEY (dataset_id) REFERENCES demo_content_datasets(dataset_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP TABLE IF EXISTS demo_content_items');
        $database->exec('DROP TABLE IF EXISTS demo_content_datasets');
    }
};
