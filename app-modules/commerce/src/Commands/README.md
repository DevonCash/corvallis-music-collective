# Commerce Module Commands

## Stripe Sync Command

The `commerce:sync-stripe` command allows you to manually synchronize Cashier subscription data with Stripe. This is useful for ensuring that your local database records match the current state in Stripe.

### Usage

```bash
# Sync all users with Stripe IDs
php artisan commerce:sync-stripe

# Sync a specific user by ID
php artisan commerce:sync-stripe --user=123
```

### What It Does

This command performs the following actions:

1. Retrieves subscription data directly from Stripe API
2. Updates local subscription records to match Stripe's data
3. Synchronizes subscription items (products and prices)
4. Handles subscription status, pricing, and period information
5. Removes subscription items that no longer exist in Stripe
6. Logs any errors that occur during the sync process

### Database Tables Affected

The command updates the following tables:

| Table | Description |
|-------|-------------|
| `subscriptions` | Main subscription records with status, pricing, and period information |
| `subscription_items` | Individual items within subscriptions, including products and prices |

### When to Use

- After manual changes in the Stripe dashboard
- When troubleshooting subscription discrepancies
- During data migration or system recovery
- As part of a scheduled maintenance task
- When subscription items are out of sync with Stripe

### Options

| Option | Description |
|--------|-------------|
| `--user=ID` | Sync only the specified user ID |

### Example Output

```
Starting Stripe subscription sync...
Found 25 users with Stripe IDs
[==========================] 25/25 (100%)
Sync completed: 24 successful, 1 failed
Stripe subscription sync completed!
``` 