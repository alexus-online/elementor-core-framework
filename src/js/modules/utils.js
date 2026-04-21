export function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

export function round(value, precision) {
  var factor = Math.pow(10, precision || 0);
  return Math.round(value * factor) / factor;
}

export function formatNumber(value, precision) {
  var rounded = round(value, precision || 0);
  return String(rounded).replace(/\.0+$|(\.\d*[1-9])0+$/, '$1');
}

export function formatPreviewNumber(value) {
  var rounded = Math.round(value * 100) / 100;
  return String(rounded).replace(/\.0+$|(\.\d*[1-9])0+$/, '$1');
}

export function escapeHtml(value) {
  var d = document.createElement('div');
  d.appendChild(document.createTextNode(value == null ? '' : String(value)));
  return d.innerHTML;
}

export function formatTemplate(template, value) {
  return String(template || '').replace('%s', value == null ? '' : String(value));
}

export function formatFileSize(bytes) {
  var size = Number(bytes || 0);
  if (!size) return '0 B';
  if (size < 1024) return size + ' B';
  if (size < 1024 * 1024) return formatNumber(size / 1024, 1) + ' KB';
  return formatNumber(size / (1024 * 1024), 1) + ' MB';
}

export function parseCssSizeParts(value) {
  var normalized = String(value == null ? '' : value).trim();
  var match = normalized.match(/^(-?\d+(?:[.,]\d+)?)(px|rem|em|ch|%|vw|vh)$/i);
  if (match) {
    return { value: String(match[1]).replace(',', '.'), format: String(match[2]).toLowerCase() };
  }
  return { value: normalized, format: 'custom' };
}

export function normalizeSizeRange(minPx, maxPx) {
  var normalizedMin = parseFloat(minPx);
  var normalizedMax = parseFloat(maxPx);
  if (!isFinite(normalizedMin) && !isFinite(normalizedMax)) return null;
  if (!isFinite(normalizedMin)) normalizedMin = normalizedMax;
  if (!isFinite(normalizedMax)) normalizedMax = normalizedMin;
  if (normalizedMin > normalizedMax) {
    var swap = normalizedMin; normalizedMin = normalizedMax; normalizedMax = swap;
  }
  return { minPx: normalizedMin, maxPx: normalizedMax };
}
