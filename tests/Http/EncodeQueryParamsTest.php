<?php

namespace SocialDept\AtpClient\Tests\Http;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpClient\Http\HasHttp;

class EncodeQueryParamsTest extends TestCase
{
    private function harness(): object
    {
        return new class
        {
            use HasHttp;

            /**
             * @param  array<string, mixed>  $params
             * @return array<string, mixed>
             */
            public function encode(array $params): array
            {
                return $this->encodeQueryParams($params);
            }
        };
    }

    public function test_encodes_false_as_the_literal_string(): void
    {
        $encoded = $this->harness()->encode([
            'reverse' => false,
            'limit' => 50,
            'cursor' => 'abc',
        ]);

        // XRPC rejects "0"; booleans must serialize as "true"/"false".
        $this->assertSame('false', $encoded['reverse']);
        $this->assertSame(50, $encoded['limit']);
        $this->assertSame('abc', $encoded['cursor']);
    }

    public function test_encodes_true_as_the_literal_string(): void
    {
        $this->assertSame('true', $this->harness()->encode(['reverse' => true])['reverse']);
    }
}
