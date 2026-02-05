#!/usr/bin/env python3
"""
jQuery to Vanilla JS Converter
Converts jQuery code to modern vanilla JavaScript
"""

import os
import re
import glob

def convert_jquery_to_vanilla(content):
    """Apply comprehensive jQuery to vanilla JS conversions"""
    
    # Replace $(document).ready() - multiple variations
    content = re.sub(
        r'\$\s*\(\s*document\s*\)\s*\.ready\s*\(\s*function\s*\(\s*\)\s*{',
        'onReady(() => {',
        content
    )
    content = re.sub(
        r'\$\s*\(\s*window\s*\)\s*\.ready\s*\(\s*function\s*\(\s*\)\s*{',
        'onReady(() => {',
        content
    )
    content = re.sub(
        r'\$\(document\)\.ready\(function\(\)',
        'onReady(() =>',
        content
    )
    
    # Convert function(){ to () => {
    content = re.sub(r'function\s*\(\s*\)\s*{', '() => {', content)
    
    # Convert function(param){ to (param) => {
    content = re.sub(r'function\s*\(\s*(\w+)\s*\)\s*{', r'(\1) => {', content)
    
    # Replace $. patterns (.ajax, .get, .post, etc)
    content = re.sub(r'\$\.ajax\s*\(', 'fetch(', content)
    content = re.sub(r'\$\.get\s*\(', 'fetch(', content) 
    content = re.sub(r'\$\.post\s*\(', 'fetch(', content)
    
    # Replace $(selector) with document.querySelector
    # But be careful with chaining
    def replace_selector(match):
        selector = match.group(1)
        # If ID selector (single purpose), use getElementById
        if selector.startswith('#') and '#' not in selector[1:]:
            var_name = selector[1:]
            return f"document.getElementById('{var_name}')"
        # Otherwise use querySelector
        return f"document.querySelector('{selector}')"
    
    content = re.sub(r'\$\s*\(\s*[\'"]([#.][\w-]+)[\'"]\s*\)', replace_selector, content)
    
    # Replace $()all queries
    content = re.sub(r'\$\s*\(\s*[\'"]([^"\']+)[\'"]\s*\)', 
                     lambda m: f"document.querySelectorAll('{m.group(1)}')" if m.group(1).startswith('.') 
                     else f"document.querySelector('{m.group(1)}')", content)
    
    # Replace jQuery method chains with vanilla JS
    # .click(function) -> .addEventListener('click', function)
    content = re.sub(
        r'\.click\s*\(\s*function\s*\(\s*\)\s*{',
        ".addEventListener('click', () => {",
        content
    )
    
    # .on('event', fn) -> .addEventListener('event', fn)
    content = re.sub(
        r'\.on\s*\(\s*[\'"](\w+)[\'"]\s*,\s*function',
        r".addEventListener('\1', function",
        content
    )
    
    # .off('event') -> .removeEventListener('event')
    content = re.sub(
        r'\.off\s*\(\s*[\'"](\w+)[\'"]\s*\)',
        r".removeEventListener('\1')",
        content
    )
    
    # .addClass('class') -> .classList.add('class')
    content = re.sub(
        r'\.addClass\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)',
        r".classList.add('\1')",
        content
    )
    
    # .removeClass('class') -> .classList.remove('class')
    content = re.sub(
        r'\.removeClass\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)',
        r".classList.remove('\1')",
        content
    )
    
    # .hasClass('class') -> .classList.contains('class')
    content = re.sub(
        r'\.hasClass\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)',
        r".classList.contains('\1')",
        content
    )
    
    # .toggleClass('class') -> .classList.toggle('class')
    content = re.sub(
        r'\.toggleClass\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)',
        r".classList.toggle('\1')",
        content
    )
    
    # .attr('name', 'value') -> .setAttribute('name', 'value')
    content = re.sub(
        r'\.attr\s*\(\s*[\'"](\w+)[\'"]\s*,\s*',
        r".setAttribute('\1', ",
        content
    )
    
    # .attr('name') -> .getAttribute('name')
    content = re.sub(
        r'\.attr\s*\(\s*[\'"](\w+)[\'"]\s*\)',
        r".getAttribute('\1')",
        content
    )
    
    # .removeAttr('name') -> .removeAttribute('name')
    content = re.sub(
        r'\.removeAttr\s*\(\s*[\'"](\w+)[\'"]\s*\)',
        r".removeAttribute('\1')",
        content
    )
    
    # .text('text') -> .textContent = 'text'
    content = re.sub(
        r'\.text\s*\(\s*[\'"]([^\'"]*)[\'"]\ s*\)',
        r".textContent = '\1'",
        content
    )
    
    # .text() -> .textContent
    content = re.sub(r'\.text\s*\(\s*\)', '.textContent', content)
    
    # .html('html') -> .innerHTML = 'html'
    content = re.sub(
        r'\.html\s*\(\s*[\'"]([^\'"]*)[\'"]\ s*\)',
        r".innerHTML = '\1'",
        content
    )
    
    # .html() -> .innerHTML
    content = re.sub(r'\.html\s*\(\s*\)', '.innerHTML', content)
    
    # .val('value') -> .value = 'value'
    content = re.sub(
        r'\.val\s*\(\s*[\'"]([^\'"]*)[\'"]\ s*\)',
        r".value = '\1'",
        content
    )
    
    # .val() -> .value
    content = re.sub(r'\.val\s*\(\s*\)', '.value', content)
    
    # .find('selector') -> .querySelector('selector')
    content = re.sub(
        r'\.find\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)',
        r".querySelector('\1')",
        content
    )
    
    # .closest('selector') -> .closest('selector') (already correct)
    # Just ensure it works without $()
    
    # .append(element) -> .appendChild(element)
    content = re.sub(r'\.append\s*\(', '.appendChild(', content)
    
    # .prepend(element) -> .insertBefore(element, firstChild)
    content = re.sub(
        r'\.prepend\s*\(',
        '.insertBefore(',
        content
    )
    
    # .remove() -> .remove() (already correct)
    # .empty() -> .innerHTML = ''
    content = re.sub(r'\.empty\s*\(\)', ".innerHTML = ''", content)
    
    # .css('prop', 'value') -> .style.prop = 'value'
    content = re.sub(
        r'\.css\s*\(\s*[\'"](\w+)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"]\ s*\)',
        r'.style.\1 = "\2"',
        content
    )
    
    # .css('prop') -> window.getComputedStyle().prop
    content = re.sub(
        r'\.css\s*\(\s*[\'"](\w+)[\'"]\s*\)',
        r'.style.\1',
        content
    )
    
    # .each(function) -> .forEach(function)
    content = re.sub(
        r'\.each\s*\(function\(\s*\w+\s*\)',
        r'.forEach(function',
        content
    )
    
    # .show() -> .style.display = ''
    content = re.sub(r'\.show\s*\(\)', ".style.display = ''", content)
    
    # .hide() -> .style.display = 'none'
    content = re.sub(r'\.hide\s*\(\)', ".style.display = 'none'", content)
    
    # Replace == with === and != with !==
    content = re.sub(r'([^=!<>])==([^=])', r'\1===\2', content)
    content = re.sub(r'!==([^=])', r'!==\1', content)
    
    # Replace var with const (basic - be careful)
    content = re.sub(r'^(\s*)var\s+', r'\1const ', content, flags=re.MULTILINE)
    
    return content

def process_files():
    """Process all JavaScript files in js/ and subdirectories"""
    js_dir = '/workspaces/vichan/js'
    
    # Get all .js files recursively
    import os
    files = []
    for root, dirs, filenames in os.walk(js_dir):
        for filename in filenames:
            if filename.endswith('.js'):
                filepath = os.path.join(root, filename)
                files.append(filepath)
    
    skip_patterns = ['jquery', 'min.js', 'dom-utils.js']
    
    processed = 0
    errors = []
    
    for filepath in sorted(files):
        filename = os.path.basename(filepath)
        
        # Skip certain files
        if any(skip in filename for skip in skip_patterns):
            continue
        
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Skip if already converted (has onReady or no jQuery)
            if 'onReady' in content or ('$(' not in content and '$.ajax' not in content):
                print(f'✓ SKIP: {filename} (already modern)')
                continue
            
            # Convert
            converted = convert_jquery_to_vanilla(content)
            
            # Write back
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(converted)
            
            print(f'✓ CONVERTED: {filename}')
            processed += 1
            
        except Exception as e:
            errors.append((filename, str(e)))
            print(f'✗ ERROR: {filename} - {str(e)}')
    
    print(f'\n=== Summary ===')
    print(f'Processed: {processed} files')
    if errors:
        print(f'Errors: {len(errors)}')
        for filename, error in errors:
            print(f'  - {filename}: {error}')
    
    return processed, errors

if __name__ == '__main__':
    process_files()
