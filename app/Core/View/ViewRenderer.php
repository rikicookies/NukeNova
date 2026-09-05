<?php

declare(strict_types=1);

namespace NovaNuke\Core\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class ViewRenderer
{
    private readonly Environment $twig;
    private readonly FilesystemLoader $loader;

    public function __construct(string $viewPath, string $cachePath, bool $debug)
    {
        $this->loader = new FilesystemLoader($viewPath);
        $this->twig = new Environment($this->loader, [
            'cache' => $debug ? false : $cachePath,
            'debug' => $debug,
            'strict_variables' => $debug,
            'autoescape' => 'html',
        ]);
    }

    public function addNamespace(string $namespace, string $path): void
    {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $namespace)) {
            throw new \InvalidArgumentException('Invalid view namespace.');
        }

        $this->loader->addPath($path, $namespace);
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
}
