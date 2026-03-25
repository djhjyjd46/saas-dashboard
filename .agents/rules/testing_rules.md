# UI and Integration Testing Rules

To ensure autonomous testing via Puppeteer or other tools, the following rules MUST be followed:

1. **Test Credentials**: Always look for `TEST_AGENT_EMAIL` and `TEST_AGENT_PASSWORD` in the project's `.env` file for authentication.
2. **Login First**: Before testing protected routes, use the login form to authenticate using these credentials.
3. **No Brute Force**: If login fails twice, stop and ask the user for updated credentials.
4. **Local Environment**: Prioritize internal/local URLs (e.g., those from OSPanel) for testing unless explicitly told to check production.

## New Project Initialization

When starting a new project, the assistant MUST:

1. **Create a Test User**: Generate a migration or seeder to create a dedicated test administrator.
2. **Populate .env**: Automatically add `TEST_AGENT_EMAIL` and `TEST_AGENT_PASSWORD` to the `.env` file using the generated credentials.
3. **Ensure Connectivity**: Verify the login works via Puppeteer as the final step of project initialization.

## Laravel Development & Testing Standard

When working on Laravel 11+ applications, the assistant MUST follow the **Feature-Test-Feature** workflow:

1. **Mandatory Coverage**: Every new feature, model method, or Livewire component must be covered by automated tests (**Feature** and/or **Unit**).
2. **Immediate Testing**: Tests should be written immediately after the feature implementation (or before, if following TDD) and before proceeding to the next task.
3. **Run Full Suite**: All tests must be verified by running `php artisan test` after any significant changes.
4. **Isolated Logic**: Business logic in Models or Services should be prioritized for **Unit** tests (fast, no DB).
5. **Integration/UI**: Livewire components and Controllers must be covered by **Feature** tests using `Livewire::test()` or HTTP assertions.
6. **Data Preparation**: Always use Model Factories to generate consistent test data.
7. **Clean State**: Always use the `RefreshDatabase` trait in Feature tests to ensure a clean testing environment.
