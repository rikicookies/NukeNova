<?php

declare(strict_types=1);

namespace Modules\News\src;

use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Settings\SettingsRepository;

final class RssController
{
    public function __construct(
        private readonly NewsRepository $news, private readonly RssFeedBuilder $feeds,
        private readonly SettingsRepository $settings, private readonly ConfigRepository $config,
    ) {
    }

    public function feed(): Response
    {
        $name = $this->settings->string('site.name', (string) $this->config->get('app.name', 'NovaNuke'));
        $url = $this->settings->string('site.url', (string) $this->config->get('app.url', 'http://localhost'));
        $language = $this->settings->string('site.locale', (string) $this->config->get('app.locale', 'en'));
        $description = $this->settings->string('site.description', "Latest news from {$name}.");
        return Response::xml($this->feeds->build($name, $description, $url, $language, $this->news->rssArticles()), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
