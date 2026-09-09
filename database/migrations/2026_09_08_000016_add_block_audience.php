<?php
declare(strict_types=1);
use NovaNuke\Core\Database\Migration;
return new class implements Migration {
    public function up(PDO $database):void{$database->exec("ALTER TABLE blocks ADD audience VARCHAR(20) NOT NULL DEFAULT 'public' AFTER visibility_mode");}
    public function down(PDO $database):void{$database->exec('ALTER TABLE blocks DROP COLUMN audience');}
};
