#!/bin/bash
# Convert jQuery to vanilla JS across all JavaScript files
# This script applies common jQuery pattern replacements

FILES=$(find /workspaces/vichan/js -name "*.js" -not -path "*/jquery*" -not -path "*min.js" -not -name "dom-utils.js")

for file in $FILES; do
    echo "Processing: $file"
    
    # Convert $(document).ready() to onReady()
    sed -i "s/\$(document)\.ready(function()\s*{/onReady(() => {/g" "$file"
    sed -i "s/\$(window)\.ready(function()\s*{/onReady(() => {/g" "$file"
    sed -i "s/\$(document)\.ready(function(\s*)\s*{/onReady(() => {/g" "$file"
    sed -i "s/\$(window)\.ready(function(\s*)\s*{/onReady(() => {/g" "$file"
    
    # Convert == to === and != to !==
    sed -i "s/ == / === /g" "$file"
    sed -i "s/ != / !== /g" "$file"
    sed -i "s/!=/\!=/g" "$file"
    
    # Convert var to const (be careful with this one)
    sed -i "s/^\s*var /const /g" "$file"
    
done

echo "Basic conversion complete. Manual review and fixes may be needed."
