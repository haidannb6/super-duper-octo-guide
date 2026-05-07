#include <WiFi.h>
#include <PubSubClient.h>
#include <WiFiUdp.h>

// Replace with your Wi-Fi credentials / 替换为你的 Wi-Fi 信息
const char* ssid = "YOUR_SSID";
const char* password = "YOUR_PASSWORD";

// Replace with your MQTT broker IP / 替换为你的 MQTT 服务器 IP
const char* mqtt_server = "YOUR_MQTT_SERVER_IP";

// Replace with the MAC address of the PC to wake / 替换为待唤醒电脑的 MAC 地址
byte macAddress[] = {0xAA, 0xBB, 0xCC, 0xDD, 0xEE, 0xFF};

WiFiClient espClient;
PubSubClient client(espClient);
WiFiUDP udp;

void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}

void wakeMyPC() {
  byte magicPacket[102];
  for (int i = 0; i < 6; i++) {
    magicPacket[i] = 0xFF;
  }
  for (int i = 0; i < 16; i++) {
    for (int j = 0; j < 6; j++) {
      magicPacket[6 + i * 6 + j] = macAddress[j];
    }
  }
  udp.beginPacket("255.255.255.255", 9);
  udp.write(magicPacket, sizeof(magicPacket));
  udp.endPacket();
  Serial.println("Magic Packet Sent!");
}

void callback(char* topic, byte* payload, unsigned int length) {
  Serial.print("Message arrived [");
  Serial.print(topic);
  Serial.print("] ");
  String msg = "";
  for (int i = 0; i < length; i++) {
    msg += (char)payload[i];
  }
  Serial.println(msg);
  if (msg == "WAKE") {
    wakeMyPC();
  }
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Attempting MQTT connection...");
    String clientId = "ESP32S3Client-";
    clientId += String(random(0xffff), HEX);
    if (client.connect(clientId.c_str(), "esp32/status", 0, true, "offline")) {
      Serial.println("connected");
      client.publish("esp32/status", "online", true);
      client.subscribe("esp32/pc_control");
    } else {
      Serial.print("failed, rc=");
      Serial.print(client.state());
      Serial.println(" try again in 5 seconds");
      delay(5000);
    }
  }
}

void setup() {
  Serial.begin(115200);
  setup_wifi();
  client.setServer(mqtt_server, 1883);
  client.setCallback(callback);
}

void loop() {
  if (!client.connected()) {
    reconnect();
  }
  client.loop();
}