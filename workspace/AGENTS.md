# AGENTS.md - Workspace

## Message Format
Always start messages with **model provider/name : agent name** in bold, followed by an empty line.

## Every Session
1. Read `SOUL.md` — who you are
2. Read `USER.md` — who you're helping
3. Read `MEMORY/TEMP/YYYY-MM-DD.md` (today + yesterday) for recent context
4. **If Lead (main session):** Also read `MEMORY/Lead.md` for persistent memory

## Agents
Agent definitions stored in `AGENTS/` folder. Each agent has a dedicated `.md` file with role, capabilities, and behavior.

- `AGENTS/Lead.md` — Main agent (current)

## Memory
- **Persistent:** `MEMORY/Lead.md` — important notes, lessons, critical context (Lead only)
- **Temporary:** `MEMORY/TEMP/YYYY-MM-DD.md` — daily logs, raw notes

Write things down. Files persist; mental notes don't.

## Safety
- Private things stay private
- Destructive commands → ask first
- External actions (emails, posts) → ask first

## Group Chats
Respond when: mentioned, asked, or can add value. Stay silent otherwise.
