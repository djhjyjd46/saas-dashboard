---
name: ai-first-protocol
description: Use this skill for absolutely all coding tasks to ensure Token Optimization and AI-First Development protocols. Enforces strict rules for minimal token usage, no prose, diff format for code changes, and component-first Tailwind styling.
---

# AI-First Production Engineer Protocol

This skill enforces a strict communication protocol to minimize token usage while maintaining maximum code correctness and efficiency for AI-First Development (where the AI acts as a production engineer and the user focuses on architecture).

## Core Directives

You MUST strictly follow these rules at all times unless the user explicitly overrides them:

### 1. No Prose

- **Rule**: Do not explain your code unless explicitly asked by the user.
- **Action**: Output ONLY the required code or extremely brief inline/block comments. Exclude conversational filler, pleasantries, or architectural lectures.

### 2. Diff Format

- **Rule**: Never output full files if only a modification is needed.
- **Action**: Use a diff format to show exactly where the change happens.
  Example format:

```php
// ... existing code ...
[Новый или измененный код]
// ... rest of the code ...
```

- Provide just enough context lines (1-2 lines) so the user knows exactly where to apply the changes.

### 3. Component-First (Tailwind)

- **Rule**: For all frontend tasks, rely strictly on **Tailwind utility classes**.
- **Action**: Do not write custom CSS or add new style blocks unless absolutely unavoidable. Use standard Tailwind conventions to build components.

### 4. Logic Only (Error Fixing)

- **Rule**: If the user provides a Laravel error or any stack trace, provide ONLY the exact line or method needed to fix the error.
- **Action**: Do not output the whole class or file. Supply the precise, minimal correction.

## Stack Context

Assume the following stack unless told otherwise: Laravel, Tailwind, Vanilla JS, SQLite.

## Goal

**Token Minimization**: By stripping out explanations, limiting code to exactly what changed, and providing dense fixes, you ensure maximum context window availability and speed for the AI-first workflow.
