<?php

declare(strict_types=1);

namespace NovaNuke\Core\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use NovaNuke\Core\I18n\Translator;

final class ViewRenderer
{
    private readonly Environment $twig;
    private readonly FilesystemLoader $loader;

    public function __construct(string $viewPath, string $cachePath, bool $debug, ?Translator $translator = null)
    {
        $this->loader = new FilesystemLoader($viewPath);
        $this->twig = new Environment($this->loader, [
            'cache' => $debug ? false : $cachePath,
            'debug' => $debug,
            'strict_variables' => $debug,
            'autoescape' => 'html',
        ]);
        if ($translator !== null) {
            $this->twig->addFunction(new TwigFunction(
                'trans',
                static fn (string $key, array $parameters = []): string => $translator->translate($key, $parameters),
            ));
        }
    }

    public function addNamespace(string $namespace, string $path): void
    {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $namespace)) {
            throw new \InvalidArgumentException('Invalid view namespace.');
        }

        $this->loader->addPath($path, $namespace);
    }

    public function prependPath(string $path): void
    {
        $this->loader->prependPath($path);
    }

    public function prependNamespace(string $namespace, string $path): void
    {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $namespace)) {
            throw new \InvalidArgumentException('Invalid view namespace.');
        }

        $this->loader->prependPath($path, $namespace);
    }

    public function addGlobal(string $name, mixed $value): void
    {
        $this->twig->addGlobal($name, $value);
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
}
