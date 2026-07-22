# PROFIBUS DP/PA/FMS 协议包 — 需 Anybus/Siemens CP 网关桥接

> [中文](README.md)

PROFIBUS DP/PA/FMS 协议包 — 需 Anybus/Siemens CP 网关桥接。Requires dedicated hardware, bridged to vendor SDK or gateway via kernel Bridge layer.

## Installation

```bash
composer require erikwang2013/industrial-protocols-kernel erikwang2013/industrial-protocols-profibus
```

> Depends on [erikwang2013/industrial-protocols-kernel](https://github.com/erikwang2013/industrial-protocols-kernel) for hardware bridge layer, connection management, and protocol registry.

## Design

This protocol requires dedicated hardware chips or interface cards — PHP cannot implement the protocol stack directly at the application layer. This package bridges to hardware SDK or gateway devices through the kernel's Bridge layer. BridgeConnector implements ConnectorInterface, internally delegating to BridgeInterface (ExternalProcessBridge launches local C/C++ SDK subprocess via proc_open, TcpGatewayBridge connects to remote gateway hardware via TCP/UDP Socket).

## Architecture

Bridge mode: BridgeConnector (implements ConnectorInterface) → BridgeInterface (open/close/execute/isReady) → ExternalProcessBridge (local SDK subprocess via proc_open stdin/stdout) or TcpGatewayBridge (remote gateway via TCP/UDP Socket). Applications use ConnectionManager for unified connect/read/write/getHealth calls.

## Supported Frameworks

Compatible with 6 PHP runtimes via kernel framework adapters: Laravel (ServiceProvider+Facade+artisan), Webman (config/plugin auto-discovery+ProtocolProcess), Hyperf (ConfigProvider+DI+KernelFactory), ThinkPHP (services.php+IndustrialProtocolsService), Yii2 (Bootstrap+component), Plain PHP (direct Kernel instantiation)

## Usage

```php
use Erikwang2013\IndustrialProtocols\Kernel;
use IndustrialProtocols\Bridge\TcpGatewayBridge;

$kernel = new Kernel(['config_path' => __DIR__ . '/config.php']);
$kernel->boot();

// Via TCP gateway
$bridge = new TcpGatewayBridge('192.168.1.200', 502);
$conn = $kernel->getConnectionManager()->connect('device-id', [
    'protocol' => 'profibus', 'bridge' => $bridge,
]);
$result = $conn->read('address');

// Via vendor factory
$bridge = $kernel->getVendorBridgeFactory()->create('siemens', 'S7-1500', 'V3.x');
```

## Adapter Vendors

Siemens (CP 5611, S7-1200/1500), Hilscher (cifX DP, netX), HMS/Anybus (PROFIBUS Master/Slave), Phoenix Contact (AXL F BK DP), Softing (PROFIusb)

## Requirements

- PHP >= 8.1
- Composer
- erikwang2013/industrial-protocols-kernel
- PROFIBUS interface card (Siemens CP 5611) or gateway (Anybus PROFIBUS Master, Hilscher cifX DP)

## Related Links

- [Industrial Protocols Main Project](https://github.com/erikwang2013/industrial-protocols)
- [Kernel](https://github.com/erikwang2013/industrial-protocols-kernel)
- [All 42 Protocol Packages](https://github.com/erikwang2013/industrial-protocols#supported-protocols)

## License

MIT — Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
