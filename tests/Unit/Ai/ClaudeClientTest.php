<?php

declare(strict_types=1);

namespace Lumina\Tests\Unit\Ai;

use Lumina\Ai\ClaudeClient;
use Lumina\Core\Config;
use PHPUnit\Framework\TestCase;

class ClaudeClientTest extends TestCase
{
    public function testIsConfiguredReturnsFalseWithoutApiKey(): void
    {
        $config = new Config(['ai' => ['anthropic' => ['api_key' => '']]]);
        $client = new ClaudeClient($config);
        
        $this->assertFalse($client->isConfigured());
    }

    public function testIsConfiguredReturnsTrueWithApiKey(): void
    {
        $config = new Config(['ai' => ['anthropic' => ['api_key' => 'test-key']]]);
        $client = new ClaudeClient($config);
        
        $this->assertTrue($client->isConfigured());
    }

    public function testGetProviderName(): void
    {
        $config = new Config(['ai' => ['anthropic' => ['api_key' => '']]]);
        $client = new ClaudeClient($config);
        
        $this->assertEquals('anthropic-claude', $client->getProviderName());
    }
}
