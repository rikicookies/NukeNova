<?php

declare(strict_types=1);

namespace Modules\DemoContent\src;

final class NovaTechCommunityDataset
{
    public const PASSWORD = 'NovaDemo!2026-Explore';

    /** @return list<array<string,string>> */
    public static function users(): array
    {
        return [
            ['username'=>'Steve_Jobs_404','display_name'=>'Steve J. 404','role'=>'administrator','bio'=>'Keeps the portal organized and occasionally misplaces the launch button.','location'=>'Cupertino Basement','website'=>'https://example.com/steve-404'],
            ['username'=>'Ramanhuyan','display_name'=>'Ram Anhuyan','role'=>'member','bio'=>'Collects small PHP utilities and even smaller mechanical keyboards.','location'=>'Silicon Barrio','website'=>''],
            ['username'=>'LinusTorvaldo','display_name'=>'Linus Torvaldo','role'=>'member','bio'=>'Kernel enthusiast who believes a CMS should run comfortably on one server.','location'=>'Penguin Bay','website'=>'https://example.com/linus-torvaldo'],
            ['username'=>'AdaByte','display_name'=>'Ada Byte','role'=>'editor','bio'=>'Writes documentation, reviews modules and prefers explicit interfaces.','location'=>'Algorithm Alley','website'=>'https://example.com/ada-byte'],
            ['username'=>'Dennis_Ritchie_Jr','display_name'=>'Dennis R. Jr.','role'=>'member','bio'=>'Enjoys simple tools, readable code and terminals with sensible defaults.','location'=>'C Street','website'=>''],
            ['username'=>'GraceHopperX','display_name'=>'Grace Hopper X','role'=>'member','bio'=>'Debugs systems by documenting exactly what they were supposed to do.','location'=>'Compiler Harbor','website'=>'https://example.com/grace-x'],
            ['username'=>'NikolaCache','display_name'=>'Nikola Cache','role'=>'member','bio'=>'Experiments with caching, queues and questionable amounts of coffee.','location'=>'Alternating Current City','website'=>''],
            ['username'=>'ByteMeMaybe','display_name'=>'Byte Maybe','role'=>'member','bio'=>'Frontend developer testing whether every layout really is responsive.','location'=>'Viewport Heights','website'=>''],
            ['username'=>'KernelPanic','display_name'=>'Kernel Panic','role'=>'member','bio'=>'Breaks staging environments so production users do not have to.','location'=>'Stack Trace Valley','website'=>''],
            ['username'=>'CodeMonkeyMX','display_name'=>'Code Monkey','role'=>'member','bio'=>'Builds practical web tools from México with PHP and too many browser tabs.','location'=>'Commitlán','website'=>''],
            ['username'=>'RootAccess','display_name'=>'Root Access','role'=>'moderator','bio'=>'Security-minded moderator who asks why a process needs that permission.','location'=>'Localhost','website'=>'https://example.com/root-access'],
            ['username'=>'ScriptKiddie42','display_name'=>'Script Kiddie','role'=>'member','bio'=>'Learning secure development one rejected payload at a time.','location'=>'Port 8080','website'=>''],
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function news(): array
    {
        $items = [
            ['NovaNuke 0.2 Development Update','The current alpha focuses on safer content workflows, modular access rules and a practical Wiki.','AdaByte','published','public','release, novanuke'],
            ['Why Modular CMS Architecture Still Matters','Small independent modules let each site install only the features it actually needs.','LinusTorvaldo','published','public','cms, architecture'],
            ['PHP 8.4 Features Worth Using','Modern PHP makes small applications easier to type, validate and maintain without a full framework.','Dennis_Ritchie_Jr','published','member','php, development'],
            ['Shared Hosting Is Not Dead Yet','A well-designed CMS can still provide useful sites on ordinary Apache and MariaDB hosting.','RootAccess','published','public','hosting, apache'],
            ['Markdown vs HTML for CMS Content','Explicit formats make rendering rules understandable while sanitization keeps both options safe.','AdaByte','published','public','markdown, html, security'],
            ['Building Safer Upload Systems in PHP','Treat names, extensions and browser MIME values as untrusted and inspect files on the server.','RootAccess','published','member','php, uploads, security'],
            ['The Return of Lightweight CMS Platforms','Not every website needs containers, queues and several JavaScript build services.','CodeMonkeyMX','published','public','cms, open-source'],
            ['How NovaNuke Modules Communicate','Small events allow optional features to collaborate without editing the core.','GraceHopperX','published','vip','modules, events'],
            ['Why We Still Love Old-School Portals','A homepage with news, navigation and useful community tools remains surprisingly effective.','ByteMeMaybe','draft','public','community, portals'],
            ['NovaNuke Community Roadmap','Stability, consistent UX and strong extension contracts come before feature overload.','Steve_Jobs_404','draft','public','roadmap, novanuke'],
        ];
        return array_map(static function (array $item, int $index): array {
            [$title,$summary,$author,$status,$audience,$tags] = $item;
            $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
            $body = "## {$title}\n\n{$summary}\n\nThe NovaTech community has been testing this area on local machines and inexpensive hosting. The useful lesson is to keep the normal path obvious, validate every boundary and measure before adding infrastructure.\n\n### Community note\n\nShare reproducible results and include the PHP, database and web-server versions when reporting a problem.";
            return compact('title','slug','summary','body','author','status','audience','tags') + [
                'format' => $index === 4 ? 'html' : 'markdown',
                'featured' => in_array($index, [0, 6], true),
                'comments' => $status === 'published',
            ];
        }, $items, array_keys($items));
    }

    /** @return list<array<string,mixed>> */
    public static function pages(): array
    {
        return [
            ['title'=>'About NovaTech Community','slug'=>'about-novatech-community','format'=>'html','access'=>'public','content'=>'<h2>A small modern technology portal</h2><p>NovaTech Community is a fictional demonstration site for NovaNuke. It brings developers together around practical PHP, hosting and open-source tools.</p>'],
            ['title'=>'Community Rules','slug'=>'community-rules','format'=>'markdown','access'=>'public','content'=>"## Be useful and respectful\n\n- Explain the problem before posting a solution.\n- Do not publish secrets, tokens or personal data.\n- Disagree with ideas without attacking people.\n- Use the correct category and a descriptive title."],
            ['title'=>'Getting Started with NovaNuke','slug'=>'getting-started-with-novanuke','format'=>'markdown','access'=>'public','content'=>"## First steps\n\n1. Point the document root to `public/`.\n2. Complete the installer.\n3. Install only the modules the site needs.\n4. Review permissions before inviting users.\n5. Back up the database and private files."],
            ['title'=>'Module Development Basics','slug'=>'module-development-basics','format'=>'markdown','access'=>'members','content'=>"## Keep modules independent\n\nDeclare metadata in `module.json`, register services during `register()` and add routes or listeners during `boot()`. Migrations and data belong to the module that owns them."],
            ['title'=>'Theme Development Guide','slug'=>'theme-development-guide','format'=>'html','access'=>'public','content'=>'<h2>Start with structure</h2><p>Define layouts, partials, module overrides and block positions before styling individual screens.</p><p>Keep templates escaped by default and place public assets under the theme asset directory.</p>'],
            ['title'=>'Hosting Recommendations','slug'=>'hosting-recommendations','format'=>'html','access'=>'public','content'=>'<h2>Choose boring infrastructure</h2><p>PHP 8.3+, PDO MySQL, HTTPS, writable private storage and a document root aimed at <code>public/</code> are more important than a long feature list.</p>'],
            ['title'=>'Security Guidelines','slug'=>'security-guidelines','format'=>'markdown','access'=>'vip','content'=>"## Deployment checklist\n\n- Keep `APP_DEBUG=false` in production.\n- Store secrets only in `.env`.\n- Require CSRF and authorization for every write.\n- Inspect uploads using server-side MIME detection.\n- Test backups by restoring them."],
            ['title'=>'Contact','slug'=>'contact','format'=>'html','access'=>'public','content'=>'<h2>Contact the fictional team</h2><p>For this demonstration, use the internal message system. No message leaves the NovaNuke installation.</p>'],
        ];
    }

    /** @return list<array<string,string>> */
    public static function downloads(): array
    {
        return [
            ['title'=>'NovaNuke Starter Theme','slug'=>'novanuke-starter-theme','url'=>'https://twig.symfony.com/doc/3.x/templates.html','category'=>'themes'],
            ['title'=>'Example Module Skeleton','slug'=>'example-module-skeleton','url'=>'https://www.php.net/manual/en/language.namespaces.php','category'=>'modules'],
            ['title'=>'PHP Configuration Checklist','slug'=>'php-configuration-checklist','url'=>'https://www.php.net/manual/en/ini.core.php','category'=>'checklists'],
            ['title'=>'Shared Hosting Deployment Checklist','slug'=>'shared-hosting-deployment-checklist','url'=>'https://httpd.apache.org/docs/2.4/howto/htaccess.html','category'=>'checklists'],
            ['title'=>'Apache .htaccess Examples','slug'=>'apache-htaccess-examples','url'=>'https://httpd.apache.org/docs/2.4/howto/htaccess.html','category'=>'configuration'],
            ['title'=>'Nginx Reference Configuration','slug'=>'nginx-reference-configuration','url'=>'https://nginx.org/en/docs/http/ngx_http_core_module.html','category'=>'configuration'],
            ['title'=>'Markdown Cheat Sheet','slug'=>'markdown-cheat-sheet','url'=>'https://commonmark.org/help/','category'=>'documentation'],
            ['title'=>'Database Backup Script Example','slug'=>'database-backup-script-example','url'=>'https://mariadb.com/kb/en/mariadb-dump/','category'=>'database'],
            ['title'=>'Module Development Checklist','slug'=>'module-development-checklist','url'=>'https://getcomposer.org/doc/04-schema.md','category'=>'checklists'],
            ['title'=>'Theme Integration Checklist','slug'=>'theme-integration-checklist','url'=>'https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Structuring_content','category'=>'themes'],
        ];
    }

    /** @return list<array<string,string>> */
    public static function links(): array
    {
        return [
            ['PHP','php','https://www.php.net/','The official PHP language website and documentation.'],
            ['Composer','composer','https://getcomposer.org/','Dependency management and autoloading for PHP.'],
            ['Twig','twig','https://twig.symfony.com/','A secure and expressive PHP template engine.'],
            ['CommonMark','commonmark','https://commonmark.org/','A strongly specified Markdown syntax and test suite.'],
            ['GitHub','github','https://github.com/','Source hosting and collaborative development tools.'],
            ['Apache HTTP Server','apache','https://httpd.apache.org/','The web server commonly available on shared hosting.'],
            ['Nginx','nginx','https://nginx.org/','A lightweight HTTP server and reverse proxy.'],
            ['MariaDB','mariadb','https://mariadb.org/','An open-source relational database compatible with NovaNuke.'],
            ["Let's Encrypt",'lets-encrypt','https://letsencrypt.org/','Free automated TLS certificates for HTTPS.'],
            ['OWASP','owasp','https://owasp.org/','Community security guidance for web applications.'],
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function comments(): array
    {
        $bodies = [
            ['AdaByte','I like the explicit ContentRenderer approach. Markdown and HTML remain easier to reason about.'],
            ['KernelPanic','Agreed, although the error messages matter just as much as the renderer.'],
            ['RootAccess','I am most interested in how this behaves on inexpensive shared hosting.'],
            ['LinusTorvaldo','A CMS should not need ten background services just to render a normal page.'],
            ['ByteMeMaybe','The desktop layout still deserves testing at awkward widths, not only mobile presets.'],
            ['CodeMonkeyMX','Prepared statements are boring until the day they save the whole site.'],
            ['GraceHopperX','Documentation should explain why a boundary exists, not repeat the method name.'],
            ['NikolaCache','Measure the query first. Adding cache to a tiny result can create more bugs than speed.'],
            ['Dennis_Ritchie_Jr','Small interfaces and predictable files are still excellent developer experience.'],
            ['ScriptKiddie42','I tried an unsafe URL and was glad the validator rejected it clearly.'],
            ['Ramanhuyan','Can we keep a Laragon example next to the Linux instructions?'],
            ['Steve_Jobs_404','Yes, but the production checklist must stay independent from local tooling.'],
            ['RootAccess','I disagree that every extension needs a setting. Safe defaults reduce maintenance.'],
            ['AdaByte','A setting is worthwhile when two real sites need different behavior.'],
            ['KernelPanic','The rollback test is the one I want to see before calling this stable.'],
            ['GraceHopperX','The module event names are readable and that makes integrations easier to audit.'],
            ['CodeMonkeyMX','Shared hosting compatibility is a feature, not an embarrassment.'],
            ['ByteMeMaybe','The page is clear, but the empty state could offer a direct next action.'],
        ];
        $comments = [];
        foreach ($bodies as $index => [$author,$body]) {
            $comments[] = ['target'=>$index % 3 === 0 ? 'pages' : 'news','target_index'=>$index % 3 === 0 ? ($index % 6) : ($index % 8),'author'=>$author,'body'=>$body,'parent'=>null];
            $replyAuthor = $bodies[($index + 5) % count($bodies)][0];
            $comments[] = ['target'=>$comments[array_key_last($comments)]['target'],'target_index'=>$comments[array_key_last($comments)]['target_index'],'author'=>$replyAuthor,'body'=>'That is a fair point. I would keep the first version small and verify it with a reproducible test.','parent'=>count($comments) - 1];
        }
        return $comments;
    }

    /** @return list<array{question:string,options:list<string>}> */
    public static function polls(): array
    {
        return [
            ['question'=>'Which PHP version are you using?','options'=>['PHP 8.2','PHP 8.3','PHP 8.4','Other']],
            ['question'=>'Preferred web server?','options'=>['Apache','Nginx','Caddy','Other']],
            ['question'=>'Preferred CMS content format?','options'=>['Markdown','HTML','Both']],
            ['question'=>'Where do you host most projects?','options'=>['Shared hosting','VPS','Dedicated server','Local only']],
            ['question'=>'What should NovaNuke improve next?','options'=>['Admin UX','Modules','Themes','Documentation','Performance']],
        ];
    }

    /** @return list<array{0:string,1:string}> */
    public static function friendships(): array
    {
        return [['Steve_Jobs_404','AdaByte'],['AdaByte','LinusTorvaldo'],['LinusTorvaldo','KernelPanic'],['GraceHopperX','AdaByte'],['RootAccess','KernelPanic'],['CodeMonkeyMX','ByteMeMaybe'],['NikolaCache','Dennis_Ritchie_Jr']];
    }

    /** @return list<array<string,string>> */
    public static function messages(): array
    {
        return [
            ['from'=>'AdaByte','to'=>'RootAccess','subject'=>'Hosting checklist review','body'=>'Can you review the shared hosting checklist?'],
            ['from'=>'RootAccess','to'=>'AdaByte','subject'=>'Apache notes','body'=>'Sure. I will test the Apache rules first.'],
            ['from'=>'KernelPanic','to'=>'LinusTorvaldo','subject'=>'ContentRenderer update','body'=>'Did you see the ContentRenderer update?'],
            ['from'=>'LinusTorvaldo','to'=>'KernelPanic','subject'=>'Re: ContentRenderer','body'=>'Yes. Explicit format handling is a good improvement.'],
            ['from'=>'GraceHopperX','to'=>'AdaByte','subject'=>'Documentation pass','body'=>'I marked three places where the security reason needs more context.'],
            ['from'=>'CodeMonkeyMX','to'=>'ByteMeMaybe','subject'=>'Responsive check','body'=>'Can you test the downloads page at tablet width?'],
            ['from'=>'ByteMeMaybe','to'=>'CodeMonkeyMX','subject'=>'Tablet results','body'=>'It works; one long title wraps earlier than expected.'],
            ['from'=>'NikolaCache','to'=>'Dennis_Ritchie_Jr','subject'=>'Query timing','body'=>'The uncached query is already under ten milliseconds locally.'],
            ['from'=>'Dennis_Ritchie_Jr','to'=>'NikolaCache','subject'=>'Re: Query timing','body'=>'Then I would leave the cache out until production data says otherwise.'],
            ['from'=>'Ramanhuyan','to'=>'Steve_Jobs_404','subject'=>'Laragon instructions','body'=>'I added Windows paths to my local installation notes.'],
            ['from'=>'ScriptKiddie42','to'=>'RootAccess','subject'=>'Upload validation question','body'=>'Should the server trust the MIME value sent by the browser?'],
            ['from'=>'RootAccess','to'=>'ScriptKiddie42','subject'=>'Re: Upload validation','body'=>'No. Inspect the file on the server and allow only expected extension and MIME pairs.'],
        ];
    }

    private function __construct()
    {
    }
}
