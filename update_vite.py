import os
import re

# List of directory paths to walk through
directory_paths = ['resources/sass', 'resources/js', 'resources/images']

# Patterns to exclude (files that should not be in vite.config.js)
exclude_patterns = [
    r'\.bak$',          # Backup files
    r'\.tmp$',          # Temporary files
    r'\.swp$',          # Vim swap files
    r'\.map$',          # Source map files
    r'~$',              # Backup files ending with ~
    r'\.orig$',         # Original files from merges
]

# Compile patterns for efficiency
compiled_excludes = [re.compile(p, re.IGNORECASE) for p in exclude_patterns]

def should_exclude(filename):
    """Check if a file should be excluded based on patterns"""
    for pattern in compiled_excludes:
        if pattern.search(filename):
            return True
    return False

def find_scss_imports(file_path):
    """Find all @import and @use statements in a SCSS file"""
    imports = set()
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Match @import 'path' or @import "path" or @use 'path' or @use "path"
        pattern = r'@(?:import|use)\s+[\'"]([^\'"]+)[\'"]'
        matches = re.findall(pattern, content)
        
        base_dir = os.path.dirname(file_path)
        
        for match in matches:
            # Remove quotes and handle the path
            import_path = match.strip()
            
            # Skip external packages (like bootstrap)
            if import_path.startswith('bootstrap') or import_path.startswith('~'):
                continue
            
            # Resolve relative paths
            if not import_path.startswith('/'):
                import_path = os.path.join(base_dir, import_path)
            
            # Normalize the path
            import_path = os.path.normpath(import_path).replace('\\', '/')
            
            # Check possible file variations
            possible_files = [
                import_path + '.scss',
                import_path + '.css',
                import_path,
                # Handle partials (files starting with _)
                os.path.join(os.path.dirname(import_path), '_' + os.path.basename(import_path) + '.scss'),
                os.path.join(os.path.dirname(import_path), '_' + os.path.basename(import_path)),
            ]
            
            for pf in possible_files:
                pf_normalized = pf.replace('\\', '/')
                if os.path.exists(pf_normalized):
                    imports.add(pf_normalized)
                    break
    except Exception as e:
        print(f"Warning: Could not parse {file_path}: {e}")
    
    return imports

def get_all_imported_files(scss_files):
    """Get all files that are imported by other SCSS files"""
    all_imports = set()
    for scss_file in scss_files:
        imports = find_scss_imports(scss_file)
        all_imports.update(imports)
    return all_imports

# First pass: collect all files
all_files = []
scss_files = []

for directory_path in directory_paths:
    for root, dirs, files in os.walk(directory_path):
        for filename in files:
            # Skip files matching exclude patterns
            if should_exclude(filename):
                continue
            file_path = os.path.join(root, filename).replace('\\', '/')
            all_files.append(file_path)
            
            if filename.endswith('.scss'):
                scss_files.append(file_path)

# Find all imported SCSS files
imported_files = get_all_imported_files(scss_files)
print(f"Found {len(imported_files)} files imported by other SCSS files")

# Filter out imported files and SCSS partials (starting with _)
input_files = []
excluded_count = 0
for file_path in all_files:
    filename = os.path.basename(file_path)
    
    # Exclude SCSS partials (they should be imported, not direct entries)
    if filename.startswith('_') and filename.endswith('.scss'):
        excluded_count += 1
        continue
    
    # Exclude files that are imported by other files
    if file_path in imported_files:
        excluded_count += 1
        continue
    
    input_files.append(file_path)

# Sort for consistent ordering
input_files.sort()

print(f"Excluded {excluded_count} files (partials or imported)")

# Prepare the input array as a JS array string
input_array_str = '[\n' + ',\n'.join(f"    '{f}'" for f in input_files) + '\n]'

# Read vite.config.js
vite_config_path = 'vite.config.js'
with open(vite_config_path, 'r', encoding='utf-8') as f:
    vite_config = f.read()

# Replace the input array in laravel({ input: [...] })
vite_config_new = re.sub(
    r'input:\s*\[[^\]]*\]',
    f'input: {input_array_str}',
    vite_config,
    count=1
)

# Write back to vite.config.js
with open(vite_config_path, 'w', encoding='utf-8') as f:
    f.write(vite_config_new)

print(f"Updated vite.config.js with {len(input_files)} files")

