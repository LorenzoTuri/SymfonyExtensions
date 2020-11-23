<?php

namespace Lturi\SymfonyExtensions\Rest\ViewModel;

class RouteViewModel
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $methods;
    /**
     * @var string
     */
    private $controller;
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $version = "v1";
    /**
     * @var array<string, mixed>
     */
    private $requirements;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RouteViewModel
     */
    public function setName(string $name): RouteViewModel
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethods(): string
    {
        return $this->methods;
    }

    /**
     * @param string $methods
     * @return RouteViewModel
     */
    public function setMethods(string $methods): RouteViewModel
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return RouteViewModel
     */
    public function setController(string $controller): RouteViewModel
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return RouteViewModel
     */
    public function setPath(string $path): RouteViewModel
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return RouteViewModel
     */
    public function setVersion(string $version): RouteViewModel
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * @param array<string, mixed> $requirements
     * @return RouteViewModel
     */
    public function setRequirements(array $requirements): RouteViewModel
    {
        $this->requirements = $requirements;
        return $this;
    }
}
