import fs from "fs";
import https from "https";
import { WebSocketServer } from "ws";

const server = https.createServer({
  key: fs.readFileSync("./key.pem"),
  cert: fs.readFileSync("./cert.pem")
});

const wss = new WebSocketServer({ server });

wss.on("connection", (ws) => {
  console.log("Client connected!");

  ws.send("Secure connection established!");

  ws.on("message", (msg) => {
    console.log("Received:", msg.toString());
  });
});

server.listen(8081, () => {
  console.log("WSS running on https://localhost:8081");
});