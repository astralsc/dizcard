# Dizcard

A Dizcard private server written in PHP     

This server is not ready for production because it is unfinished.

## Requirements

- Node.js
- Chocolatey
- mkcert

## Supported years

```
- 2015 ❌
- 2016 ❌
- 2017 🟧 (only some of the newer 2017 apks work)
- 2018 ✅
- 2019 ✅ 
- 2020 🟧 (only the earliest 2020 apks work)
- 2021 ❌
- 2022 ❌
- 2023 ❌
- 2024 ❌
- 2025 ❌
- 2026 ❌
```

## Setup     

1. Run **UwAmp Wamp Server** or **XAMPP**.
2. Put all the repository files in the **www** folder.
3. Run `npm install` inside the **wss-server** folder.
4. Run `choco install mkcert` inside the **wss-server** folder with administrator permissions.
5. Run `mkcert -install` inside the **wss-server** folder.
6. Run `mkcert -cert-file cert.pem -key-file key.pem localhost 127.0.0.1 ::1` or `mkcert -cert-file cert.pem -key-file key.pem IP_HERE` inside the **wss-server** folder.
7. Run `node .` inside the **wss-server** folder.
8. Btw you need to update gateway.php if your not using localhost.
