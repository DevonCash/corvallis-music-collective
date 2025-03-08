# State Management Module Tests

This directory contains tests for the State Management module.

## Test Structure

- `Unit/`: Contains unit tests for individual components
  - `AbstractStateTest.php`: Tests for the AbstractState class
  - `Casts/StateTest.php`: Tests for the State cast class
  - `Traits/HasStatesTest.php`: Tests for the HasStates trait
  - `Contracts/StateInterfaceTest.php`: Tests for the StateInterface contract
  - `StateManagementServiceProviderTest.php`: Tests for the service provider
- `Feature/`: Contains feature tests for the module
  - `StateManagementTest.php`: Integration tests for the state management system

## Running Tests

To run the tests, use the following command from the root of the module:

```bash
vendor/bin/phpunit
```

Or to run a specific test:

```bash
vendor/bin/phpunit --filter=AbstractStateTest
```

## Test Coverage

To generate a test coverage report, use:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage
```

This will generate an HTML coverage report in the `coverage` directory.

## Writing New Tests

When writing new tests for the State Management module, follow these guidelines:

1. Place unit tests in the `Unit/` directory
2. Place feature/integration tests in the `Feature/` directory
3. Name test files with the `Test` suffix
4. Use descriptive test method names that explain what is being tested
5. Use the `@test` annotation or prefix test methods with `test_`
6. Mock external dependencies when appropriate 