
const fs = require('fs');
const { DOMParser, XMLSerializer } = require('xmldom');
const svgPathBbox = require('svg-path-bounding-box');

const EQUIPMENT_MAP = {
    "Layer_4": "Treadmill",
    "Layer_5": "DeclineBenchPress",
    "Layer_6": "BenchPress",
    "Layer_7": "SmithMachine",
    "Layer_8": "LegPress",
    "Layer_9": "Elliptical",
    "Layer_10": "LegPress"
};

const INPUT_FILE = 'public/images/fitpung.svg'; // Using original source
const OUTPUT_DIR = 'public/images/equipment/';

if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

const svgContent = fs.readFileSync(INPUT_FILE, 'utf8');
const doc = new DOMParser().parseFromString(svgContent, 'image/svg+xml');

const defs = doc.getElementsByTagName('defs')[0];
const defsString = defs ? new XMLSerializer().serializeToString(defs) : '';

function shapeToPath(node) {
    const type = node.tagName;
    if (type === 'path') return node.getAttribute('d') || '';
    if (type === 'rect') {
        const x = parseFloat(node.getAttribute('x') || 0);
        const y = parseFloat(node.getAttribute('y') || 0);
        const w = parseFloat(node.getAttribute('width') || 0);
        const h = parseFloat(node.getAttribute('height') || 0);
        return `M ${x} ${y} H ${x + w} V ${y + h} H ${x} Z`;
    }
    if (type === 'circle') {
        const cx = parseFloat(node.getAttribute('cx') || 0);
        const cy = parseFloat(node.getAttribute('cy') || 0);
        const r = parseFloat(node.getAttribute('r') || 0);
        return `M ${cx - r}, ${cy} a ${r},${r} 0 1,0 ${r * 2},0 a ${r},${r} 0 1,0 -${r * 2},0`;
    }
    if (type === 'line') {
        const x1 = node.getAttribute('x1');
        const y1 = node.getAttribute('y1');
        const x2 = node.getAttribute('x2');
        const y2 = node.getAttribute('y2');
        return `M ${x1} ${y1} L ${x2} ${y2}`;
    }
    if (type === 'polyline' || type === 'polygon') {
        const points = node.getAttribute('points');
        if (!points) return '';
        const pts = points.trim().split(/\s+|,/);
        let d = '';
        for (let i = 0; i < pts.length; i += 2) {
            const x = pts[i];
            const y = pts[i + 1];
            if (!x || !y) continue;
            d += (i === 0 ? 'M' : 'L') + ` ${x} ${y} `;
        }
        if (type === 'polygon') d += 'Z';
        return d;
    }
    return '';
}

function processLayer(layerId, name) {
    const layer = doc.getElementById(layerId);
    if (!layer) {
        console.log(`Layer ${layerId} not found.`);
        return;
    }

    // TARGET: Get the FIRST child element (representing a single machine)
    // Filter for elements only (nodeType 1)
    let singleItemNode = null;
    if (layer.childNodes) {
        for (let i = 0; i < layer.childNodes.length; i++) {
            const child = layer.childNodes[i];
            // We want the first <g> or shape that represents an item
            // Typically layers in AI/Inkscape group items in <g>. 
            if (child.nodeType === 1 && child.tagName === 'g') {
                singleItemNode = child;
                break;
            }
        }
    }

    // Fallback: if no subgroups, maybe the layer IS the single item
    if (!singleItemNode) {
        singleItemNode = layer;
    }

    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    let pathsFound = false;

    function traverse(node) {
        if (node.nodeType !== 1) return;

        const pathData = shapeToPath(node);
        if (pathData) {
            try {
                const bbox = svgPathBbox(pathData);
                if (!isNaN(bbox.minX)) {
                    if (bbox.minX < minX) minX = bbox.minX;
                    if (bbox.minY < minY) minY = bbox.minY;
                    if (bbox.maxX > maxX) maxX = bbox.maxX;
                    if (bbox.maxY > maxY) maxY = bbox.maxY;
                    pathsFound = true;
                }
            } catch (e) {
                // ignore complex path errors
            }
        }

        if (node.childNodes) {
            for (let i = 0; i < node.childNodes.length; i++) {
                traverse(node.childNodes[i]);
            }
        }
    }

    // Reset bbox search to just the single item
    traverse(singleItemNode);

    if (!pathsFound) {
        console.log(`No paths found for ${name}`);
        return;
    }

    // padding
    const padding = 5;
    minX -= padding;
    minY -= padding;
    maxX += padding;
    maxY += padding;
    const width = maxX - minX;
    const height = maxY - minY;

    if (width <= 0 || height <= 0) return;

    // Serialize just that single node
    const nodeXml = new XMLSerializer().serializeToString(singleItemNode);

    const newSvgContent = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="${minX} ${minY} ${width} ${height}">
  ${defsString}
  ${nodeXml}
</svg>`;

    const fileName = `${OUTPUT_DIR}${name}.svg`;
    fs.writeFileSync(fileName, newSvgContent);
    console.log(`Saved SINGLE ${name} to ${fileName} (ViewBox: ${minX.toFixed(1)} ${minY.toFixed(1)} ${width.toFixed(1)} ${height.toFixed(1)})`);
}

for (const [id, name] of Object.entries(EQUIPMENT_MAP)) {
    processLayer(id, name);
}
