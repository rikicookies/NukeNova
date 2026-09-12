<?php

declare(strict_types=1);

namespace Modules\DemoContent\src;

use NovaNuke\Core\Comments\CommentCreated;
use Modules\Comments\src\CommentRepository;
use Modules\Downloads\src\DownloadInput;
use Modules\Downloads\src\DownloadRepository;
use Modules\Friends\src\FriendService;
use Modules\News\src\ContentChanged;
use Modules\News\src\NewsInput;
use Modules\News\src\NewsRepository;
use Modules\Pages\src\PageChanged;
use Modules\Pages\src\PageInput;
use Modules\Pages\src\PageRepository;
use Modules\Polls\src\PollInput;
use Modules\Polls\src\PollRepository;
use Modules\PrivateMessages\src\PrivateMessageService;
use Modules\WebLinks\src\WebLinkInput;
use Modules\WebLinks\src\WebLinkRepository;
use NovaNuke\Core\Access\EntitlementService;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Events\EventDispatcher;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class DemoContentInstaller
{
    public const DATASET = 'novatech-community-v1';

    public function __construct(
        private readonly PDO $database,
        private readonly Container $container,
        private readonly EntitlementService $entitlements,
        private readonly EventDispatcher $events,
    ) {
    }

    /** @return array<string,mixed>|null */
    public function status(): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM demo_content_datasets WHERE dataset_id=:dataset LIMIT 1');
        $statement->execute(['dataset' => self::DATASET]);
        $state = $statement->fetch();
        if (! is_array($state)) return null;
        $state['counts'] = $this->jsonArray($state['counts'] ?? null);
        $state['modules'] = $this->jsonArray($state['modules'] ?? null);
        return $state;
    }

    /** @return array<string,bool> */
    public function availableModules(): array
    {
        return [
            'News' => $this->container->has(NewsRepository::class),
            'Pages' => $this->container->has(PageRepository::class),
            'Downloads' => $this->container->has(DownloadRepository::class),
            'Web Links' => $this->container->has(WebLinkRepository::class),
            'Comments' => $this->container->has(CommentRepository::class),
            'Polls' => $this->container->has(PollRepository::class),
            'Friends' => $this->container->has(FriendService::class),
            'Private Messages' => $this->container->has(PrivateMessageService::class),
            'Notifications (event listener)' => $this->container->has(\Modules\Notifications\src\NotificationRepository::class),
            'Search (automatic providers)' => $this->container->has(\Modules\Search\src\SearchRepository::class),
            'Statistics (derived automatically)' => $this->container->has(\Modules\Statistics\src\StatisticsRepository::class),
        ];
    }

    /** @return array{counts:array<string,int>,modules:list<string>} */
    public function install(int $actorId): array
    {
        if ($actorId < 1) throw new RuntimeException('A valid administrator is required.');
        $this->assertReservedNewsTagsAreAvailable();
        $this->beginDataset($actorId);
        $counts = [];
        $modules = [];
        try {
            $users = $this->createUsers();
            $counts['Users'] = count($users);
            $this->createEntitlements($users, $actorId);
            $counts['VIP entitlements'] = 3;

            $news = $this->createNews($users, $actorId);
            if ($news !== []) { $counts['News'] = count($news); $modules[] = 'news'; }
            $pages = $this->createPages($users, $actorId);
            if ($pages !== []) { $counts['Pages'] = count($pages); $modules[] = 'pages'; }
            $downloads = $this->createDownloads($users, $actorId);
            if ($downloads !== []) { $counts['Downloads'] = count($downloads); $modules[] = 'downloads'; }
            $links = $this->createLinks($users);
            if ($links !== []) { $counts['Web Links'] = count($links); $modules[] = 'web-links'; }
            $comments = $this->createComments($users, $news, $pages);
            if ($comments > 0) { $counts['Comments'] = $comments; $modules[] = 'comments'; }
            $polls = $this->createPolls($users, $actorId);
            if ($polls > 0) { $counts['Polls'] = $polls; $modules[] = 'polls'; }
            $friends = $this->createFriends($users);
            if ($friends > 0) { $counts['Friendships'] = $friends; $modules[] = 'friends'; }
            $messages = $this->createMessages($users);
            if ($messages > 0) { $counts['Private conversations'] = $messages; $modules[] = 'private-messages'; }
            if ($this->container->has(\Modules\Notifications\src\NotificationRepository::class)) $modules[] = 'notifications';
            if ($this->container->has(\Modules\Search\src\SearchRepository::class)) $modules[] = 'search';
            if ($this->container->has(\Modules\Statistics\src\StatisticsRepository::class)) $modules[] = 'statistics';

            $statement = $this->database->prepare(
                "UPDATE demo_content_datasets SET status='installed',counts=:counts,modules=:modules,installed_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE dataset_id=:dataset AND status='installing'"
            );
            $statement->execute([
                'counts' => json_encode($counts, JSON_THROW_ON_ERROR),
                'modules' => json_encode(array_values(array_unique($modules)), JSON_THROW_ON_ERROR),
                'dataset' => self::DATASET,
            ]);
            if ($statement->rowCount() !== 1) throw new RuntimeException('Demo dataset state could not be finalized.');
            return ['counts' => $counts, 'modules' => array_values(array_unique($modules))];
        } catch (Throwable $error) {
            try { $this->cleanupFailedInstallation(); } catch (Throwable $cleanupError) {
                throw new RuntimeException('Demo content installation failed and automatic cleanup also failed: ' . $cleanupError->getMessage(), 0, $error);
            }
            throw new RuntimeException('Demo content installation failed without changing existing content: ' . $error->getMessage(), 0, $error);
        }
    }

    private function beginDataset(int $actorId): void
    {
        try {
            $statement = $this->database->prepare(
                "INSERT INTO demo_content_datasets(dataset_id,status,installed_by,created_at,updated_at) VALUES(:dataset,'installing',:actor,UTC_TIMESTAMP(),UTC_TIMESTAMP())"
            );
            $statement->execute(['dataset' => self::DATASET, 'actor' => $actorId]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') throw new RuntimeException('This demo dataset is already installed or currently being installed.', 0, $error);
            throw $error;
        }
    }

    /** @return array<string,int> */
    private function createUsers(): array
    {
        $roles = [];
        foreach ($this->database->query('SELECT id,slug FROM roles')->fetchAll() as $role) $roles[(string) $role['slug']] = (int) $role['id'];
        if (! isset($roles['member'])) throw new RuntimeException('The Member role is required for demo accounts.');
        $ids = [];
        $this->database->beginTransaction();
        try {
            $user = $this->database->prepare(
                "INSERT INTO users(username,email,password_hash,must_change_password,auth_version,status,email_verified_at,created_at,updated_at) VALUES(:username,:email,:password,1,1,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())"
            );
            $profile = $this->database->prepare(
                'INSERT INTO user_profiles(user_id,display_name,bio,bio_format,website,location,locale,timezone,preferences,created_at,updated_at) VALUES(:user,:display_name,:bio,\'markdown\',:website,:location,\'en\',\'UTC\',NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP())'
            );
            $assignment = $this->database->prepare('INSERT INTO user_roles(user_id,role_id,created_at) VALUES(:user,:role,UTC_TIMESTAMP())');
            foreach (NovaTechCommunityDataset::users() as $definition) {
                $roleId = $roles[$definition['role']] ?? null;
                if ($roleId === null) throw new RuntimeException('Required role is unavailable: ' . $definition['role']);
                $emailName = strtolower(str_replace('_', '.', $definition['username']));
                $user->execute([
                    'username' => $definition['username'],
                    'email' => $emailName . '@demo.novanuke.test',
                    'password' => password_hash(NovaTechCommunityDataset::PASSWORD, PASSWORD_DEFAULT),
                ]);
                $id = (int) $this->database->lastInsertId();
                $profile->execute([
                    'user' => $id,
                    'display_name' => $definition['display_name'],
                    'bio' => $definition['bio'],
                    'website' => $definition['website'] === '' ? null : $definition['website'],
                    'location' => $definition['location'],
                ]);
                $assignment->execute(['user' => $id, 'role' => $roleId]);
                $this->track('user', $id);
                $ids[$definition['username']] = $id;
            }
            $this->database->commit();
            return $ids;
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) $this->database->rollBack();
            throw $error;
        }
    }

    /** @param array<string,int> $users */
    private function createEntitlements(array $users, int $actorId): void
    {
        $this->entitlements->grant($users['LinusTorvaldo'], EntitlementService::VIP, 90, $actorId);
        $this->entitlements->grant($users['GraceHopperX'], EntitlementService::VIP, 30, $actorId);
        $statement = $this->database->prepare(
            "INSERT INTO user_entitlements(user_id,entitlement,starts_at,expires_at,granted_by,created_at,updated_at) VALUES(:user,'vip',DATE_SUB(UTC_TIMESTAMP(),INTERVAL 60 DAY),DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY),:actor,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 60 DAY),UTC_TIMESTAMP())"
        );
        $statement->execute(['user' => $users['ScriptKiddie42'], 'actor' => $actorId]);
    }

    /** @param array<string,int> $users @return list<int> */
    private function createNews(array $users, int $actorId): array
    {
        if (! $this->container->has(NewsRepository::class) || ! $this->container->has(NewsInput::class)) return [];
        $repository = $this->container->get(NewsRepository::class);
        $input = $this->container->get(NewsInput::class);
        $category = $repository->saveTaxonomy('category', $input->taxonomy(['name'=>'NovaTech News','slug'=>'novatech-news','description'=>'Technology and NovaNuke community updates.']));
        $this->track('news_category', $category);
        $topic = $repository->saveTaxonomy('topic', $input->taxonomy(['name'=>'Development','slug'=>'novatech-development','description'=>'Practical development discussions.']));
        $this->track('news_topic', $topic);
        $ids = [];
        $trackedTags = [];
        foreach (NovaTechCommunityDataset::news() as $definition) {
            $body = $definition['format'] === 'html'
                ? '<h2>' . htmlspecialchars($definition['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2><p>' . htmlspecialchars($definition['summary'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p><p>Explicit content formats keep rendering predictable and safe.</p>'
                : $definition['body'];
            $data = $input->article([
                'title'=>$definition['title'],'slug'=>$definition['slug'],'summary'=>$definition['summary'],
                'summary_format'=>'markdown','content'=>$body,'content_format'=>$definition['format'],
                'status'=>$definition['status'],'audience'=>$definition['audience'],'category_id'=>$category,'topic_id'=>$topic,
                'tags'=>$this->demoTags($definition['tags']),'is_featured'=>$definition['featured']?'1':'0','comments_enabled'=>$definition['comments']?'1':'0',
                'seo_title'=>$definition['title'],'seo_description'=>$definition['summary'],
            ], true);
            $id = $repository->save(null, $data, $users[$definition['author']] ?? $actorId);
            $this->track('news', $id);
            foreach ($data['tags'] as $slug => $name) {
                if (isset($trackedTags[$slug])) continue;
                $tag = $this->database->prepare('SELECT id FROM news_tags WHERE slug=:slug');
                $tag->execute(['slug' => $slug]);
                $tagId = (int) $tag->fetchColumn();
                if ($tagId < 1) throw new RuntimeException('A demo news tag could not be attributed to the dataset.');
                $this->track('news_tag', $tagId, ['slug' => $slug]);
                $trackedTags[$slug] = true;
            }
            $this->events->dispatch(\NovaNuke\Core\Events\EventName::CONTENT_CREATED, new ContentChanged('news', $id, $actorId));
            $ids[] = $id;
        }
        return $ids;
    }

    /** @param array<string,int> $users @return list<int> */
    private function createPages(array $users, int $actorId): array
    {
        if (! $this->container->has(PageRepository::class) || ! $this->container->has(PageInput::class)) return [];
        $repository = $this->container->get(PageRepository::class);
        $input = $this->container->get(PageInput::class);
        $authors = ['AdaByte','RootAccess','GraceHopperX','CodeMonkeyMX'];
        $ids = [];
        foreach (NovaTechCommunityDataset::pages() as $index => $definition) {
            $data = $input->page([
                'title'=>$definition['title'],'slug'=>$definition['slug'],'content'=>$definition['content'],
                'content_format'=>$definition['format'],'status'=>'published','access_type'=>$definition['access'],
                'template'=>$index===0?'landing':'default','comments_enabled'=>'1','show_in_directory'=>'1',
                'menu_title'=>$definition['title'],'seo_title'=>$definition['title'],
                'seo_description'=>'NovaTech Community demonstration page: ' . $definition['title'],
            ], true);
            $id = $repository->save(null, $data, $users[$authors[$index % count($authors)]] ?? $actorId);
            $this->track('page', $id);
            $this->events->dispatch(\NovaNuke\Core\Events\EventName::CONTENT_CREATED, new PageChanged('pages', $id, $actorId));
            $ids[] = $id;
        }
        return $ids;
    }

    /** @param array<string,int> $users @return list<int> */
    private function createDownloads(array $users, int $actorId): array
    {
        if (! $this->container->has(DownloadRepository::class) || ! $this->container->has(DownloadInput::class)) return [];
        $repository = $this->container->get(DownloadRepository::class);
        $input = $this->container->get(DownloadInput::class);
        $categories = [];
        foreach (['themes'=>'Themes','modules'=>'Modules','checklists'=>'Checklists','configuration'=>'Configuration','documentation'=>'Documentation','database'=>'Database'] as $slug => $name) {
            $categories[$slug] = $repository->saveCategory($input->category(['name'=>'Demo ' . $name,'slug'=>'demo-' . $slug,'description'=>'NovaTech demonstration resources.']));
            $this->track('download_category', $categories[$slug]);
        }
        $ids = [];
        foreach (NovaTechCommunityDataset::downloads() as $index => $definition) {
            $data = $input->download([
                'name'=>$definition['title'],'slug'=>$definition['slug'],
                'description'=>'A curated external reference used by the fictional NovaTech developer community.',
                'description_format'=>'markdown','requirements'=>'A modern web browser and an internet connection.','requirements_format'=>'markdown',
                'version'=>'Reference','author_name'=>'NovaTech Community','source_type'=>'external','external_url'=>$definition['url'],
                'status'=>$index===9?'draft':'published','access_type'=>$index===6?'vip':($index===2?'members':'public'),
                'category_id'=>$categories[$definition['category']] ?? null,'license_name'=>'External documentation','is_featured'=>$index<2?'1':'0',
            ], true) + ['stored_name'=>null,'original_name'=>null,'file_size'=>null,'mime_type'=>null];
            $id = $repository->save(null, $data, $users['CodeMonkeyMX'] ?? $actorId);
            $this->track('download', $id);
            $ids[] = $id;
        }
        return $ids;
    }

    /** @param array<string,int> $users @return list<int> */
    private function createLinks(array $users): array
    {
        if (! $this->container->has(WebLinkRepository::class) || ! $this->container->has(WebLinkInput::class)) return [];
        $repository = $this->container->get(WebLinkRepository::class);
        $input = $this->container->get(WebLinkInput::class);
        $category = $repository->saveCategory($input->category(['name'=>'Demo Developer Resources','slug'=>'demo-developer-resources','description'=>'Official project sites used by the NovaTech demo.']));
        $this->track('web_link_category', $category);
        $authors = array_values($users);
        $ids = [];
        foreach (NovaTechCommunityDataset::links() as $index => [$title,$slug,$url,$description]) {
            $data = $input->link([
                'category_id'=>$category,'title'=>$title,'slug'=>$slug . '-demo','url'=>$url,
                'description'=>$description,'description_format'=>$index % 2===0?'markdown':'html',
                'status'=>'published','audience'=>$index===8?'vip':($index===3?'member':'public'),'is_featured'=>$index<3?'1':'0',
            ], true);
            $id = $repository->save(null, $data, $authors[$index % count($authors)] ?? null);
            $this->track('web_link', $id);
            $ids[] = $id;
        }
        return $ids;
    }

    /** @param array<string,int> $users @param list<int> $news @param list<int> $pages */
    private function createComments(array $users, array $news, array $pages): int
    {
        if (! $this->container->has(CommentRepository::class)) return 0;
        $repository = $this->container->get(CommentRepository::class);
        $commentIds = [];
        $created = 0;
        foreach (NovaTechCommunityDataset::comments() as $index => $definition) {
            $targets = $definition['target'] === 'news' ? $news : $pages;
            if ($targets === []) continue;
            $contentId = $targets[$definition['target_index'] % count($targets)];
            $parent = $definition['parent'] === null ? null : ($commentIds[$definition['parent']] ?? null);
            $id = $repository->create([
                'content_type'=>$definition['target'],'content_id'=>$contentId,'parent_id'=>$parent,
                'user_id'=>$users[$definition['author']] ?? null,'guest_name'=>null,'body'=>$definition['body'],
                'body_format'=>'markdown','status'=>'approved','ip_hash'=>hash('sha256', self::DATASET . ':comment:' . $index),
            ]);
            $commentIds[$index] = $id;
            $this->track('comment', $id);
            $this->events->dispatch(\NovaNuke\Core\Events\EventName::COMMENT_CREATED, new CommentCreated($id, $definition['target'], $contentId, 'approved'));
            $created++;
        }
        $userIds = array_values($users);
        foreach ($commentIds as $index => $commentId) {
            if ($index % 2 !== 0) continue;
            $likes = 1 + ($index % 4);
            for ($offset = 0; $offset < $likes; $offset++) $repository->react($commentId, $userIds[($index + $offset) % count($userIds)], 'like');
            if ($index % 6 === 0) $repository->react($commentId, $userIds[($index + 7) % count($userIds)], 'dislike');
        }
        return $created;
    }

    /** @param array<string,int> $users */
    private function createPolls(array $users, int $actorId): int
    {
        if (! $this->container->has(PollRepository::class)) return 0;
        $repository = $this->container->get(PollRepository::class);
        $input = new PollInput();
        $count = 0;
        foreach (NovaTechCommunityDataset::polls() as $pollIndex => $definition) {
            $data = $input->poll(['question'=>$definition['question'],'options'=>implode("\n", $definition['options']),'status'=>'active']);
            $id = $repository->save(null, $data, $actorId);
            $this->track('poll', $id);
            $options = $repository->find($id)['options'];
            foreach (array_values($users) as $userIndex => $userId) {
                $option = (int) $options[($userIndex + $pollIndex) % count($options)]['id'];
                $repository->vote($id, [$option], $userId, 'demo-user:' . $userId);
            }
            $count++;
        }
        return $count;
    }

    /** @param array<string,int> $users */
    private function createFriends(array $users): int
    {
        if (! $this->container->has(FriendService::class)) return 0;
        $service = $this->container->get(FriendService::class);
        foreach (NovaTechCommunityDataset::friendships() as [$from,$to]) {
            $service->request($users[$from], $users[$to]);
            $service->accept($users[$to], $users[$from]);
        }
        $service->request($users['Ramanhuyan'], $users['ScriptKiddie42']);
        $service->block($users['ScriptKiddie42'], $users['ByteMeMaybe']);
        return count(NovaTechCommunityDataset::friendships()) + 2;
    }

    /** @param array<string,int> $users */
    private function createMessages(array $users): int
    {
        if (! $this->container->has(PrivateMessageService::class)) return 0;
        $service = $this->container->get(PrivateMessageService::class);
        $count = 0;
        foreach (NovaTechCommunityDataset::messages() as $message) {
            $conversation = $service->compose($users[$message['from']], $message['to'], $message['subject'], $message['body'], 'markdown');
            $this->track('private_conversation', $conversation);
            $count++;
        }
        return $count;
    }

    /** @param array<string,scalar|null> $metadata */
    private function track(string $type, int $id, array $metadata = []): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO demo_content_items(dataset_id,resource_type,resource_id,metadata,created_at) VALUES(:dataset,:type,:id,:metadata,UTC_TIMESTAMP())'
        );
        $statement->execute([
            'dataset'=>self::DATASET,'type'=>$type,'id'=>$id,
            'metadata'=>$metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    private function cleanupFailedInstallation(): void
    {
        $items = $this->database->prepare('SELECT resource_type,resource_id FROM demo_content_items WHERE dataset_id=:dataset');
        $items->execute(['dataset'=>self::DATASET]);
        $records = $items->fetchAll();
        $priority = ['comment'=>100,'private_conversation'=>95,'poll'=>90,'web_link'=>85,'download'=>85,'page'=>85,'news'=>85,'news_tag'=>70,'web_link_category'=>60,'download_category'=>60,'news_topic'=>60,'news_category'=>60,'user'=>10];
        usort($records, static fn (array $left, array $right): int => ($priority[(string) $right['resource_type']] ?? 0) <=> ($priority[(string) $left['resource_type']] ?? 0));
        $tables = [
            'comment'=>'comments','private_conversation'=>'private_conversations','poll'=>'polls',
            'web_link'=>'web_links','download'=>'downloads','page'=>'pages','news'=>'news_articles',
            'news_tag'=>'news_tags',
            'web_link_category'=>'web_link_categories','download_category'=>'download_categories',
            'news_topic'=>'news_topics','news_category'=>'news_categories','user'=>'users',
        ];
        $this->database->beginTransaction();
        try {
            foreach ($records as $item) {
                $table = $tables[(string) $item['resource_type']] ?? null;
                if ($table === null) continue;
                $statement = $this->database->prepare("DELETE FROM {$table} WHERE id=:id");
                $statement->execute(['id'=>(int) $item['resource_id']]);
            }
            $this->database->prepare('DELETE FROM demo_content_datasets WHERE dataset_id=:dataset')->execute(['dataset'=>self::DATASET]);
            $this->database->commit();
        } catch (Throwable $error) {
            if ($this->database->inTransaction()) $this->database->rollBack();
            throw $error;
        }
    }

    /** @return array<mixed> */
    private function jsonArray(mixed $value): array
    {
        if (! is_string($value) || $value === '') return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function demoTags(string $tags): string
    {
        $names = array_filter(array_map('trim', explode(',', $tags)), static fn (string $name): bool => $name !== '');
        return implode(', ', array_map(static fn (string $name): string => 'NovaTech Demo ' . $name, $names));
    }

    private function assertReservedNewsTagsAreAvailable(): void
    {
        if (! $this->container->has(NewsRepository::class)) return;
        $count = (int) $this->database->query("SELECT COUNT(*) FROM news_tags WHERE slug LIKE 'novatech-demo-%'")->fetchColumn();
        if ($count > 0) throw new RuntimeException('Reserved NovaTech demo news tags already exist; no existing tags were changed.');
    }
}
