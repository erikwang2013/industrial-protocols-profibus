<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\Profibus\Tests\Simulation;

use Erikwang2013\IndustrialProtocols\Profibus\ProfibusProtocol;
use Erikwang2013\IndustrialProtocols\Bridge\BridgeConnector;
use Erikwang2013\IndustrialProtocols\Bridge\TcpGatewayBridge;
use Erikwang2013\IndustrialProtocols\Connection\ConnectionState;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end connector lifecycle through a real TcpGatewayBridge against a
 * fake gateway implementing the bridge framing
 * (cmd-len v + cmd + payload-len V + payload).
 */
class GatewayBridgeTest extends TestCase
{
    public function testLifecycleThroughTcpGateway(): void
    {
        $proc = proc_open([PHP_BINARY, '-r', <<<'STUB'
            $server = stream_socket_server('tcp://127.0.0.1:15202');
            echo "READY\n";
            flush();
            $client = @stream_socket_accept($server, 5);
            while ($client && !feof($client)) {

                $request = fread($client, 4096);
                if ($request === false || $request === '') break;
$cmdLen = unpack('v', substr($request, 0, 2))[1];
                    $cmd = substr($request, 2, $cmdLen);
                    $payloadLen = unpack('V', substr($request, 2 + $cmdLen, 4))[1];
                    $payload = substr($request, 6 + $cmdLen, $payloadLen);
fwrite($client, "ACK:$cmd:$payload");
usleep(100000); // hold open briefly; immediate close races the parent read
}
if ($client) fclose($client);
fclose($server);
STUB, ], [1 => ['pipe', 'w']], $pipes);

        fgets($pipes[1]);

        $bridge = new TcpGatewayBridge('127.0.0.1', 15202, 5.0);
        $connector = (new profibusProtocol())->createConnector(['bridge' => $bridge]);
        $this->assertInstanceOf(BridgeConnector::class, $connector);

        $connector->connect();
        $this->assertTrue($connector->isConnected());

        $result = $connector->read('D0.1');
        $this->assertSame('ACK:read:{"address":"D0.1"}', $result['D0.1']);

        $result = $connector->write('D0.1', [1]);
        $this->assertSame('ACK:write:{"address":"D0.1","value":1}', $result['D0.1']);

        $this->assertSame(ConnectionState::HEALTHY, $connector->getHealth()->state);

        $connector->disconnect();
        $this->assertFalse($connector->isConnected());

        proc_close($proc);
    }

    public function testConnectRefusedThrows(): void
    {
        // Bind an ephemeral port, release it, then connect — ECONNREFUSED
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $name = stream_socket_get_name($server, false);
        $port = (int) substr($name, strrpos($name, ':') + 1);
        fclose($server);

        $bridge = new TcpGatewayBridge('127.0.0.1', $port, 2.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gateway bridge connect failed');
        $bridge->open();
    }
}
