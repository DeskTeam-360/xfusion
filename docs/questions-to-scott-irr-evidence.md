# Questions to Scott — IRR Individual Evidence™ (Steps 1 & 2)

**Context:** The Individual Readiness Review™ wizard Step 1 (evidence checklist) and Step 2 (evidence dashboard) are now wired to real FUSION data where sources exist. Several UI blocks and checklist items from the mockups **do not have a clear data source** in the platform today. We need product direction before engineering can finish them.

**Reference:** [`irr-individual-evidence.md`](./irr-individual-evidence.md) — full live vs.not-started checklist.

**Review scope:** One IRR per `(employee, calendar year)`. Evidence is aggregated for the employee under review, scoped to their company group for org-average comparisons.

---

## 1. Behavioral Driver Trends — monthly line chart

**What the mockup shows:** A Jan–Dec line chart comparing “Your Score” vs “Organization Average” over the year.

**What we have today:** Latest weighted average per Behavioral Driver (0–5 scale) from course scoring groups + org average across group members. **No monthly time-series.**

**Questions for Scott:**

1. Should the chart use **monthly scoring submissions** (Gravity Forms entries grouped by month), **monthly COR Unified evaluations**, or something else?
2. Is one data point per month required, or is quarterly acceptable?
3. If the employee has no submissions in a given month, should the line **gap**, **carry forward** the last score, or show zero?

---

## 2. Development Trends (Strategic Thinking, Delegation, Change Leadership, Coaching, Influencing)

**What the mockup shows:** Progress bars separate from “Strength Trends” (Leadership, Accountability, Problem Solving, etc.).

**What we have today:** Self-assessment scoring groups map to **Alignment, Accountability, Communication, Leadership, Execution** — not the Development Trends labels in the mockup. No scoring-group mapping exists for Strategic Thinking, Delegation, etc.

**Questions for Scott:**

1. Are Development Trends **specific course scoring groups** we should map (please provide exact group titles as they appear in wp_course_scoring_groups)?
2. Or should they come from **AI Development Assessment™ (Step 3)** output rather than raw evidence (Step 2)?
3. Should Strength Trends and Development Trends both appear in Step 2, or should Development Trends move to Step 3 only?

---

## 3. Strength Trends — extra dimensions

**What the mockup shows:** Leadership, Accountability, Problem Solving, Communication, Adaptability.

**What we have today:** Five COR capability dimensions — Alignment, Accountability, Communication, Leadership, Execution.

**Questions for Scott:**

1. Confirm Step 2 Strength Trends should use the **COR five** (Alignment, Accountability, Communication, Leadership, Execution), not the mockup labels.
2. If Problem Solving and Adaptability are required, where do they live in FUSION (scoring group names or another instrument)?

---

## 4. Growth Timeline — quarter focus labels

**What the mockup shows:** Q1–Q4 each with a thematic focus (e.g. “Process Improvement”, “Team Development”) plus commitment count.

**What we have today:** Commitment count per quarter from 1-on-1 commitments; focus label is placeholder (first commitment title or behavioral driver in that quarter).

**Questions for Scott:**

1. What is the **authoritative source** for quarter focus theme?
   - First 1-on-1 synthesis theme that quarter?
   - ARP / QBR priority name?
   - Manager-entered field?
   - AI-generated label in a future step?
2. Should empty quarters still appear on the timeline with “No focus recorded”?

---

## 5. Reflection Themes (Step 1 checklist item)

**What the mockup shows:** Listed in Step 1 “Evidence Being Compiled” but **not** in the Step 2 dashboard.

**What we have today:** No aggregation service. Private reflections / journals are not wired.

**Questions for Scott:**

1. What is Reflection Themes — private employee journal entries, 1-on-1 prep fields, or another module?
2. Should it appear in **Step 2** as well, or stay checklist-only in Step 1?
3. **Privacy rule:** Can distilled themes enter the evidence snapshot, or must they stay out until Step 3 AI assessment?

---

## 6. Organizational Context & Organizational Alignment

**What the mockup shows:** Step 2 bullet list about alignment with team priorities, QBR/ARP objectives, and values.

**What we have today:** Placeholders in the 1-on-1 brief evidence bundle; no individual-level linkage to QBR or ARP priorities.

**Questions for Scott:**

1. Define **Organizational Context** vs **Organizational Alignment** — same thing or two different sources?
2. For alignment bullets, should we match employee commitments to QBR/ARP priorities **by name** (same pattern as QBR Step 5 org priority link)?
3. Is company-wide event context (reorgs, leadership changes) in scope, and if so where is it stored?

---

## 7. QBR & ARP Priorities (Step 1 checklist)

**What the mockup shows:** “Quarterly priorities and strategic objectives alignment.”

**What we have today:** Not implemented for IRR evidence.

**Questions for Scott:**

1. Should IRR pull **active ARP strategic priorities** for the employee’s company group?
2. Should it pull **QBR commitments/objectives** from the four quarters of the review year?
3. Display in Step 1 checklist only, Step 2 section, or both?

---

## 8. Development Participation — “Not Assigned” bucket

**What the mockup shows:** Completed / In Progress / Not Started / **Not Assigned** (donut breakdown).

**What we have today:** Submission counts per program type (Transform, Sustain, Revitalize). No assignment model for “this activity was assigned to this employee.”

**Questions for Scott:**

1. Is there an existing **course assignment** concept we should use (table/workflow)?
2. If not, should we simplify to **submissions only** and drop Not Assigned from the UI?
3. Does “In Progress” mean partial GF save, enrolled-but-not-submitted, or something else?

---

## 9. Evidence Highlights — year-over-year trends

**What we have today:** Compares current year counts to **prior-year IRR snapshot** when a review exists for `year - 1`.

**Questions for Scott:**

1. Is prior IRR snapshot the correct baseline, or should YoY compare to **calendar-year activity** even without a published IRR?
2. What should display when there is **no prior IRR** — hide trend, show “N/A”, or compare to org average?

---

## 10. Insufficient evidence gate

**Technical spec (AC2):** A 360/IRR review should not proceed with zero completed 1-on-1 conversations in the review year.

**Questions for Scott:**

1. Confirm this rule applies to **IRR** (not only legacy 360 naming).
2. Minimum bar: at least **one completed 1-on-1**, or also require COR Unified Insight / activities?
3. Should Step 1 **block Generate**, or allow generate with a visible warning?

---

## 11. Step 3+ LLM (for planning — not Step 2)

Not blocking Step 2 display, but affects what evidence we send later.

**Questions for Scott:**

1. Confirm LLM endpoints will be named **`/api/v1/360/development-assessment`** and **`development-synthesis`** per technical spec.
2. Any fields in Step 2 snapshot that must **never** be sent to the LLM (beyond raw GF answers and private prep)?

---

## Summary — decisions needed to close Step 2

| Priority | Topic | Blocks |
|----------|-------|--------|
| High | Development Trends source | Step 2 right column mockup |
| High | Monthly driver chart definition | Step 2 line chart |
| High | QBR/ARP priority linkage | Step 1 item + Step 2 alignment |
| Medium | Growth Timeline focus label | Step 2 timeline quality |
| Medium | Participation Not Assigned / In Progress | Step 2 donut accuracy |
| Medium | Reflection Themes | Step 1 checklist + possible Step 2 |
| Lower | Strength Trends label mapping | Cosmetic vs COR five |
| Lower | YoY trend baseline | Evidence Highlights footer |

---

*Document owner: Engineering (DeskTeam 360). Please reply inline or schedule a short review to unblock IRR Step 2 completion.*
