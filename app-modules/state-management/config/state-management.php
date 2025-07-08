<?php

return [
    /*
    |--------------------------------------------------------------------------
    | State Management Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the state management
    | system. You can customize behavior, logging, validation, and UI options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how errors are handled throughout the state management system.
    |
    */
    'error_handling' => [
        // Whether to display user-friendly error messages in the UI
        'user_friendly_messages' => true,
        
        // Whether to log state transition errors
        'log_errors' => true,
        
        // Log channel to use for state management errors
        'log_channel' => env('STATE_MANAGEMENT_LOG_CHANNEL', 'stack'),
        
        // Whether to throw exceptions on invalid state transitions
        'throw_exceptions' => true,
        
        // Whether to send notifications for failed state transitions
        'send_notifications' => true,
        
        // Maximum number of retry attempts for failed transitions
        'max_retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Configure validation behavior for state transitions.
    |
    */
    'validation' => [
        // Whether to validate state transitions before executing them
        'validate_transitions' => true,
        
        // Whether to validate required fields before state transitions
        'validate_required_fields' => true,
        
        // Whether to run custom validation rules defined in state classes
        'run_custom_validation' => true,
        
        // Whether to cache validation results for performance
        'cache_validation_results' => false,
        
        // Cache TTL for validation results (in seconds)
        'validation_cache_ttl' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for state management operations.
    |
    */
    'logging' => [
        // Whether to log successful state transitions
        'log_transitions' => true,
        
        // Whether to log state validation attempts
        'log_validation' => false,
        
        // Whether to log performance metrics
        'log_performance' => env('STATE_MANAGEMENT_LOG_PERFORMANCE', false),
        
        // Whether to log bulk operations
        'log_bulk_operations' => true,
        
        // Log level for state management operations
        'log_level' => env('STATE_MANAGEMENT_LOG_LEVEL', 'info'),
        
        // Whether to include sensitive data in logs
        'include_sensitive_data' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings.
    |
    */
    'performance' => [
        // Whether to cache state definitions
        'cache_state_definitions' => env('STATE_MANAGEMENT_CACHE_STATES', true),
        
        // Cache TTL for state definitions (in seconds)
        'state_cache_ttl' => 3600,
        
        // Whether to eager load state relationships
        'eager_load_relationships' => true,
        
        // Whether to use database transactions for state changes
        'use_database_transactions' => true,
        
        // Batch size for bulk state operations
        'bulk_operation_batch_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure UI behavior and appearance.
    |
    */
    'ui' => [
        // Whether to show state transition history in the UI
        'show_transition_history' => true,
        
        // Whether to show state transition forms
        'show_transition_forms' => true,
        
        // Whether to show confirmation dialogs for state transitions
        'show_confirmation_dialogs' => true,
        
        // Whether to show progress indicators during transitions
        'show_progress_indicators' => true,
        
        // Default icon set to use for states
        'default_icon_set' => 'heroicon-o',
        
        // Whether to use custom colors for state badges
        'use_custom_colors' => true,
        
        // Default colors for common state types
        'default_colors' => [
            'pending' => 'gray',
            'active' => 'success',
            'completed' => 'info',
            'cancelled' => 'danger',
            'archived' => 'secondary',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Configure security-related settings.
    |
    */
    'security' => [
        // Whether to check user permissions for state transitions
        'check_permissions' => true,
        
        // Whether to audit state transitions
        'audit_transitions' => true,
        
        // Whether to validate model ownership before transitions
        'validate_model_ownership' => false,
        
        // Whether to rate limit state transitions
        'rate_limit_transitions' => false,
        
        // Rate limit per user per minute
        'rate_limit_per_minute' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    |
    | Configure activity logging for state transitions.
    |
    */
    'activity_logging' => [
        // Whether to log activities using Spatie Activity Log
        'enabled' => true,
        
        // Default log name for state transitions
        'default_log_name' => 'state_transition',
        
        // Whether to log additional properties
        'log_properties' => true,
        
        // Whether to log the user who performed the transition
        'log_causer' => true,
        
        // Whether to log the model being transitioned
        'log_subject' => true,
        
        // Custom activity log attributes
        'custom_attributes' => [
            'ip_address' => false,
            'user_agent' => false,
            'session_id' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notification settings for state transitions.
    |
    */
    'notifications' => [
        // Whether to send notifications for state transitions
        'enabled' => true,
        
        // Default notification channels
        'default_channels' => ['database'],
        
        // Whether to send notifications to model owners
        'notify_model_owners' => false,
        
        // Whether to send notifications to administrators
        'notify_administrators' => false,
        
        // Whether to send notifications for failed transitions
        'notify_on_failure' => true,
        
        // Whether to send notifications for successful transitions
        'notify_on_success' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Development
    |--------------------------------------------------------------------------
    |
    | Configuration options for development and debugging.
    |
    */
    'development' => [
        // Whether to enable debug mode
        'debug_mode' => env('STATE_MANAGEMENT_DEBUG', false),
        
        // Whether to dump state transition data for debugging
        'dump_transition_data' => false,
        
        // Whether to enable verbose logging
        'verbose_logging' => env('STATE_MANAGEMENT_VERBOSE_LOGGING', false),
        
        // Whether to validate state class configurations on boot
        'validate_configurations' => env('APP_DEBUG', false),
    ],
];