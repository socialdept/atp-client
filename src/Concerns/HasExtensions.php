<?php

namespace SocialDept\AtpClient\Concerns;

use BadMethodCallException;
use Closure;

trait HasExtensions
{
    /**
     * Registered domain client extensions.
     *
     * @var array<string, Closure>
     */
    protected static array $extensions = [];

    /**
     * Registered request client extensions for existing domains.
     *
     * @var array<string, array<string, Closure>>
     */
    protected static array $domainExtensions = [];

    /**
     * Resolved extension instances (lazy loading).
     *
     * @var array<string, object>
     */
    protected array $resolvedExtensions = [];

    /**
     * Register a domain client extension.
     */
    public static function extend(string $name, Closure $callback): void
    {
        static::$extensions[$name] = $callback;
    }

    /**
     * Register a request client extension for an existing domain.
     */
    public static function extendDomain(string $domain, string $name, Closure $callback): void
    {
        static::$domainExtensions[$domain][$name] = $callback;
    }

    /**
     * Check if an extension is registered.
     */
    public static function hasExtension(string $name): bool
    {
        return isset(static::$extensions[$name]);
    }

    /**
     * Check if a domain extension is registered.
     */
    public static function hasDomainExtension(string $domain, string $name): bool
    {
        return isset(static::$domainExtensions[$domain][$name]);
    }

    /**
     * Get domain extensions for a specific domain.
     */
    public static function getDomainExtensionsFor(string $domain): array
    {
        return static::$domainExtensions[$domain] ?? [];
    }

    /**
     * Flush all registered extensions (useful for testing).
     */
    public static function flushExtensions(): void
    {
        static::$extensions = [];
        static::$domainExtensions = [];
    }

    /**
     * Resolve an extension instance.
     */
    protected function resolveExtension(string $name): object
    {
        if (! isset($this->resolvedExtensions[$name])) {
            $this->resolvedExtensions[$name] = (static::$extensions[$name])($this);
        }

        return $this->resolvedExtensions[$name];
    }

    /**
     * Magic getter for extension access.
     */
    public function __get(string $name): mixed
    {
        if (static::hasExtension($name)) {
            return $this->resolveExtension($name);
        }

        throw new BadMethodCallException(
            sprintf('Property [%s] does not exist on [%s].', $name, static::class)
        );
    }

    /**
     * Magic isset for extension checking.
     */
    public function __isset(string $name): bool
    {
        return static::hasExtension($name);
    }
}
