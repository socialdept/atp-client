<?php

namespace SocialDept\AtpClient\Concerns;

use BadMethodCallException;

trait HasDomainExtensions
{
    /**
     * Resolved domain extension instances.
     *
     * @var array<string, object>
     */
    protected array $resolvedDomainExtensions = [];

    /**
     * Get the domain name for this client.
     */
    abstract protected function getDomainName(): string;

    /**
     * Get the root client class for extension lookup.
     */
    abstract protected function getRootClientClass(): string;

    /**
     * Resolve a domain extension instance.
     */
    protected function resolveDomainExtension(string $name): object
    {
        if (! isset($this->resolvedDomainExtensions[$name])) {
            $rootClass = $this->getRootClientClass();
            $extensions = $rootClass::getDomainExtensionsFor($this->getDomainName());
            $this->resolvedDomainExtensions[$name] = ($extensions[$name])($this);
        }

        return $this->resolvedDomainExtensions[$name];
    }

    /**
     * Check if a domain extension exists.
     */
    protected function hasDomainExtension(string $name): bool
    {
        $rootClass = $this->getRootClientClass();

        return $rootClass::hasDomainExtension($this->getDomainName(), $name);
    }

    /**
     * Magic getter for domain extension access.
     */
    public function __get(string $name): mixed
    {
        if ($this->hasDomainExtension($name)) {
            return $this->resolveDomainExtension($name);
        }

        throw new BadMethodCallException(
            sprintf('Property [%s] does not exist on [%s].', $name, static::class)
        );
    }

    /**
     * Magic isset for domain extension checking.
     */
    public function __isset(string $name): bool
    {
        return $this->hasDomainExtension($name);
    }
}
