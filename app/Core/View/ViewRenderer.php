<?php

declare(strict_types=1);

namespace NovaNuke\Core\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class ViewRenderer
{
    private readonly Environment $twig;

    public function __construct(string $viewPath, string $cachePath, bool $debug)
    {
        $loader = new FilesystemLoader($viewPath);
        $this->twig = new Environment($loader, [
            'cache' => $debug ? false : $cachePath,
            'debug' => $debug,
            'strict_variables' => $debug,
            'autoescape' => 'html',
        ]);
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
}
