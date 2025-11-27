<?php

namespace SocialDept\AtpClient\Attributes;

use Attribute;
use SocialDept\AtpClient\Enums\Scope;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequiresScope
{
    public array $scopes;

    /**
     * @param  string|Scope|array<string|Scope>  $scopes  Required scope(s) for this method
     * @param  string|null  $granular  Future granular scope equivalent
     * @param  string  $description  Human-readable description of scope requirement
     */
    public function __construct(
        string|Scope|array $scopes,
        public readonly ?string $granular = null,
        public readonly string $description = '',
    ) {
        $this->scopes = $this->normalizeScopes($scopes);
    }

    protected function normalizeScopes(string|Scope|array $scopes): array
    {
        if (! is_array($scopes)) {
            $scopes = [$scopes];
        }

        return array_map(
            fn ($scope) => $scope instanceof Scope ? $scope->value : $scope,
            $scopes
        );
    }
}
