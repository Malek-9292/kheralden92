<?php

namespace Tests\Unit;

use Tests\TestCase;

class RedisConfigTest extends TestCase
{
    public function test_database_redis_defines_cache_connection_for_cache_store(): void
    {
        $redis = config('database.redis');

        $this->assertIsArray($redis);
        $this->assertArrayHasKey('default', $redis);
        $this->assertArrayHasKey('cache', $redis);
        $this->assertSame('1', (string) $redis['cache']['database']);
    }
}
