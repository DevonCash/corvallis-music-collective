# Specification Test Coverage Command

This command parses specification files (`.spec.md` or `.spec.yaml`) and compares them against test coverage annotations in test files.

## Usage

```bash
php artisan spec:test-coverage [module] [--output=format] [--uncovered]
```

### Arguments

- `module` (optional): The specific module to analyze. If not provided, all modules will be analyzed.

### Options

- `--output=format` (optional): Output format. Available formats: `table` (default), `json`, `csv`.
- `--uncovered` (optional): Show only uncovered requirements.

## Examples

Analyze all modules and display results in a table:
```bash
php artisan spec:test-coverage
```

Analyze a specific module:
```bash
php artisan spec:test-coverage practice-space
```

Show only uncovered requirements:
```bash
php artisan spec:test-coverage --uncovered
```

Export results to CSV:
```bash
php artisan spec:test-coverage --output=csv > coverage.csv
```

## Specification Formats

The command supports two specification formats:

### YAML Format (Recommended)

YAML specifications should follow the format defined in the specification-format.mdc rule:

```yaml
metadata:
  version: "1.0.0"
  date: "2023-05-01"
  status: "Draft"
  module: "example"

requirements:
  - id: "REQ-001"
    description: "The system shall provide a user authentication mechanism."
    type: "mandatory"
    priority: "High"
    acceptance_criteria: "Users can log in with valid credentials."
    tests: ["TEST-001", "TEST-002"]
    related_requirements: ["REQ-002", "REQ-003"]
```

### Markdown Format (Legacy)

Markdown specifications should follow this format:

```markdown
- **REQ-001**: The system shall maintain detailed profiles for each practice space.
  - **Priority**: High
  - **Acceptance Criteria**: Room profiles contain complete information with at least 5 descriptive fields.
```

## Test Annotation Format

The command expects test methods to be annotated with PHPDoc comments as defined in the testing.mdc rule:

```php
/**
 * @test
 * @covers REQ-001, REQ-002
 */
public function testSomeFunctionality()
{
    // Test code
}
```

## Output

The command provides the following information:

- Total number of requirements found
- Number and percentage of requirements covered by tests
- Detailed list of requirements with their coverage status
- For covered requirements, the number of tests covering each requirement

## Integration with CI/CD

You can integrate this command into your CI/CD pipeline to ensure that all requirements are covered by tests:

```bash
php artisan spec:test-coverage --uncovered --output=json | jq -e 'if (. | length > 0) then halt(1) else empty end'
```

This will fail the pipeline if there are any uncovered requirements. 