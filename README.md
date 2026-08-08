# Dizcard

A Dizcard private server written in PHP     

This server is not ready for production because it is unfinished.

## Requirements

- Node.js
- OpenSSL (Win64 OpenSSL Light)

## Supported years

```
- 2015 ❌
- 2016 ❌
- 2017 🟧 (only some of the newer 2017 akpks work)
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
4. Run `openssl req -x509 -newkey rsa:2048 -nodes -keyout key.pem -out cert.pem -days 365` inside the **wss-server** folder.
5. Run `node .` inside the **wss-server** folder.
