import { clamp, round, formatNumber } from './utils.js';

export function hexToRgb(hex) {
  var value = String(hex || '').trim().replace(/^#/, '');
  if (value.length === 3) value = value.replace(/(.)/g, '$1$1');
  if (value.length === 4) value = value.replace(/(.)/g, '$1$1');
  if (value.length === 8) value = value.slice(0, 6);
  if (!/^[0-9a-f]{6}$/i.test(value)) return null;
  return {
    r: parseInt(value.slice(0, 2), 16),
    g: parseInt(value.slice(2, 4), 16),
    b: parseInt(value.slice(4, 6), 16)
  };
}

export function parseAlphaFromHex(value) {
  var hex = String(value || '').trim().replace(/^#/, '');
  if (hex.length === 4) return parseInt(hex.charAt(3) + hex.charAt(3), 16) / 255;
  if (hex.length === 8) return parseInt(hex.slice(6, 8), 16) / 255;
  return 1;
}

export function componentToHex(value) {
  return clamp(Math.round(value), 0, 255).toString(16).padStart(2, '0');
}

export function rgbToHex(rgb) {
  if (!rgb) return '';
  return '#' + componentToHex(rgb.r) + componentToHex(rgb.g) + componentToHex(rgb.b);
}

export function alphaToHex(alpha) {
  return componentToHex(clamp((alpha == null ? 1 : alpha) * 255, 0, 255));
}

export function rgbToHsl(rgb) {
  if (!rgb) return null;
  var r = clamp(rgb.r, 0, 255) / 255;
  var g = clamp(rgb.g, 0, 255) / 255;
  var b = clamp(rgb.b, 0, 255) / 255;
  var max = Math.max(r, g, b);
  var min = Math.min(r, g, b);
  var h, s;
  var l = (max + min) / 2;
  var d = max - min;
  if (d === 0) {
    h = 0; s = 0;
  } else {
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r: h = ((g - b) / d) + (g < b ? 6 : 0); break;
      case g: h = ((b - r) / d) + 2; break;
      default: h = ((r - g) / d) + 4; break;
    }
    h = h * 60;
  }
  return { h: round(h, 1), s: round(s * 100, 1), l: round(l * 100, 1) };
}

function hueToRgb(p, q, t) {
  if (t < 0) t += 1;
  if (t > 1) t -= 1;
  if (t < 1 / 6) return p + (q - p) * 6 * t;
  if (t < 1 / 2) return q;
  if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
  return p;
}

export function hslToRgb(hsl) {
  if (!hsl) return null;
  var h = ((((hsl.h % 360) + 360) % 360) / 360);
  var s = clamp(hsl.s, 0, 100) / 100;
  var l = clamp(hsl.l, 0, 100) / 100;
  var r, g, b;
  if (s === 0) {
    r = g = b = l;
  } else {
    var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    var p = 2 * l - q;
    r = hueToRgb(p, q, h + 1 / 3);
    g = hueToRgb(p, q, h);
    b = hueToRgb(p, q, h - 1 / 3);
  }
  return { r: Math.round(r * 255), g: Math.round(g * 255), b: Math.round(b * 255) };
}

export function parseRgbValue(value) {
  var match = String(value || '').trim().match(/^rgba?\s*\(\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)(?:\s*,\s*([+-]?\d*(?:\.\d+)?))?\s*\)$/i);
  if (!match) return null;
  return {
    r: clamp(parseFloat(match[1]), 0, 255),
    g: clamp(parseFloat(match[2]), 0, 255),
    b: clamp(parseFloat(match[3]), 0, 255),
    a: match[4] === undefined || match[4] === '' ? 1 : clamp(parseFloat(match[4]), 0, 1)
  };
}

export function parseHslValue(value) {
  var match = String(value || '').trim().match(/^hsla?\s*\(\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)%\s*,\s*([+-]?\d+(?:\.\d+)?)%(?:\s*,\s*([+-]?\d*(?:\.\d+)?))?\s*\)$/i);
  if (!match) return null;
  return {
    h: parseFloat(match[1]),
    s: clamp(parseFloat(match[2]), 0, 100),
    l: clamp(parseFloat(match[3]), 0, 100),
    a: match[4] === undefined || match[4] === '' ? 1 : clamp(parseFloat(match[4]), 0, 1)
  };
}

export function parseHexValue(value) {
  var match = String(value || '').trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i);
  if (!match) return null;
  var rgb = hexToRgb(match[1]);
  if (!rgb) return null;
  rgb.a = parseAlphaFromHex(match[1]);
  return rgb;
}

export function parseDisplayColor(value, format) {
  if (format === 'rgb' || format === 'rgba') return parseRgbValue(value);
  if (format === 'hsl' || format === 'hsla') {
    var hsl = parseHslValue(value);
    if (!hsl) return null;
    var rgb = hslToRgb(hsl);
    rgb.a = hsl.a == null ? 1 : hsl.a;
    return rgb;
  }
  return parseHexValue(value);
}

export function detectStoredFormat(value) {
  var normalized = String(value || '').trim().toLowerCase();
  if (/^#[0-9a-f]{8}$/.test(normalized)) return 'hexa';
  if (/^#[0-9a-f]{6}$/.test(normalized)) return 'hex';
  if (/^rgba\(/.test(normalized)) return 'rgba';
  if (/^rgb\(/.test(normalized)) return 'rgb';
  if (/^hsla\(/.test(normalized)) return 'hsla';
  if (/^hsl\(/.test(normalized)) return 'hsl';
  return 'hex';
}

export function formatColorValue(hex, format) {
  var parsed = null;
  if (hex && typeof hex === 'object') {
    parsed = { r: hex.r, g: hex.g, b: hex.b, a: hex.a == null ? 1 : hex.a };
  } else {
    parsed = parseHexValue(hex) || parseRgbValue(hex);
    if (!parsed && (/^hsl/i).test(String(hex || '').trim())) {
      parsed = parseDisplayColor(hex, 'hsla');
    }
  }
  var rgb = parsed;
  if (!rgb) return '';
  var alpha = rgb.a == null ? 1 : clamp(rgb.a, 0, 1);
  if (format === 'rgb') return 'rgb(' + formatNumber(rgb.r, 0) + ', ' + formatNumber(rgb.g, 0) + ', ' + formatNumber(rgb.b, 0) + ')';
  if (format === 'rgba') return 'rgba(' + formatNumber(rgb.r, 0) + ', ' + formatNumber(rgb.g, 0) + ', ' + formatNumber(rgb.b, 0) + ', ' + formatNumber(alpha, 3) + ')';
  if (format === 'hsl') { var hsl = rgbToHsl(rgb); return 'hsl(' + formatNumber(hsl.h, 1) + ', ' + formatNumber(hsl.s, 1) + '%, ' + formatNumber(hsl.l, 1) + '%)'; }
  if (format === 'hsla') { var hsla = rgbToHsl(rgb); return 'hsla(' + formatNumber(hsla.h, 1) + ', ' + formatNumber(hsla.s, 1) + '%, ' + formatNumber(hsla.l, 1) + '%, ' + formatNumber(alpha, 3) + ')'; }
  if (format === 'hexa') return rgbToHex(rgb).toUpperCase() + alphaToHex(alpha).toUpperCase();
  return rgbToHex(rgb).toUpperCase();
}

export function mixRgb(a, b, amount) {
  return {
    r: Math.round(a.r + (b.r - a.r) * amount),
    g: Math.round(a.g + (b.g - a.g) * amount),
    b: Math.round(a.b + (b.b - a.b) * amount),
    a: a.a == null ? 1 : a.a
  };
}

export function colorGeneratorItems(baseRgb, type, count) {
  var items = [];
  var target = type === 'tints'
    ? { r: 255, g: 255, b: 255, a: baseRgb.a == null ? 1 : baseRgb.a }
    : { r: 0, g: 0, b: 0, a: baseRgb.a == null ? 1 : baseRgb.a };
  var safeCount = clamp(parseInt(count, 10) || 6, 4, 10);
  for (var i = 1; i <= safeCount; i++) {
    var rgb = mixRgb(baseRgb, target, i / (safeCount + 1));
    items.push({ label: (type === 'tints' ? 'tint-' : 'shade-') + i, value: rgbToHex(rgb).toUpperCase() });
  }
  return items;
}
