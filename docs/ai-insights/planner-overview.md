# AI Insights — Planner Overview

Non-technical reference for product planning, UAT, and handoff to planning agents.  
For JSON schemas and code paths, see the [technical appendix](./README.md).  
For REST endpoints, see [../api/README.md](../api/README.md).

---

## What these features are

FUSION has **three AI-generated insight types**. Each reads structured human input from a wizard, calls FUSION AI, and shows read-only analysis plus (where noted) a manual leadership field.

| Feature | Product | Wizard step | Who uses it |
|---------|---------|-------------|-------------|
| **Meeting Brief** | 1-on-1 Alignment | Step 2 (before the meeting) | Leader + employee pair |
| **Meeting Synthesis** | 1-on-1 Alignment | Step 6 (after the meeting) | Leader + employee pair |
| **Readiness Review** | Annual Readiness Plan (ARP) | Step 6 | Executive / group leader |

---

## Shared product rules

| Rule | Applies to |
|------|------------|
| AI output is **read-only** in the wizard | All three |
| User must **Generate** explicitly; nothing auto-runs on page load | All three |
| **Regenerate** creates a **new version**; prior versions are kept in history (1-on-1 admin) or DB (ARP) | All three |
| AI is **advisory** — copy tells users insights are for consideration only | All three |
| Requires **FUSION LLM service** to be running and configured | All three |
| Only **group leaders** can generate ARP AI; 1-on-1 follows existing meeting permissions | ARP vs 1-on-1 |

---

# 1. AI Meeting Brief™ (1-on-1)

## Purpose

Prepare the leader and employee **before** the 1-on-1 conversation with coaching-oriented summaries: alignment, development, commitments, trends, discussion topics, opportunities, and barriers.

## When it runs

User reviews **Step 1 (Continuous Evidence)**, opens **Step 2**, then clicks **Generate AI Meeting Brief**.

## What must exist first (inputs)

| Input | Source | Required? |
|-------|--------|-----------|
| Previous 1-on-1 meetings | Past conversations for this pair | Helpful; empty if first meeting |
| Open / past commitments | Commitment records | Helpful |
| Individual insights, activities, self-assessments | Employee profile / FUSION data | Partial — some sections may be empty |
| Behavioral driver trends | Scoring history | Partial |
| AI insight trends | Prior AI outputs | Partial |
| QBR priorities, ARP priorities, 360, org context | Other FUSION modules | **Placeholder today** — AI is told data may be missing |

**Privacy rule:** Raw private preparation text from **other** meetings is **never** sent. Only distilled **prior meeting syntheses** (max ~6) plus Step 1 evidence.

## What the user sees (outputs)

Seven read-only sections on Step 2:

1. Alignment Snapshot™  
2. Development Snapshot™  
3. Commitment Review™  
4. Behavioral Trends™  
5. Suggested Discussion Areas™  
6. Emerging Opportunities™  
7. Potential Barriers™  

Each section: short bullets + longer “View Details” narrative.

## Admin / history

**WP Admin → XFusion LLM → 1-on-1 Brief History** lists every generated brief with model, cost, and full section content.

## Acceptance criteria (UAT)

- [ ] Generate works when Step 1 evidence has at least some real data  
- [ ] Step 2 shows all 7 sections after successful generate  
- [ ] Regenerate produces a new brief; old version visible in admin history  
- [ ] User cannot edit AI text in the wizard  
- [ ] Clear error message if LLM is down or misconfigured  
- [ ] First meeting (no history) still generates without crashing — honest “no data yet” copy  

## Known gaps / future work

- QBR, ARP priorities, 360, and organizational context sections are **placeholders** in evidence  
- Fallback composer may run if LLM fails (non-AI stub brief) — verify behavior in UAT  

---

# 2. AI Meeting Synthesis™ (1-on-1)

## Purpose

Summarize **this meeting only** after it happened: what was discussed, alignment level, development themes, commitments, risks, opportunities, coaching topics, and follow-ups. Future briefs may use **this synthesis** as historical context (patterns only).

## When it runs

After the meeting, user opens **Step 6** and clicks **Generate AI Meeting Synthesis** (commitments should be saved on Step 5 first).

## What must exist first (inputs)

| Input | Source | Required? |
|-------|--------|-----------|
| Leader + employee preparations | This conversation — saved to Laravel (`wp_fusion_one_on_one_preparations`) | Expected |
| Meeting notes by section | Saved to Laravel (`wp_fusion_one_on_one_notes`) | Expected |
| Commitments | Step 5 — Laravel (`wp_fusion_one_on_one_commitments`) | Expected |

**Scope rule:** Only **current conversation** data — not other meetings’ prep or notes.

## What the user sees (outputs)

Eight read-only sections:

1. Meeting Summary™  
2. Alignment Summary™ — includes **score out of 5** + label  
3. Development Summary™  
4. Commitment Summary™ — includes counts (employee / leader / open)  
5. Emerging Risks™  
6. Emerging Opportunities™  
7. Suggested Coaching Topics™  
8. Recommended Follow-up™  

Each section: bullets + detail narrative (same pattern as brief).

## Admin / history

**WP Admin → XFusion LLM → 1-on-1 Synthesis History** — version list per conversation, latest flagged.

## Acceptance criteria (UAT)

- [ ] Synthesis reflects commitments actually saved in Step 5  
- [ ] Alignment score displays as **x / 5** with label  
- [ ] Commitment summary counts match saved commitments  
- [ ] Regenerate creates new row; prior synthesis still in admin  
- [ ] Next meeting’s brief can use prior synthesis (continuity test across two meetings)  
- [ ] Empty notes/prep still generates with honest limitations stated  

## Known gaps / future work

- Commitment counts are reconciled server-side — UAT should verify counts even if AI miscounts  

---

# 3. AI Readiness Review™ (ARP Step 6)

## Purpose

Analyze the **Annual Readiness Plan** built in Steps 1–5: strategic alignment, organizational readiness, gaps, priority alignment, risk summary, and suggested focus areas for the year.

## When it runs

User opens **ARP Step 6** and clicks **Generate AI Insights** (or **Regenerate** after editing earlier steps).

## What must exist first (inputs)

| Step | Human content | Storage (Laravel) |
|------|---------------|-------------------|
| 1 Organizational Foundation | Mission, vision, values, narrative, environment | `wp_fusion_arps` |
| 2 Future State | Future narrative and desired experiences | `wp_fusion_arp_future_states` |
| 3 Organizational Readiness | Readiness priority list | `wp_fusion_arp_readiness_priorities` |
| 4 Strategic Priorities | Strategic priority list | `wp_fusion_arp_strategic_priorities` |
| 5 Organizational Learning | Assumptions, risks, opportunities, objectives | `wp_fusion_arp_learnings` |

Empty steps are allowed — AI scores conservatively and states limitations.

## What the user sees (outputs)

**Before generate:** Sections **6.1–6.6 are empty**; only the generate button and banner show.

**After generate:**

| Section | Content type |
|---------|--------------|
| **6.1** Strategic Alignment Summary™ | Score /100 donut, summary, strength bullets |
| **6.2** Organizational Readiness Assessment™ | Score /100, strengths / development / critical gap counts |
| **6.3** Potential Gaps™ | Table: area, description, impact, priority |
| **6.4** Priority Alignment™ | Score /100 + dimension progress bars |
| **6.5** Risk Summary™ | High / medium / low risk + strengths counts |
| **6.6** Suggested Areas of Focus™ | Action-oriented focus list |

**Leadership Context™** (below AI blocks): **Editable** textarea — executive adds manual context for the year. Saved separately from AI; not generated by AI.

## Permissions

- **View** ARP: any group member  
- **Generate / regenerate / edit leadership context**: group **leader** only  
- View-only users see disabled generate button  

## Admin / history

**No wp-admin history page yet.** Each generate appends a DB record; leadership context carries forward on regenerate.

**Planner note:** Roadmap item — ARP AI history admin (parity with 1-on-1 Brief/Synthesis History).

## Acceptance criteria (UAT)

- [ ] Empty state before first generate (6.1–6.6 blank)  
- [ ] Generate fills all six sections matching plan content in Steps 1–5  
- [ ] Regenerate after editing Step 3 priorities updates gaps/focus areas  
- [ ] Leadership context saves via Save Draft without re-generating AI  
- [ ] Regenerate preserves leadership context from previous row  
- [ ] View-only member cannot generate  
- [ ] Generate button shows loading / error states clearly  

## Known gaps / future work

- No admin UI for ARP AI version history  
- `wp_fusion_evidence_log` writes on ARP publish / AI generate — no cross-component UI yet  

---

# Cross-feature dependencies

```text
┌─────────────────────────────────────────────────────────────┐
│                     FUSION LLM Service                       │
│         (OpenAI + prompts + Bearer auth)                     │
└────────────────────────▲────────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
   1-on-1 Brief    1-on-1 Synthesis   ARP Review
         │               │               │
         ▼               ▼               ▼
   Step 1 evidence   This meeting    ARP Steps 1–5
   + prior synth     prep/notes/     (foundation,
                     commitments      priorities,
                                      learning)
```

| Dependency | Impact if missing |
|------------|-------------------|
| LLM server down | Generate fails with user-visible error |
| Wrong API key (Laravel ↔ LLM) | Auth error on generate |
| ARP Steps 1–5 not saved in wizard | Partial or empty plan context for AI |
| Steps 3–4 empty (ARP) | Weaker readiness / strategic analysis |

---

# Suggested UAT test scenarios (planner checklist)

## Scenario A — First 1-on-1 ever

1. New pair, no prior meetings  
2. Generate brief → expect honest “limited history” tone, no crash  
3. Complete meeting → generate synthesis  
4. Schedule second meeting → brief should reference first synthesis  

## Scenario B — Full ARP year plan

1. Complete ARP Steps 1–5 with realistic content  
2. Step 6 generate → all donuts, gaps table, focus areas populated  
3. Edit Step 4 strategic priority → regenerate → focus/gaps should shift  
4. Add leadership context → save draft → reload → text persists without regen  

## Scenario C — Permissions

1. Non-leader group member opens ARP Step 6 → view only, no generate  
2. Leader generates → success  

## Scenario D — Operations

1. LLM stopped → user sees clear failure, not silent empty UI  
2. LLM restored → generate succeeds without code deploy on WordPress  

---

# What to give a planning agent

**Minimum bundle:**

1. This file (`planner-overview.md`)  
2. One of the [technical docs](./README.md) only if the planner needs field-level detail  
3. Current environment note, e.g. “LLM at :8000, ARP Step 6 live, QBR evidence placeholder”  

**Prompt example for a planner AI:**

> Using `planner-overview.md`, draft a Q3 UAT plan for ARP Step 6 AI Readiness Review. Include acceptance criteria, test scenarios, and open gaps. Do not propose code changes.

---

# Document map

| Audience | Read this |
|----------|-----------|
| Product / planning AI | **This file** |
| Engineering / coding AI | [README.md](./README.md) + feature-specific JSON docs |
| QA manual testing | Acceptance criteria + UAT scenarios in this file |
