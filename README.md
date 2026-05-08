## Demo
<img width="400" height="225" alt="2026-05-08 11-20-57" src="https://github.com/user-attachments/assets/33c5fff8-a9c8-4ca9-be0a-67e4ea5b9ce1" />

## Full video
https://www.bilibili.com/video/BV163duBwEz7

## English
# ESP32-S3 Remote Wake-on-LAN and Secure Web Console

## Project Overview
This project uses an ESP32-S3 development board to send WOL (Wake-on-LAN) magic packets, enabling computer awakening across a Wide Area Network (WAN). The front-end console is built with pure PHP and native JavaScript, requiring no separate database service. It utilizes SQLite for state logging and features highly secure remote access via low-level IP interception.

## Core Features
* **WAN Wake-on-LAN (WOL)**: Overcomes LAN restrictions, allowing remote boot commands to be sent from a public web interface.
* **High-Security Architecture**:
    * **Low-Level IP Whitelisting**: Based on PHP's `$_SERVER['REMOTE_ADDR']`, unauthorized IP accesses are physically isolated, displaying only "No permission" and pure front-end animations. The real source code, MQTT configurations, and API endpoints are **completely hidden**.
    * **Physical File Isolation**: The database file is stored in a hidden directory (`.db_data`) by default, coupled with Nginx rules to prevent direct downloading.
* **Real-time Status Monitoring**: Based on the MQTT Last Will and Testament (LWT) mechanism, the authorized web interface displays the device's online/offline status in real-time.
* **Interactive Animations**: The PC interface features a character-level particle repulsion force field effect powered by a JavaScript physics engine.
* **Data Persistence**: Uses a lightweight SQLite database to record and display cumulative boot counts, with a built-in session-based anti-spam mechanism.

## Requirements
### Hardware
* ESP32-S3 Development Board
* Computer motherboard and network adapter supporting and enabling WOL
* 5V/1A stable power supply

### Software & Server
* Cloud Server (Ports 80, 443, 1883, 8083 must be open)
* Runtime Environment: Nginx + PHP 8.0 or above (aaPanel recommended for management)
* MQTT Server: EMQX (Deployable via Docker)
* IDE: Arduino IDE (for compiling and flashing ESP32 firmware)

## Deployment Steps

### 1. Deploy MQTT Server (EMQX)
Run the following Docker command on your cloud server to deploy EMQX:
```bash
docker run -d --name emqx -p 1883:1883 -p 8083:8083 -p 8084:8084 -p 8883:8883 -p 18083:18083 emqx/emqx:latest
```
*(It is strongly recommended to configure usernames/passwords and ACL rules in the EMQX dashboard to enhance security.)*

### 2. Web Server Deployment
1. Create a site in your server panel (e.g., aaPanel) and configure an SSL certificate (HTTPS is mandatory).
2. Upload the `index.php` file to the website's root directory.
3. **Security Configuration (Mandatory)**: Modify `$allowed_ip` at the top of `index.php` to your exclusive public IP address.
4. **Nginx Configuration**: Add the WebSocket reverse proxy rules and database protection rules to your site's Nginx configuration:
```nginx
location /mqtt {
    proxy_pass http://127.0.0.1:8083/mqtt;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400;
}

location ~ \.db$ {
    deny all;
}
```

### 3. Hardware Flashing (ESP32-S3)
1. Install the ESP32 board support package and the `PubSubClient` library in Arduino IDE.
2. Open `main.ino` and modify the following configurations based on the comments:
   * `ssid` / `password`: Local Wi-Fi credentials
   * `mqtt_server`: MQTT Server IP
   * `mqtt_user` / `mqtt_pass`: (If EMQX authentication is configured)
   * `macAddress`: Target computer's physical MAC address
3. Compile and upload the code to the ESP32-S3.

### 4. Computer Configuration (WOL)
1. Enter the computer BIOS and enable "PCIE Wake" or "Wake on LAN" related options.
2. Open Windows Device Manager and go to the Network Adapter properties.
3. Under the "Advanced" tab, enable "Wake on Magic Packet".
4. Under the "Power Management" tab, check "Allow this device to wake the computer".

## Usage Instructions
1. Ensure the target computer is turned off but connected to power.
2. Power the ESP32-S3 and ensure it is connected to the same local network as the target machine.
3. Ensure your current network IP matches the whitelisted IP set in `index.php`.
4. Access the webpage. Once the status shows "Device Ready", click the center button to send the boot command.

## Notes & Disclaimers
* **IP Changes**: Due to the extremely strict low-level IP whitelisting mechanism, if your client IP changes (e.g., switching networks), you will not be able to access the real console and must update the IP configuration on the server.
* This project is for educational and personal use only. The author assumes no responsibility for any security issues arising from network configurations or code modifications.


## Chinese
# ESP32-S3 远程开机与网页控制台

## 项目简介
本项目利用 ESP32-S3 开发板发送 WOL（Wake-on-LAN）魔术包，实现跨广域网的计算机唤醒功能。前端控制台基于纯 PHP 与原生 JavaScript 构建，无需数据库服务，通过 SQLite 记录状态，并通过底层 IP 拦截提供高安全性的远程访问。

## 核心功能
* **广域网唤醒 (WOL)**：打破局域网限制，通过公网网页远程发送开机指令。
* **高安全性架构**：
    * **底层 IP 白名单**：基于 PHP `$_SERVER['REMOTE_ADDR']` 的底层物理隔离，未授权 IP 访问仅显示“No permission”及纯前端动效，**彻底隐藏**真实源码、MQTT 配置与 API 接口。
    * **物理文件隔离**：数据库文件默认存储在隐藏目录 `.db_data` 中，配合 Nginx 规则防止直接下载。
* **实时状态监控**：基于 MQTT 遗嘱机制（LWT），授权网页端实时显示设备的在线/离线状态。
* **交互式动效**：PC 端页面包含基于 JavaScript 物理计算引擎的单字粒子排斥力场效果。
* **数据持久化**：使用轻量级 SQLite 数据库记录并展示累计开机次数，内置 Session 级防刷机制。

## 环境要求
### 硬件
* ESP32-S3 开发板
* 支持并已开启 WOL（网络唤醒）功能的计算机主板及网卡
* 5V/1A 稳定电源

### 软件与服务器
* 云服务器（需开放 80, 443, 1883, 8083 端口）
* 运行环境：Nginx + PHP 8.0 或以上版本（推荐使用宝塔面板管理）
* MQTT 服务器：EMQX（可通过 Docker 快速部署）
* 编译工具：Arduino IDE（用于编译和烧录 ESP32 固件）

## 部署步骤

### 1. 部署 MQTT 服务器 (EMQX)
在云服务器中运行以下 Docker 命令部署 EMQX：
```bash
docker run -d --name emqx -p 1883:1883 -p 8083:8083 -p 8084:8084 -p 8883:8883 -p 18083:18083 emqx/emqx:latest
```
*(强烈建议在 EMQX 面板中配置账号密码及 ACL 规则以提升安全性。)*

### 2. 网页端部署 (Web Server)
1. 在服务器面板（如宝塔）中创建站点并申请配置 SSL 证书（必须开启 HTTPS）。
2. 将 `index.php` 文件上传至网站根目录。
3. **安全配置 (必须)**：在 `index.php` 顶部修改 `$allowed_ip` 为你自己的专属公网 IP。
4. **Nginx 配置**：在站点的 Nginx 配置文件中加入 WebSocket 反向代理规则及数据库保护规则：
```nginx
location /mqtt {
    proxy_pass http://127.0.0.1:8083/mqtt;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400;
}

location ~ \.db$ {
    deny all;
}
```

### 3. 硬件端烧录 (ESP32-S3)
1. 在 Arduino IDE 中安装 ESP32 开发板支持包及 `PubSubClient` 库。
2. 打开 `main.ino`，根据注释修改以下配置：
   * `ssid` / `password`: 局域网 Wi-Fi 信息
   * `mqtt_server`: MQTT 服务器 IP
   * `mqtt_user` / `mqtt_pass`: (若 EMQX 配置了鉴权则填入)
   * `macAddress`: 目标计算机的网卡物理地址 (MAC)
3. 编译并上传代码至 ESP32-S3。

### 4. 计算机端配置 (WOL)
1. 进入计算机 BIOS，开启“PCIE 唤醒”或“Wake on LAN”相关选项。
2. 进入 Windows 设备管理器，打开网络适配器属性。
3. 在“高级”选项卡中，开启“唤醒魔术包”。
4. 在“电源管理”选项卡中，勾选“允许此设备唤醒计算机”。

## 使用说明
1. 确保目标计算机处于关机但通电状态。
2. 将 ESP32-S3 接入电源，并确保其连接至目标机所在的同一个局域网。
3. 确保你当前的网络 IP 与 `index.php` 中设置的白名单 IP 一致。
4. 访问网页，待状态显示“设备已就绪”后，点击中间的按钮下发开机指令。

## 声明与注意事项
* **IP 变更问题**：由于启用了极严格的底层 IP 白名单机制，若你的客户端 IP 发生变化（如切换网络），你将无法访问真实控制台，需重新修改服务器上的 IP 配置。
* 本项目仅供学习与个人使用，对于因网络配置或代码修改引发的安全问题，作者不承担责任。
