import type { CmsEditorSaveState } from './CmsEditContext';

export const AUTOSAVE_DELAY_MS = 15_000;
export const MAX_RECOVERY_LENGTH = 1_000_000;
export const RECOVERY_MAX_AGE_MS = 24 * 60 * 60 * 1000;
export type RecoveryCopy = { schema: 1; pageId: number; editorId: number; lockVersion: number; updatedAt: number; sections: any[] };

export const recoveryKey = (editorId: number, pageId: number) => `cms:recovery:v1:${editorId}:${pageId}`;
export const autosaveKey = (editorId: number) => `cms:autosave:v1:${editorId}`;
export const sectionsEqual = (a: any[], b: any[]) => JSON.stringify(a.map(({ id: _id, ...section }) => section)) === JSON.stringify(b.map(({ id: _id, ...section }) => section));
export const alignSectionIds = (sections: any[], serverSections: any[]) => sections.map(section => ({ ...section, id: serverSections.find(item => item.section_key === section.section_key)?.id ?? null }));

const record = (value: unknown): value is Record<string, any> => Boolean(value && typeof value === 'object' && !Array.isArray(value));
const componentTypes = new Set(['hero', 'text', 'image', 'image_text', 'cards', 'services', 'cta', 'publications', 'career_timeline', 'achievements', 'faq', 'gallery', 'stats', 'contact', 'appointment', 'video', 'divider', 'spacer']);
function validContent(content: Record<string, any>): boolean {
  return Object.entries(content).every(([key, value]) => {
    if (key === 'items') return Array.isArray(value) && value.every(item => record(item) && Object.values(item).every(field => field === null || typeof field === 'string' || typeof field === 'boolean'));
    if (key.endsWith('_marks')) return Array.isArray(value) && value.every(mark => record(mark) && ['bold', 'italic', 'underline', 'link'].includes(mark.type) && Number.isInteger(mark.start) && Number.isInteger(mark.end) && mark.start >= 0 && mark.end >= mark.start && (mark.url === undefined || typeof mark.url === 'string'));
    return value === null || typeof value === 'string' || typeof value === 'boolean';
  });
}
function safeTree(value: unknown, depth = 0): boolean {
  if (depth > 15) return false;
  if (value === null || ['string', 'number', 'boolean'].includes(typeof value)) return true;
  if (Array.isArray(value)) return value.length <= 500 && value.every(item => safeTree(item, depth + 1));
  return record(value) && Object.entries(value).every(([key, child]) => !['__proto__', 'constructor', 'prototype'].includes(key) && safeTree(child, depth + 1));
}

export function readRecovery(editorId: number, pageId: number): RecoveryCopy | null {
  try {
    const key = recoveryKey(editorId, pageId);
    const reject = () => { sessionStorage.removeItem(key); return null; };
    const raw = sessionStorage.getItem(key);
    if (!raw) return null;
    if (raw.length > MAX_RECOVERY_LENGTH) return reject();
    const copy = JSON.parse(raw);
    if (copy.schema !== 1 || copy.editorId !== editorId || copy.pageId !== pageId || !Number.isInteger(copy.lockVersion) || copy.lockVersion < 0 || !Number.isFinite(copy.updatedAt) || copy.updatedAt > Date.now() || Date.now() - copy.updatedAt > RECOVERY_MAX_AGE_MS || !Array.isArray(copy.sections) || copy.sections.length > 100 || !safeTree(copy.sections)) return reject();
    if (!copy.sections.every((section: any) => record(section) && typeof section.section_key === 'string' && componentTypes.has(section.type) && record(section.content) && validContent(section.content) && record(section.presentation))) return reject();
    return copy;
  } catch { return null; }
}

export function writeRecovery(copy: RecoveryCopy): boolean {
  try {
    const value = JSON.stringify(copy);
    if (value.length > MAX_RECOVERY_LENGTH) return false;
    sessionStorage.setItem(recoveryKey(copy.editorId, copy.pageId), value);
    return true;
  } catch { return false; }
}

export function clearRecovery(editorId: number, pageId: number): boolean {
  try { sessionStorage.removeItem(recoveryKey(editorId, pageId)); return true; } catch { return false; }
}

export function editorFailure(error: any, operation: 'save' | 'preview' | 'publish' | 'reload'): { state: CmsEditorSaveState; message: string } {
  const status = error?.response?.status;
  const retained = 'Your local edits are retained. ';
  const action = operation === 'publish' ? 'Publication could not be confirmed.' : operation === 'preview' ? 'Preview could not be opened.' : operation === 'reload' ? 'The server draft could not be loaded.' : 'The draft could not be saved.';
  if (status === 401 || status === 419) return { state: 'session-expired', message: `${action} Your editing session expired. ${retained}Sign in again in another tab using the same account, then retry.` };
  if (status === 409) return { state: 'conflict', message: `${action} Another editing session changed this page. ${retained}Load the server draft to review it before choosing whether to restore your local copy.` };
  if (status === 403) return { state: 'failed', message: `${action} This account no longer has permission to edit this page. ${retained}Ask a Power Admin to check your access.` };
  if (status === 422) return { state: 'failed', message: `${action} Some page content failed validation. ${retained}${Object.values(error?.response?.data?.errors || {}).flat().filter(value => typeof value === 'string').slice(0, 3).join(' ').slice(0, 600) || 'Check the selected content, required fields, links and image descriptions, then retry.'}` };
  if (status === 429) return { state: 'failed', message: `${action} Too many requests were sent. ${retained}Wait a moment, then retry manually.` };
  if (!error?.response) return { state: 'failed', message: `${action} The connection was interrupted. ${retained}Check your connection, then retry. A lost response may mean the server already received the request.` };
  return { state: 'failed', message: `${action} The server could not confirm the request. ${retained}Try again shortly; if a conflict appears, review the server draft first.` };
}
