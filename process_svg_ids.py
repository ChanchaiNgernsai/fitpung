
import xml.etree.ElementTree as ET
import re

def add_ids_to_subgroups():
    input_path = 'public/images/fitpung_interactive.svg'
    output_path = 'public/images/fitpung_interactive.svg'
    
    ET.register_namespace('', "http://www.w3.org/2000/svg")
    try:
        tree = ET.parse(input_path)
    except ET.ParseError as e:
        print(f"XML Parse Error: {e}")
        return

    # Iterate over Layer groups
    for elem in tree.iter('{http://www.w3.org/2000/svg}g'):
        layer_id = elem.get('id')
        if layer_id and layer_id.startswith('Layer_'):
            count = 1
            for child in elem:
                # 1. Generate unique ID if missing
                if not child.get('id'):
                    child.set('id', f"{layer_id}_{count}")
                
                # 2. Append class instead of overwriting
                existing_class = child.get('class', '')
                if 'interactive-machine' not in existing_class:
                    new_class = f"{existing_class} interactive-machine".strip()
                    child.set('class', new_class)
                
                # 3. Data Attribute
                child.set('data-parent-layer', layer_id)
                
                count += 1

    tree.write(output_path, encoding='UTF-8', xml_declaration=True)
    print("Processed SVG IDs and Classes while preserving styles.")

if __name__ == "__main__":
    add_ids_to_subgroups()
