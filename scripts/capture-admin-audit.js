#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const { chromium } = require('@playwright/test');
const {
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
} = require('../tests/ui/helpers/ecf-admin');

const outDir = path.resolve(__dirname, '..', 'tmp', 'admin-audit');

const panelCaptures = [
  { name: 'components-website', type: 'general', tab: 'website' },
  { name: 'components-interface', type: 'general', tab: 'interface' },
  { name: 'components-system', type: 'general', tab: 'system' },
  { name: 'components-favorites', type: 'general', tab: 'favorites' },
  { name: 'tokens', type: 'panel', panel: 'tokens' },
  { name: 'typography', type: 'panel', panel: 'typography' },
  { name: 'spacing', type: 'panel', panel: 'spacing' },
  { name: 'shadows', type: 'panel', panel: 'shadows' },
  { name: 'variables', type: 'panel', panel: 'variables' },
  { name: 'utilities', type: 'panel', panel: 'utilities' },
  { name: 'sync', type: 'panel', panel: 'sync' },
  { name: 'help', type: 'panel', panel: 'help' },
];

async function capture(page, name) {
  await page.locator('.ecf-wrap').first().screenshot({
    path: path.join(outDir, `${name}.png`),
  });
}

async function main() {
  fs.mkdirSync(outDir, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1600, height: 1200 } });

  try {
    await loginToWordPress(page);
    await openPluginPage(page);

    for (const item of panelCaptures) {
      if (item.type === 'general') {
        await openGeneralTab(page, item.tab);
      } else {
        await openPanel(page, item.panel);
      }
      await page.waitForTimeout(250);
      await capture(page, item.name);
      console.log(item.name);
    }
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error && error.stack ? error.stack : String(error));
  process.exit(1);
});
