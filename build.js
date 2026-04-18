#!/usr/bin/env node
/**
 * Layrix Admin JS Build
 * Usage: node build.js          — development build
 *        node build.js --watch  — watch mode
 *        node build.js --prod   — production (minified)
 */
const esbuild = require('esbuild');
const path    = require('path');

const isProd  = process.argv.includes('--prod');
const isWatch = process.argv.includes('--watch');

const config = {
  entryPoints: ['src/js/index.js'],
  outfile:     'assets/admin.js',
  bundle:      true,
  platform:    'browser',
  target:      ['es2018'],
  minify:      isProd,
  sourcemap:   !isProd,
  // jQuery and ecfAdmin are loaded externally by WordPress — don't bundle them
  external:    ['jquery'],
  banner: {
    js: '/* Layrix Admin UI — built by esbuild, do not edit assets/admin.js directly */',
  },
};

if (isWatch) {
  esbuild.context(config).then(ctx => {
    ctx.watch();
    console.log('Watching src/js/ for changes …');
  });
} else {
  esbuild.build(config)
    .then(() => console.log(`Built assets/admin.js ${isProd ? '(minified)' : '(dev)'}`))
    .catch(() => process.exit(1));
}
