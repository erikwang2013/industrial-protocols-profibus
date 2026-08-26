<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\Profibus\Tests\Unit;

use Erikwang2013\IndustrialProtocols\Profibus\ProfibusProtocol;
use Erikwang2013\IndustrialProtocols\Bridge\BridgeConnector;
use Erikwang2013\IndustrialProtocols\Bridge\BridgeInterface;
use Erikwang2013\IndustrialProtocols\Connection\ConnectionState;
use PHPUnit\Framework\TestCase;

class BridgeConnectorTest extends TestCase
{
    private function fakeBridge(): BridgeInterface
    {
        return new class implements BridgeInterface {
            public bool $opened = false;
            public array $commands = [];

            public function open(): void { $this->opened = true; }
            public function close(): void { $this->opened = false; }

            public function execute(string $command, string|array $data = ''): string
            {
                $this->commands[] = [$command, $data];
                return 'ok';
            }

            public function isReady(): bool { return $this->opened; }
            public function getType(): string { return 'fake'; }
        };
    }

    public function testFullMetadata(): void
    {
        $p = new profibusProtocol();

        $this->assertSame('profibus', $p->getName());
        $this->assertSame('1.1.1', $p->getVersion());
        $this->assertSame(['dp', 'pa', 'fms'], $p->getSupportedVariants());
        $this->assertSame(0, $p->getDefaultPort());
    }

    public function testCreateConnectorWithoutBridgeThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('BridgeInterface');
        (new profibusProtocol())->createConnector([]);
    }

    public function testCreateConnectorWithBridge(): void
    {
        $bridge = $this->fakeBridge();
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);

        $this->assertInstanceOf(BridgeConnector::class, $connector);
        $this->assertSame($bridge, $connector->getBridge());
        $this->assertFalse($connector->isConnected());
    }

    public function testConnectDisconnectLifecycle(): void
    {
        $connector = (new profibusProtocol())->createConnector(['bridge' => $this->fakeBridge()]);

        $connector->connect();
        $this->assertTrue($connector->isConnected());

        $connector->disconnect();
        $this->assertFalse($connector->isConnected());
    }

    public function testReadSinglePointDelegatesToBridge(): void
    {
        $bridge = $this->fakeBridge();
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);

        $result = $connector->read('D0.1');

        $this->assertSame(['D0.1' => 'ok'], $result);
        $this->assertSame([['read', ['address' => 'D0.1']]], $bridge->commands);
    }

    public function testReadMultiplePoints(): void
    {
        $bridge = $this->fakeBridge();
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);

        $result = $connector->read(['D0.1', 'D0.2']);

        $this->assertSame(['D0.1' => 'ok', 'D0.2' => 'ok'], $result);
        $this->assertCount(2, $bridge->commands);
    }

    public function testWriteWithSequentialValues(): void
    {
        $bridge = $this->fakeBridge();
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);

        $result = $connector->write('D0.1', [1]);

        $this->assertSame(['D0.1' => 'ok'], $result);
        $this->assertSame([['write', ['address' => 'D0.1', 'value' => 1]]], $bridge->commands);
    }

    public function testWriteWithAssociativeValues(): void
    {
        $bridge = $this->fakeBridge();
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);

        $connector->write(['D0.1', 'D0.2'], ['D0.1' => 5, 'D0.2' => 9]);

        $this->assertSame([
            ['write', ['address' => 'D0.1', 'value' => 5]],
            ['write', ['address' => 'D0.2', 'value' => 9]],
        ], $bridge->commands);
    }

    public function testCommandDelegatesRaw(): void
    {
        $bridge = $this->fakeBridge();
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);

        $response = $connector->command('status', 'raw');

        $this->assertSame('ok', $response);
        $this->assertSame([['status', 'raw']], $bridge->commands);
    }

    public function testHealthStates(): void
    {
        $bridge = $this->fakeBridge();
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);

        $this->assertSame(ConnectionState::CLOSED, $connector->getHealth()->state);

        $connector->connect();
        $this->assertSame(ConnectionState::HEALTHY, $connector->getHealth()->state);
    }
}
