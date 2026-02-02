
import re
import os

def extract_equipment():
    input_path = 'public/images/fitpung_interactive.svg'
    
    with open(input_path, 'r') as f:
        full_content = f.read()

    # Create output directory
    os.makedirs('public/images/equipment', exist_ok=True)
    
    # Extract Definitions (defs and style)
    defs_match = re.search(r'<defs>.*?</defs>', full_content, re.DOTALL)
    defs_content = defs_match.group(0) if defs_match else "<defs></defs>"

    # Base headers (1920x1080 for equipment as requested previously)
    equipment_header = f'''<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1920 1080">
  {defs_content}
'''
    base_svg_footer = '</svg>'
    
    # Mapping
    equipment_map = {
        "Layer_4": "Treadmill",
        "Layer_5": "DeclineBenchPress",
        "Layer_6": "BenchPress",
        "Layer_7": "SmithMachine",
        "Layer_8": "LegPress",
        "Layer_9": "Elliptical",
        "Layer_10": "LegPress"
    }

    # Regex to find layers
    # We use a pattern that captures the ID
    layer_pattern = re.compile(r'<g id="(Layer_\d+)"')
    matches = list(layer_pattern.finditer(full_content))
    
    end_of_svg_index = full_content.rfind('</svg>')
    matches.append(None) 
    
    for i in range(len(matches)-1):
        match = matches[i]
        next_match = matches[i+1]
        
        start = match.start()
        end = next_match.start() if next_match else end_of_svg_index
        
        layer_content = full_content[start:end]
        layer_id = match.group(1)
        
        # We skip Layer_3 as it is likely the background/map itself
        if layer_id == "Layer_3":
            print(f"Skipping {layer_id} (Background Map)")
            continue
            
        # Determine filename
        if layer_id in equipment_map:
            name = equipment_map[layer_id]
        else:
            name = f"Equipment_{layer_id}"
            
        filename = f"public/images/equipment/{name}.svg"
        
        with open(filename, 'w') as out:
            out.write(equipment_header + layer_content + base_svg_footer)
        
        print(f"Extracted {name} to {filename}")

if __name__ == "__main__":
    extract_equipment()
