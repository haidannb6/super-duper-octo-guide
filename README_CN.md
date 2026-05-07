# ESP32-S3 远程开机与网页控制台

## 项目简介
本项目通过公网 MQTT 服务器作为消息中转，利用放置在目标计算机同一局域网内的 ESP32-S3 开发板发送 WOL（Wake-on-LAN）魔术包，实现跨广域网的计算机唤醒功能。

## 核心功能
* 广域网控制：打破局域网限制，通过公网网页随时发送开机指令。
* 状态实时监控：基于 MQTT 遗嘱机制（LWT），网页端实时显示 ESP32-S3 设备的在线或离线状态。
* 网页交互动效：包含基于 JavaScript 物理计算的单字粒子排斥力场效果。
* 数据记录：通过 SQLite 数据库与 PHP 接口，记录并显示累计开机次数。

## 环境要求
### 硬件
* ESP32-S3 开发板
* 支持 WOL 功能的计算机主板及网卡
* 5V/1A 稳定电源

### 软件与服务器
* 云服务器（需放行 80, 443, 1883, 8083, 18083 端口）
* 宝塔面板（含 Nginx 与 PHP 8.0+）
* EMQX（通过 Docker 部署）
* Arduino IDE（用于编译烧录 ESP32 固件）

## 部署步骤

### 1. 服务端配置 (MQTT 服务器)
在云服务器中运行以下 Docker 命令部署 EMQX：
docker run -d --name emqx -p 1883:1883 -p 8083:8083 -p 8084:8084 -p 8883:8883 -p 18083:18083 emqx/emqx:latest

### 2. 网页端部署 (Web Server)
1. 在宝塔中创建站点并配置 SSL 证书（域名：nb6.icu）。
2. 上传 index.html 与 api.php 至网站根目录。
3. 在 Nginx 配置文件中添加 WebSocket 反向代理：
location /mqtt {
    proxy_pass http://127.0.0.1:8083/mqtt;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400;
}
4. 确保根目录有写入权限。

### 3. 硬件端烧录 (ESP32-S3)
1. 在 Arduino IDE 中安装 ESP32 库与 PubSubClient 库。
2. 在代码中配置 Wi-Fi SSID、密码、服务器 IP 及目标机网卡 MAC 地址。
3. 上传代码至 ESP32-S3。

### 4. 计算机端配置
1. BIOS 中开启 Power On By PCIE 或 WOL。
2. 网卡属性的高级选项中开启“唤醒魔术包”。
3. 电源管理中勾选“允许此设备唤醒计算机”。

## 使用说明
1. 确保电脑已关机通电，ESP32-S3 在同局域网内运行。
2. 访问网页地址，待状态显示“设备已就绪”。
3. 点击开机按钮下发指令。

## 注意事项
* 供电不足可能导致开发板频繁重启，请使用高质量电源。
* HTTPS 环境下必须使用 WSS 协议进行 MQTT 通信。
