# PROFIBUS DP/PA/FMS 协议包 — 需 Siemens CP 5611/Anybus 网关桥接

> [English](README.en.md)

PROFIBUS (Process Field Bus) DP/PA/FMS 现场总线。需要专用硬件接口卡（Siemens CP 5611）或 Anybus 网关，通过内核 Bridge 层桥接通信。

## 安装

```bash
composer require erikwang2013/industrial-protocols-profibus
```

## 架构

ProfibusProtocol → BridgeConnector → ExternalProcessBridge/TcpGatewayBridge。通过内核 Bridge 层适配厂商 SDK 或网关硬件。

## 功能

PROFIBUS DP/PA/FMS 桥接、BridgeConnector 连接管理

## 使用说明

```php
$bridge = new TcpGatewayBridge('192.168.1.200', 502);
$conn = $kernel->getConnectionManager()->connect('profibus-device', [
    'protocol' => 'profibus', 'bridge' => $bridge,
]);
```

## 所需硬件

Siemens CP 5611/CP 5613/CP 5614 接口卡、Anybus Communicator、Hilscher cifX、Softing PROFIusb

## 兼容框架

Laravel / Webman / Hyperf / ThinkPHP / Yii2 / Plain PHP

## 系统要求

- PHP >= 8.1
- PROFIBUS 接口硬件（CP 5611/Anybus/Hilscher）
- erikwang2013/industrial-protocols-kernel

## License

MIT — Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
