#!/usr/bin/env node

/**
 * Copy JavaScript and CSS assets:
 * 1. From node_modules to public/assets/js and public/assets/css
 * 2. From app/client/src/js to public/assets/js
 * This script automatically runs after npm install via postinstall hook
 */

const fs = require("fs");
const path = require("path");

// Configuration: Add new files here as you install packages
const ASSETS_TO_COPY = {
  js: [
    { from: "jquery/dist/jquery.min.js", to: "jquery.min.js" },
    { from: "preline/dist/preline.js", to: "preline.js" },
    { from: "datatables.net/js/dataTables.min.js", to: "dataTables.min.js" },
    {
      from: "datatables.net-dt/js/dataTables.dataTables.min.js",
      to: "dataTables.dataTables.min.js",
    },
    { from: "chart.js/dist/chart.umd.js", to: "chart.umd.js" },
  ],
  css: [
    {
      from: "datatables.net-dt/css/dataTables.dataTables.min.css",
      to: "dataTables.dataTables.min.css",
    },
  ],
};

// Apps to copy JS files from
const NODE_MODULES = path.join(__dirname, "..", "node_modules");
const PUBLIC_JS = path.join(__dirname, "..", "public", "assets", "js");
const PUBLIC_CSS = path.join(__dirname, "..", "public", "assets", "css");

function copyFile(src, dest) {
  const srcPath = path.join(NODE_MODULES, src);
  const destPath = path.join(dest, path.basename(src));

  if (!fs.existsSync(srcPath)) {
    console.warn(`⚠️  Warning: Source file not found: ${srcPath}`);
    return false;
  }

  try {
    // Ensure destination directory exists
    fs.mkdirSync(dest, { recursive: true });

    // Copy file
    fs.copyFileSync(srcPath, destPath);
    console.log(`✓ Copied: ${src} → ${path.relative(process.cwd(), destPath)}`);
    return true;
  } catch (error) {
    console.error(`✗ Error copying ${src}:`, error.message);
    return false;
  }
}

function copyAppJsFiles() {
  console.log("📦 Copying JS files...\n");

  let appJsCount = 0;

  const srcDir = path.join("client", "src", "js");
  const destDir = path.join(__dirname, "..", "public", "assets", "js");

  if (!fs.existsSync(srcDir)) {
    // Doesn't have a client/src/js directory, skip silently
    return;
  }

  try {
    // Ensure destination directory exists
    fs.mkdirSync(destDir, { recursive: true });

    // Read all JS files from source directory
    const files = fs
      .readdirSync(srcDir)
      .filter((file) => file.endsWith(".js"));

    if (files.length === 0) {
      return;
    }

    files.forEach((file) => {
      const srcPath = path.join(srcDir, file);
      const destPath = path.join(destDir, file);

      try {
        fs.copyFileSync(srcPath, destPath);
        console.log(
          `✓ Copied: app//client/src/js/${file} → public//assets/js/${file}`
        );
        appJsCount++;
      } catch (error) {
        console.error(`✗ Error copying ${file}:`, error.message);
      }
    });
  } catch (error) {
    console.error(`✗ Error reading app/client/src/js:`, error.message);
  }

  return appJsCount;
}

function copyAssets() {
  console.log("📦 Copying assets from node_modules...\n");

  let jsCount = 0;
  let cssCount = 0;

  // Copy JavaScript files
  ASSETS_TO_COPY.js.forEach(({ from, to }) => {
    if (copyFile(from, PUBLIC_JS)) {
      jsCount++;
    }
  });

  // Copy CSS files
  ASSETS_TO_COPY.css.forEach(({ from, to }) => {
    if (copyFile(from, PUBLIC_CSS)) {
      cssCount++;
    }
  });

  console.log(
    `\n✅ Node modules: ${jsCount} JS file(s), ${cssCount} CSS file(s) copied`
  );

  // Copy app-specific JS files
  const appJsCount = copyAppJsFiles();

  if (appJsCount > 0) {
    console.log(`\n✅ App JS files: ${appJsCount} file(s) copied`);
  }

  const totalCount = jsCount + cssCount + appJsCount;

  if (totalCount === 0) {
    console.warn(
      "⚠️  No files were copied. Make sure node_modules exists and run: npm install"
    );
    process.exit(1);
  }
}

// Run the script
copyAssets();
