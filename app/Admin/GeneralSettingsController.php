<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Modules\ModuleManager;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\Settings\GeneralSettingsInput;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Core\View\ViewRenderer;

final class GeneralSettingsController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly SettingsRepository $settings,
        private readonly GeneralSettingsInput $input,
        private readonly ModuleManager $modules,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
        private readonly SessionManager $session,
        private readonly ViewRenderer $views,
    ) {
    }

    public function show(): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }

        return $this->view($this->currentValues(), [], (bool) $this->session->pull('settings.saved', false));
    }

    public function update(Request $request): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $homepages = $this->homepages();
        $result = $this->input->validate($request->allInput(), $homepages);
        if ($result['errors'] !== []) {
            return $this->view($result['data'], $result['errors'], false, 422, $homepages);
        }

        $data = $result['data'];
        $before = $this->currentValues();
        $this->settings->setMany([
            'site.name' => ['value' => $data['name'], 'type' => 'string', 'group' => 'site'],
            'site.description' => ['value' => $data['description'], 'type' => 'string', 'group' => 'site'],
            'site.url' => ['value' => $data['url'], 'type' => 'string', 'group' => 'site'],
            'site.admin_email' => ['value' => $data['admin_email'], 'type' => 'string', 'group' => 'site'],
            'site.timezone' => ['value' => $data['timezone'], 'type' => 'string', 'group' => 'site'],
            'site.locale' => ['value' => $data['locale'], 'type' => 'string', 'group' => 'site'],
            'site.date_format' => ['value' => $data['date_format'], 'type' => 'string', 'group' => 'site'],
            'site.per_page' => ['value' => (string) $data['per_page'], 'type' => 'integer', 'group' => 'site'],
            'site.homepage' => ['value' => $data['homepage'], 'type' => 'string', 'group' => 'site'],
            'system.maintenance' => ['value' => $data['maintenance'] ? '1' : '0', 'type' => 'boolean', 'group' => 'system'],
        ]);

        $changed = [];
        foreach ($data as $key => $value) {
            if (($before[$key] ?? null) !== $value) {
                $changed[] = $key;
            }
        }
        $user = $this->auth->user();
        $this->activity->log((int) $user['id'], 'settings.general.updated', 'settings', 'general', [
            'changed_keys' => implode(',', $changed),
        ], $request->ip());
        $this->session->put('settings.saved', true);

        return Response::redirect('/admin/settings', 303);
    }

    private function guard(): ?Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }
        if (! $this->authorization->allows((int) $user['id'], 'settings.manage')) {
            return Response::html('Forbidden', 403);
        }

        return null;
    }

    /** @return array<string,mixed> */
    private function currentValues(): array
    {
        $user = $this->auth->user();
        return [
            'name' => $this->settings->string('site.name', 'NovaNuke'),
            'description' => $this->settings->string('site.description', 'A modern modular CMS with an old-school spirit.'),
            'url' => $this->settings->string('site.url', 'http://localhost'),
            'admin_email' => $this->settings->string('site.admin_email', (string) ($user['email'] ?? '')),
            'timezone' => $this->settings->string('site.timezone', 'UTC'),
            'locale' => $this->settings->string('site.locale', 'en'),
            'date_format' => $this->settings->string('site.date_format', 'F j, Y'),
            'per_page' => $this->settings->integer('site.per_page', 10, 5, 100),
            'homepage' => $this->settings->string('site.homepage', 'home'),
            'maintenance' => $this->settings->boolean('system.maintenance', false),
        ];
    }

    /** @return array<string,string> */
    private function homepages(): array
    {
        $available = ['home' => 'NovaNuke welcome page'];
        $labels = ['news' => 'News', 'pages' => 'Pages', 'downloads' => 'Downloads'];
        foreach ($this->modules->inventory() as $slug => $module) {
            if (($module['enabled'] ?? false) && isset($labels[$slug])) {
                $available[$slug] = $labels[$slug];
            }
        }

        return $available;
    }

    /** @param array<string,mixed> $values @param array<string,string> $errors @param array<string,string>|null $homepages */
    private function view(array $values, array $errors, bool $saved, int $status = 200, ?array $homepages = null): Response
    {
        return Response::html($this->views->render('admin/general-settings.twig', [
            'values' => $values,
            'errors' => $errors,
            'saved' => $saved,
            'csrf_token' => $this->csrf->token(),
            'timezones' => timezone_identifiers_list(),
            'date_formats' => GeneralSettingsInput::DATE_FORMATS,
            'homepages' => $homepages ?? $this->homepages(),
        ]), $status);
    }
}
