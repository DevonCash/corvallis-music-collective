<?php

$file = file_get_contents('app-modules/practice-space/tests/Feature/RecurringBookingTest.php');

// Modified pattern to capture all @covers annotations in a test method
preg_match_all('/\/\*\*\s*\n(?:.*\n)*?\s*\*\s*@test\s*\n(?:.*\n)*?(?:\s*\*\s*@covers\s+([^*\n]+)\n)+/', $file, $testBlocks, PREG_SET_ORDER);

echo "Found " . count($testBlocks) . " test methods\n";

foreach ($testBlocks as $index => $testBlock) {
    echo "Test method #" . ($index + 1) . ":\n";
    
    // Now extract all @covers from each test block
    preg_match_all('/\*\s*@covers\s+([^*\n]+)/', $testBlock[0], $coversMatches);
    
    foreach ($coversMatches[1] as $requirement) {
        echo "  Requirement: " . trim($requirement) . "\n";
    }
    echo "\n";
} 