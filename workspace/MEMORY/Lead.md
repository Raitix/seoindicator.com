# Lead.md — Persistent Memory

Important notes, lessons learned, and critical context that shouldn't be forgotten.

---

## Agent Identity
- **Name:** Lead
- **Role:** Main agent
- **Model:** kilo/z-ai/glm-5:free
- **User:** Raitis (Europe/Riga)

## Workspace Structure
```
workspace/
├── AGENTS.md         — workspace instructions, agent management
├── AGENTS/           — agent definitions (Lead.md, future specialists)
├── IDENTITY.md       — agent identity (points to AGENTS/<name>.md)
├── SOUL.md           — core behavior directives
├── USER.md           — user profile
├── MEMORY/
│   ├── Lead.md       — persistent memory (this file)
│   └── TEMP/         — daily temporary memories (YYYY-MM-DD.md)
├── TOOLS.md          — local notes template
└── HEARTBEAT.md      — heartbeat instructions
```

## Git Repository
- Location: `C:\Users\Raitis\.openclaw`
- Initial commit: `afae3f5`
- Tracked: workspace files, agent definitions
- Ignored: sessions, tokens, credentials, openclaw.json

## Key Lessons
- User prefers direct execution — no asking for confirmation
- Minimal token consumption preferred
- Context compaction: monitor and save important memories before it happens

## Important Notes
- Model preference: `kilo/z-ai/glm-5:free` for main agent
- Message format: `**model provider/name : agent name**`
- Subagent sessions persist in gateway memory until restart — no API to kill them directly
