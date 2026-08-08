import fs from 'fs'
import https from 'https'
import http from 'http'
import mysql from 'mysql2/promise'
import { WebSocketServer } from 'ws'

const db = mysql.createPool({
    host: '127.0.0.1',
    user: 'root',
    password: 'root',
    database: 'dizcard',
    supportBigNumbers: true,
    bigNumberStrings: true
})

try {
    const c = await db.getConnection()
    c.release()
} catch (e) {
    console.error(e)
    process.exit(1)
}

const server = https.createServer({
    key: fs.readFileSync('./key.pem'),
    cert: fs.readFileSync('./cert.pem')
})

fs.mkdirSync("../uploads/attachments", { recursive: true });

const wss = new WebSocketServer({ server, perMessageDeflate: false })
const heartbeat = 45000
const statuses = ['online', 'idle', 'dnd', 'invisible']
const uploadDir = '../uploads/attachments'
const uploadUrl = 'http://discordapp.com/uploads/attachments/'

const send = (ws, d) => {
    if (ws.readyState === 1) ws.send(JSON.stringify(d))
}

function settings(v) {
    try {
        return typeof v === 'string' ? JSON.parse(v) || {} : v || {}
    } catch {
        return {}
    }
}

function user(a) {
    return {
        id: String(a.id),
        username: a.username,
        discriminator: String(a.discriminator),
        avatar: null,
        email: a.email,
        verified: true,
        bot: false,
        premium: true,
        claimed: true,
        mfa_enabled: false,
        premium_type: 2,
        nsfw_allowed: true,
        settings: settings(a.settings)
    }
}

async function getUsers(ids) {
    const unique = [...new Set(ids.map(String))]
    if (!unique.length) return []

    const [rows] = await db.query(
        `SELECT id, username, discriminator, email, settings
         FROM users WHERE id IN (${unique.map(() => '?').join(',')})`,
        unique
    )

    return rows.map(user)
}

async function friends(s) {
    return getUsers(Array.isArray(s.friends) ? s.friends : [])
}

async function pendingIncoming(s) {
    return getUsers(Array.isArray(s.pending_incoming) ? s.pending_incoming : [])
}

async function pendingOutgoing(s) {
    return getUsers(Array.isArray(s.pending_outgoing) ? s.pending_outgoing : [])
}

async function blocked(s) {
    return getUsers(Array.isArray(s.blocked) ? s.blocked : [])
}

let increment = 0

function snowflake() {
    const timestamp = BigInt(Date.now() - 1420070400000)
    const sequence = BigInt(increment++ & 4095)
    return String((timestamp << 22n) | sequence)
}

function getAttachments(id) {
    try {
        return fs.readdirSync(uploadDir)
            .filter(file => file.startsWith(id + '-'))
            .map((filename, i) => {
                const path = `${uploadDir}/${filename}`
                const stat = fs.statSync(path)
                let type = 'application/octet-stream'

                try {
                    const ext = filename.split('.').pop().toLowerCase()
                    const types = {
                        png: 'image/png',
                        jpg: 'image/jpeg',
                        jpeg: 'image/jpeg',
                        gif: 'image/gif',
                        webp: 'image/webp',
                        mp4: 'video/mp4',
                        mp3: 'audio/mpeg',
                        txt: 'text/plain',
                        pdf: 'application/pdf'
                    }
                    type = types[ext] || type
                } catch {}

                const a = {
                    id: `${id}-${i + 1}`,
                    filename,
                    size: stat.size,
                    url: uploadUrl + filename,
                    proxy_url: uploadUrl + filename,
                    content_type: type
                }

                try {
                    const info = require('image-size')(path)
                    a.width = info.width
                    a.height = info.height
                    a.original_content_type = type
                } catch {}

                return a
            })
    } catch (e) {
        console.error('Attachments:', e)
        return []
    }
}

async function getDMChannel(userA, userB) {
    const a = Number(userA)
    const b = Number(userB)
    const user_id_1 = Math.min(a, b)
    const user_id_2 = Math.max(a, b)

    const [rows] = await db.execute(
        `SELECT id FROM channels
         WHERE user_id_1=? AND user_id_2=? LIMIT 1`,
        [user_id_1, user_id_2]
    )

    if (rows.length) return String(rows[0].id)

    const id = snowflake()

    try {
        await db.execute(
            `INSERT INTO channels
             (id, type, user_id_1, user_id_2, created_at)
             VALUES (?, 1, ?, ?, NOW())`,
            [id, user_id_1, user_id_2]
        )
        return id
    } catch (e) {
        if (e.code === 'ER_DUP_ENTRY') {
            const [existing] = await db.execute(
                `SELECT id FROM channels
                 WHERE user_id_1=? AND user_id_2=? LIMIT 1`,
                [user_id_1, user_id_2]
            )
            if (existing.length) return String(existing[0].id)
        }
        throw e
    }
}

async function getGroupChannels(userId) {
    const [channels] = await db.execute(
        `SELECT id, type, owner_id, name, icon
         FROM group_channels
         WHERE id IN (
             SELECT channel_id FROM group_channel_recipients
             WHERE user_id=?
         )
         ORDER BY created_at`,
        [userId]
    )

    const result = []

    for (const channel of channels) {
        const [rows] = await db.execute(
            `SELECT u.id, u.username, u.discriminator, u.email, u.settings
             FROM group_channel_recipients r
             JOIN users u ON u.id=r.user_id
             WHERE r.channel_id=? AND r.user_id!=?
             ORDER BY r.user_id`,
            [channel.id, userId]
        )

        result.push({
            id: String(channel.id),
            type: 3,
            name: channel.name,
            icon: channel.icon,
            owner_id: String(channel.owner_id),
            recipients: rows.map(user),
            last_message_id: null
        })
    }

    return result
}

const typingServer = http.createServer((req, res) => {
    if (req.method !== 'POST' || req.url !== '/typing') {
        res.writeHead(404)
        return res.end()
    }

    let body = ''

    req.on('data', x => body += x)

    req.on('end', async () => {
        try {
            const d = JSON.parse(body)
            const channelId = String(d.channel_id)
            const userId = String(d.user_id)

            const [members] = await db.execute(
                `SELECT user_id FROM group_channel_recipients WHERE channel_id=?`,
                [channelId]
            )

            const ids = members.map(x => String(x.user_id))

            if (!ids.includes(userId)) {
                const [channel] = await db.execute(
                    `SELECT user_id_1, user_id_2
                     FROM channels WHERE id=? LIMIT 1`,
                    [channelId]
                )

                if (channel.length) {
                    ids.push(
                        String(channel[0].user_id_1),
                        String(channel[0].user_id_2)
                    )
                }
            }

            for (const client of wss.clients) {
                if (
                    client.readyState === 1 &&
                    client.user &&
                    ids.includes(String(client.user.id)) &&
                    String(client.user.id) !== userId
                ) {
                    send(client, {
                        op: 0,
                        s: ++client.seq,
                        t: 'TYPING_START',
                        d: {
                            channel_id: channelId,
                            user_id: userId,
                            timestamp: Math.floor(Date.now() / 1000)
                        }
                    })
                }
            }

            res.writeHead(204)
            res.end()
        } catch (e) {
            console.error('Typing:', e)
            res.writeHead(500)
            res.end()
        }
    })
})

typingServer.listen(8082, '127.0.0.1')

let lastMessageId = null

setInterval(async () => {
    try {
        const [rows] = await db.execute(`
            SELECT m.*, u.username, u.discriminator
            FROM messages m
            JOIN users u ON u.id=m.author_id
            ORDER BY m.created_at DESC
            LIMIT 1
        `)

        if (!rows.length) return

        const m = rows[0]
        const id = String(m.id)

        if (id === lastMessageId) return
        lastMessageId = id

        const message = {
            type: 0,
            guild_id: null,
            id,
            content: m.content,
            channel_id: String(m.channel_id),
            author: {
                username: m.username,
                discriminator: String(m.discriminator),
                id: String(m.author_id),
                avatar: null,
                bot: false,
                flags: 0,
                premium: true
            },
            attachments: getAttachments(id),
            embeds: [],
            mentions: [],
            mention_everyone: false,
            mention_roles: [],
            nonce: m.nonce,
            edited_timestamp: null,
            timestamp: new Date(m.created_at).toISOString(),
            reactions: [],
            tts: false,
            pinned: false
        }

        for (const client of wss.clients) {
            if (client.readyState === 1 && client.user) {
                send(client, {
                    op: 0,
                    s: ++client.seq,
                    t: 'MESSAGE_CREATE',
                    d: message
                })
            }
        }
    } catch (e) {
        console.error('Message poll:', e)
    }
}, 500)

const messageServer = http.createServer((req, res) => {
    if (req.method !== 'POST' || req.url !== '/message') {
        res.writeHead(404)
        return res.end()
    }

    let body = ''

    req.on('data', x => body += x)

    req.on('end', async () => {
        try {
            const d = JSON.parse(body)
            const channelId = String(d.channel_id)
            const message = d.message
            const ids = []

            const [dm] = await db.execute(
                `SELECT user_id_1,user_id_2
                 FROM channels WHERE id=? LIMIT 1`,
                [channelId]
            )

            if (dm.length) {
                ids.push(
                    String(dm[0].user_id_1),
                    String(dm[0].user_id_2)
                )
            } else {
                const [rows] = await db.execute(
                    `SELECT user_id FROM group_channel_recipients WHERE channel_id=?`,
                    [channelId]
                )
                ids.push(...rows.map(x => String(x.user_id)))
            }

            for (const client of wss.clients) {
                if (
                    client.readyState === 1 &&
                    client.user &&
                    ids.includes(String(client.user.id))
                ) {
                    send(client, {
                        op: 0,
                        s: ++client.seq,
                        t: 'MESSAGE_CREATE',
                        d: message
                    })
                }
            }

            res.writeHead(204)
            res.end()
        } catch (e) {
            console.error('Message:', e)
            res.writeHead(500)
            res.end()
        }
    })
})

messageServer.listen(8083, '127.0.0.1')

wss.on('connection', ws => {
    ws.seq = 0
    ws.user = null
    ws.presence = null

    send(ws, {
        op: 10,
        s: null,
        d: { heartbeat_interval: heartbeat }
    })

    ws.on('message', async raw => {
        let m

        try {
            m = JSON.parse(raw)
        } catch {
            return ws.close(4000, 'Invalid payload')
        }

        const { op, d } = m

        if (op === 1)
            return send(ws, { op: 11, d: null })

        if (op === 2) {
            if (!d?.token)
                return ws.close(4004, 'Authentication required')

            const [rows] = await db.execute(
                `SELECT id, username, discriminator, email, token, settings
                 FROM users WHERE token=? LIMIT 1`,
                [d.token]
            )

            if (!rows.length)
                return ws.close(4004, 'Invalid token')

            ws.user = rows[0]
            console.log(`Connected: ${ws.user.username}`)

            const s = settings(ws.user.settings)
            const savedStatus = s.last_status || s.status
            const status = statuses.includes(savedStatus) ? savedStatus : 'online'

            s.status = status

            ws.presence = {
                since: null,
                status,
                afk: false,
                game: null
            }

            await db.execute(
                'UPDATE users SET settings=? WHERE id=?',
                [JSON.stringify(s), ws.user.id]
            )

            const me = user(ws.user)
            const fs = await friends(s)
            const incoming = await pendingIncoming(s)
            const outgoing = await pendingOutgoing(s)
            const blockedUsers = await blocked(s)

            const relationships = [
                ...fs.map(f => ({ id: f.id, type: 1, user: f })),
                ...blockedUsers.map(f => ({ id: f.id, type: 2, user: f })),
                ...incoming.map(f => ({ id: f.id, type: 3, user: f })),
                ...outgoing.map(f => ({ id: f.id, type: 4, user: f }))
            ]

            const channels = []

            for (const f of fs) {
                const channelId = await getDMChannel(ws.user.id, f.id)

                channels.push({
                    id: channelId,
                    type: 1,
                    recipients: [f],
                    last_message_id: null
                })
            }

            const groupChannels = await getGroupChannels(ws.user.id)
            channels.push(...groupChannels)

            const presences = fs.map(f => ({
                user: { id: f.id },
                status: f.settings?.status || 'invisible',
                activities: []
            }))

            const uniqueUsers = [
                ...new Map([
                    ...fs,
                    ...blockedUsers,
                    ...incoming,
                    ...outgoing,
                    ...groupChannels.flatMap(c => c.recipients)
                ].map(f => [String(f.id), f])).values()
            ]

            send(ws, {
                op: 0,
                s: ++ws.seq,
                t: 'READY',
                d: {
                    v: 6,
                    guilds: [],
                    presences,
                    private_channels: channels,
                    relationships,
                    read_state: [],
                    tutorial: {
                        indicators_suppressed: true,
                        indicators_confirmed: []
                    },
                    user: me,
                    user_settings: {
                        locale: s.locale || 'en-US',
                        theme: s.theme || 'dark',
                        status,
                        inline_embed_media: s.inline_embed_media ?? true,
                        inline_attachment_media: s.inline_attachment_media ?? true,
                        render_embeds: s.render_embeds ?? true,
                        render_reactions: s.render_reactions ?? true,
                        show_current_game: s.show_current_game ?? true,
                        default_guilds_restricted: s.default_guilds_restricted ?? false,
                        explicit_content_filter: s.explicit_content_filter ?? 0,
                        friend_source_flags: s.friend_source_flags || { all: true },
                        guild_positions: s.guild_positions || [],
                        guild_folders: s.guild_folders || [],
                        restricted_guilds: s.restricted_guilds || [],
                        message_display_compact: s.message_display_compact ?? false,
                        convert_emoticons: s.convert_emoticons ?? true,
                        animate_emoji: s.animate_emoji ?? true,
                        developer_mode: s.developer_mode ?? false,
                        detect_platform_accounts: s.detect_platform_accounts ?? true,
                        disable_games_tab: s.disable_games_tab ?? false,
                        enable_tts_command: s.enable_tts_command ?? true
                    },
                    session_id: '00000000000000000000000000000000',
                    friend_suggestion_count: 0,
                    notes: {},
                    analytics_token: '00000000000000000000',
                    experiments: [],
                    connected_accounts: [],
                    guild_experiments: [],
                    user_guild_settings: [],
                    sessions: [{
                        session_id: '00000000000000000000000000000000',
                        client_info: {
                            client: 'unknown',
                            os: 'unknown',
                            version: null
                        }
                    }],
                    merged_members: [],
                    users: uniqueUsers,
                    notification_settings: { flags: null },
                    game_relationships: [],
                    application: null
                }
            })

            return
        }

        if (op === 3 && ws.user) {
            const status = statuses.includes(d?.status) ? d.status : 'online'

            ws.presence = {
                since: d?.since ?? null,
                status,
                afk: d?.afk ?? false,
                game: d?.game ?? null
            }

            const [rows] = await db.execute(
                `SELECT settings FROM users WHERE id=? LIMIT 1`,
                [ws.user.id]
            )

            const s = settings(rows[0]?.settings)
            s.status = status

            if (status !== 'invisible')
                s.last_status = status

            await db.execute(
                'UPDATE users SET settings=? WHERE id=?',
                [JSON.stringify(s), ws.user.id]
            )

            return
        }

        if ([4, 6, 13, 14].includes(op))
            return

        console.log('Unhandled op:', op, d)
    })

    ws.on('close', async () => {
        if (!ws.user) return

        console.log(`Disconnected: ${ws.user.username}`)

        try {
            const [rows] = await db.execute(
                `SELECT settings FROM users WHERE id=? LIMIT 1`,
                [ws.user.id]
            )

            const s = settings(rows[0]?.settings)
            const status = ws.presence?.status || s.status || 'online'

            if (statuses.includes(status) && status !== 'invisible')
                s.last_status = status

            s.status = 'invisible'

            await db.execute(
                'UPDATE users SET settings=? WHERE id=?',
                [JSON.stringify(s), ws.user.id]
            )
        } catch (err) {
            console.error('Failed to save disconnect status:', err)
        }
    })

    ws.on('error', console.error)
})

server.listen(8081, () => {
    console.log('WSS running on wss://localhost:8081')
})