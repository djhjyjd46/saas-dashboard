---
name: laravel-testing-standard
description: Mandatory testing workflow for Laravel 11+ development. Use when: (1) Adding a new feature, (2) Implementing model logic, (3) Creating Livewire components, (4) Refactoring existing code. Every change must be covered by automated tests (Feature or Unit) after implementation and before moving to the next task.
---

# Laravel Testing Standard

This skill defines the mandatory workflow for ensuring code quality in Laravel 11+ applications.

## Core Principle: Feature-Test-Feature

Never move to a new task until the current one is covered by tests.

1.  **Implement** the feature or logic.
2.  **Add Test**: Create a corresponding test (Feature or Unit).
3.  **Run**: Execute `php artisan test` and ensure all tests are green (PASS).
4.  **Refactor**: Clean up the code knowing you have a safety net.

## 🔴 Feature Tests (Integration)

Use for testing the user-facing side of the application.

- Location: `tests/Feature/`
- Tool: `Livewire::test()`, `$this->get()`, `$this->post()`.
- Scope: Controllers, Livewire components, API endpoints, Middleware.

**Example (Livewire Component):**

```php
Livewire::test(MyComponent::class)
    ->set('search', 'item')
    ->assertSee('Found Item');
```

## 🟢 Unit Tests (Logic)

Use for testing isolated pieces of logic, model methods, and helper functions.

- Location: `tests/Unit/`
- Scope: Model attributes, calculations, string manipulation, service methods.
- Requirement: No DB access (if possible).

**Example (Model Attribute):**

```php
$model = new User(['status' => 'PENDING']);
$this->assertEquals('pending', $model->normalized_status);
```

## 🛠️ Testing Tools

- **Factories**: Always use `Model::factory()->create()` to generate test data.
- **RefreshDatabase**: Use the `RefreshDatabase` trait in Feature tests to keep the DB clean.
- **Mockery**: Use for mocking external services or heavy dependencies.

## ✅ Verification

Always run the full suite before finishing a task:

```bash
php artisan test
```
