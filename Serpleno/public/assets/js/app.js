import { renderLayout } from './lib/layout.js';
import { enforceGuards } from './lib/session.js';

async function boot() {
  await enforceGuards();
  await renderLayout();
}

document.addEventListener('DOMContentLoaded', boot);
