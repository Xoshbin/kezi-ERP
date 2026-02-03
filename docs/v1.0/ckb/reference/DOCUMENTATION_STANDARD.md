---
title: Documentation Standard
icon: heroicon-o-document-text
order: 1
---

# Documentation Standard: The Diátaxis Framework

We follow the **Diátaxis framework** to organize our documentation. This framework prevents the common mistake of mixing "learning" with "technical specs" by splitting documentation into four distinct categories based on the user's needs.

---

## 1. The 4 Quadrants

Every piece of documentation must fall into one of these four categories:

| Quadrant | Type | Goal | User's Mindset | Example |
| :--- | :--- | :--- | :--- | :--- |
| **1. Tutorials** | *Learning-oriented* | "Show me how to start." | I am a beginner. | "Creating your first Invoice from scratch." |
| **2. How-to Guides** | *Task-oriented* | "How do I do X?" | I am stuck / need to do work. | "How to generate a PDF report for Q3." |
| **3. Reference** | *Information-oriented* | "I need specs." | I need facts/data. | "API Endpoint: `POST /api/v1/invoices` parameters." |
| **4. Explanation** | *Understanding-oriented* | "Why does it work this way?" | I want to understand the system. | "Why we use the Repository Pattern for Billing." |

---

## 2. Directory Structure

We use a strict folder structure to keep these separate. Do not mix them.

```text
docs/
├── tutorials/      # Lessons for beginners (e.g., "First Entry")
├── how-to/         # Recipes/Guides for specific tasks (e.g., "Add custom tax")
├── reference/      # Technical specs (API docs, Class references)
└── explanation/    # Design decisions, Architecture, Schema
```

---

## 3. Writing Guidelines by Quadrant

### 1. Tutorials (Learning)
*   **Goal**: Take the user by the hand and lead them through a complete workflow.
*   **Tone**: Teacher / Instructor ("Let's do this...").
*   **Structure**: Ordered steps, no distractions, guaranteed success.
*   **Do**: "First, open the dashboard..."
*   **Don't**: Explain distinct options or edge cases here. Keep it to the "Happy Path".

### 2. How-to Guides (Tasks)
*   **Goal**: Solve a specific problem or complete a specific task.
*   **Tone**: Helper / Colleague.
*   **Structure**: Problem statement -> Solution steps.
*   **Do**: Use specific titles like "How to cancel a paid invoice".
*   **Don't**: Teach basic concepts (that's for Tutorials) or explain theory (that's for Explanation).

### 3. Reference (Information)
*   **Goal**: Provide accurate, complete facts.
*   **Tone**: Neutral / Dictionary.
*   **Structure**: Consistent, structured data (Tables, Lists).
*   **Do**: "Function `calculateTax(float $amount)` returns float."
*   **Don't**: Use opinions or instructional language.
*   **Note**: API documentation is largely automated using **Scramble**.

### 4. Explanation (Understanding)
*   **Goal**: Clarify context, history, and choices.
*   **Tone**: Architect / Expert.
*   **Structure**: Essays, Diagrams, background context.
*   **Do**: Discuss the "Why" and the "Big Picture".
*   **Don't**: Include code steps or instructions.

---

## 4. General Writing specific to Kezi

While the structure changes, our friendly tone remains (especially for Tutorials and How-to guides).

### Tone & Style
*   **Conversational**: Write like you're explaining to a smart friend.
*   **Plain Language**: Avoid jargon soup.
*   **Visuals**: Use tables, ASCII diagrams, and screenshots.

### File Naming
*   Use `kebab-case.md` (e.g., `creating-first-invoice.md`).
*   For translations, use `filename.lang.md` (e.g., `creating-first-invoice.ckb.md`) in the **same directory**.

---

## 5. Tools

*   **Reference**: We use `dedoc/scramble` to auto-generate API documentation from routes and PHPDocs.
*   **Site Generation**: We use **VitePress** to publish `docs/` as a static site.

---

## Quick Checklist

Before merging documentation:
1.  [ ] Is it in the right folder? (Tutorial vs How-to vs Explanation)
2.  [ ] Does it stick to its quadrant's goal? (Don't explain "Why" in a Tutorial)
3.  [ ] Is the tone appropriate?
4.  [ ] are images/diagrams included?
