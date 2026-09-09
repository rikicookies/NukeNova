<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE news_articles ADD audience VARCHAR(20) NOT NULL DEFAULT 'public' AFTER status");
        $database->exec('CREATE INDEX news_articles_audience_index ON news_articles (audience, status, published_at)');
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP INDEX news_articles_audience_index ON news_articles');
        $database->exec('ALTER TABLE news_articles DROP COLUMN audience');
    }
};
