import fs from 'fs'
import https from 'https'
import mysql from 'mysql2/promise'
import { WebSocketServer } from 'ws'

const db = mysql.createPool({
    host: '127.0.0.1',
    user: 'root',
    password: 'root',
    database: 'dizcard'
})

try {
    const dbgC = await db.getConnection()
    dbgC.release()
} catch (e) {
    console.error(e)
    process.exit(1)
}

const server = https.createServer({key: fs.readFileSync('./key.pem'), cert: fs.readFileSync('./cert.pem')})

const wss = new WebSocketServer({ server, perMessageDeflate: false })
const heartbeat = 45000
const statuses = ['online', 'idle', 'dnd', 'invisible']

const send = (ws, d) => {if (ws.readyState === 1) ws.send(JSON.stringify(d))}

function settings(v) {try {return typeof v === 'string' ? JSON.parse(v) || {} : v || {}} catch {return {}}}

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
    const [rows] = await db.query(`SELECT id, username, discriminator, email, settings FROM users WHERE id IN (${unique.map(() => '?').join(',')})`, unique)
    return rows.map(user)
}

async function friends(s) {return getUsers(Array.isArray(s.friends) ? s.friends : [])}
async function pendingIncoming(s) {return getUsers(Array.isArray(s.pending_incoming) ? s.pending_incoming : [])}
async function pendingOutgoing(s) {return getUsers(Array.isArray(s.pending_outgoing) ? s.pending_outgoing : [])}

wss.on('connection', ws => {
    ws.seq = 0
    ws.user = null
    ws.presence = null

    send(ws, {op: 10, s: null, d: { heartbeat_interval: heartbeat }})

    ws.on('message', async raw => {
        let m
        try {
            m = JSON.parse(raw)
        } catch {
            return ws.close(4000, 'Invalid payload')
        }

        const { op, d } = m

        if (op === 1) return send(ws, { op: 11, d: null })

        if (op === 2) {
            if (!d?.token) return ws.close(4004, 'Authentication required')

            const [rows] = await db.execute(`SELECT id, username, discriminator, email, token, settings FROM users WHERE token=? LIMIT 1`, [d.token])

            if (!rows.length) return ws.close(4004, 'Invalid token')

            ws.user = rows[0]
            console.log(`Connected: ${ws.user.username}`)
            console.log(raw.toString()) // debug

            const s = settings(ws.user.settings)
            const savedStatus = s.last_status || s.status
            const status = statuses.includes(savedStatus) ? savedStatus : 'online'
            s.status = status
            ws.presence = { since: null, status, afk: false, game: null }
            await db.execute('UPDATE users SET settings=? WHERE id=?', [JSON.stringify(s), ws.user.id])

            const me = user(ws.user)
            const fs = await friends(s)
            const incoming = await pendingIncoming(s)
            const outgoing = await pendingOutgoing(s)

            const relationships = [
                ...fs.map(f => ({ id: f.id, type: 1, user: f })),
                ...incoming.map(f => ({ id: f.id, type: 3, user: f })),
                ...outgoing.map(f => ({ id: f.id, type: 4, user: f }))
            ]

            const channels = fs.map((f, i) => ({
                id: String(i),
                type: 1,
                recipients: [f],
                last_message_id: null
            }))

            const presences = fs.map(f => ({
                user: { id: f.id },
                status: f.settings?.status || 'invisible',
                activities: []
            }))

            const uniqueUsers = [...new Map([...fs, ...incoming, ...outgoing].map(f => [String(f.id), f])).values()]

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
                    heartbeat_interval: heartbeat,
                    resume_gateway_url: 'wss://localhost:8081',
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

            const [rows] = await db.execute('SELECT settings FROM users WHERE id=? LIMIT 1', [ws.user.id])
            const s = settings(rows[0]?.settings)
            s.status = status
            if (status !== 'invisible') s.last_status = status
            await db.execute('UPDATE users SET settings=? WHERE id=?', [JSON.stringify(s), ws.user.id])
            return
        }

        if (op === 4 || op === 6 || op === 13 || op === 14) return

        console.log('Unhandled op:', op, d)
    })

    ws.on('close', async () => {
        if (!ws.user) return
        console.log(`Disconnected: ${ws.user.username}`);
        try {
            const [rows] = await db.execute('SELECT settings FROM users WHERE id=? LIMIT 1', [ws.user.id])
            const s = settings(rows[0]?.settings)
            const status = ws.presence?.status || s.status || 'online'
            if (statuses.includes(status) && status !== 'invisible')
                s.last_status = status
            s.status = 'invisible'
            await db.execute('UPDATE users SET settings=? WHERE id=?', [JSON.stringify(s), ws.user.id])
        } catch (err) {console.error('Failed to save disconnect status:', err)}
    })
    ws.on('error', console.error)
})

server.listen(8081, () => {console.log('WSS running on wss://localhost:8081')})