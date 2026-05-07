# ESP32-S3 Remote Wake-on-LAN and Web Console

## Project Overview
This project uses a public MQTT server as a message broker to allow an ESP32-S3 development board, placed within the same local network as the target computer, to send WOL (Wake-on-LAN) magic packets for wide-area network computer awakening.

## Core Features
* WAN Control: Overcomes LAN restrictions, allowing remote boot commands from a public web page.
* Real-time Monitoring: Displays real-time online/offline status of the ESP32-S3 using MQTT LWT.
* Interactive Web UI: Features character-level particle repulsion physics effects via JavaScript.
* Data Recording: Uses a SQLite database and PHP API to track and display total boot counts.

## Requirements
### Hardware
* ESP32-S3 Development Board
* Computer motherboard and NIC supporting WOL
* 5V/1A Stable Power Supply

### Software & Server
* Cloud Server (Ports 80, 443, 1883, 8083, 18083 open)
* Control Panel (Nginx & PHP 8.0+)
* EMQX (via Docker)
* Arduino IDE (for firmware compilation and flashing)

## Deployment Steps

### 1. Server Configuration (MQTT Server)
Deploy EMQX using Docker on your server:
docker run -d --name emqx -p 1883:1883 -p 8083:8083 -p 8084:8084 -p 8883:8883 -p 18083:18083 emqx/emqx:latest

### 2. Web Deployment (Web Server)
1. Create a site and configure SSL (Domain: nb6.icu).
2. Upload index.html and api.php to the root directory.
3. Add the WebSocket reverse proxy in Nginx:
location /mqtt {
    proxy_pass http://127.0.0.1:8083/mqtt;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400;
}
4. Ensure write permissions for the root directory.

### 3. Hardware Flashing (ESP32-S3)
1. Install ESP32 and PubSubClient libraries in Arduino IDE.
2. Configure Wi-Fi SSID, password, server IP, and target MAC address in the code.
3. Upload to ESP32-S3.

### 4. Computer Configuration
1. Enable "Power On By PCIE" or "WOL" in BIOS.
2. Enable "Wake on Magic Packet" in NIC advanced properties.
3. Check "Allow this device to wake the computer" in Power Management.

## Usage
1. Ensure the computer is off but powered, and ESP32-S3 is on the same LAN.
2. Access the web URL and wait for "Device Ready".
3. Click the power button to send the command.

## Notes
* Insufficient power may cause the board to reboot; use a high-quality power source.
* WSS protocol is mandatory for MQTT communication under HTTPS environments.
