import { beforeEach, expect, test } from 'vitest';
import { alignSectionIds, clearRecovery, editorFailure, MAX_RECOVERY_LENGTH, readRecovery, RECOVERY_MAX_AGE_MS, recoveryKey, sectionsEqual, writeRecovery, type RecoveryCopy } from './cmsDraftRecovery';

const copy = (): RecoveryCopy => ({ schema: 1, editorId: 4, pageId: 8, lockVersion: 2, updatedAt: Date.now(), sections: [{ id: 9, section_key: 'test', type: 'text', is_visible: true, content: { heading: 'Draft', body: 'Private copy' }, presentation: {} }] });
beforeEach(() => { sessionStorage.clear(); });

test('recovery is bounded and isolated by editor and page, and stores no account/session payload', () => {
  const draft = copy();
  expect(writeRecovery(draft)).toBe(true);
  expect(readRecovery(4, 8)).toEqual(draft);
  expect(readRecovery(5, 8)).toBeNull(); expect(readRecovery(4, 9)).toBeNull();
  expect(Object.keys(JSON.parse(sessionStorage.getItem(recoveryKey(4, 8))!)).sort()).toEqual(['editorId', 'lockVersion', 'pageId', 'schema', 'sections', 'updatedAt']);
  const oversized = copy(); oversized.sections[0].content.body = 'x'.repeat(MAX_RECOVERY_LENGTH);
  expect(writeRecovery(oversized)).toBe(false);
  expect(readRecovery(4, 8)).toEqual(draft);
  expect(clearRecovery(4, 8)).toBe(true); expect(readRecovery(4, 8)).toBeNull();
});

test('expired, wrong-identity, malformed, and renderer-invalid recovery copies are rejected', () => {
  for (const change of [
    (value: any) => { value.updatedAt = Date.now() - RECOVERY_MAX_AGE_MS - 1; },
    (value: any) => { value.editorId = 5; },
    (value: any) => { value.sections[0].content.body = { nested: 'Not text' }; },
    (value: any) => { value.sections[0].content.body_marks = 'Not marks'; },
    (value: any) => { value.sections[0].type = 'unsupported'; },
  ]) {
    const value = copy(); change(value); sessionStorage.setItem(recoveryKey(4, 8), JSON.stringify(value));
    expect(readRecovery(4, 8)).toBeNull(); expect(sessionStorage.getItem(recoveryKey(4, 8))).toBeNull();
  }
  sessionStorage.setItem(recoveryKey(4, 8), '{invalid'); expect(readRecovery(4, 8)).toBeNull();
});

test('section comparison ignores database IDs and restored sections use current owned IDs', () => {
  const sections = copy().sections;
  expect(sectionsEqual(sections, [{ ...sections[0], id: 12 }])).toBe(true);
  expect(alignSectionIds(sections, [{ ...sections[0], id: 12 }])[0].id).toBe(12);
  expect(alignSectionIds(sections, [])[0].id).toBeNull();
});

test.each([401, 419, 409, 403, 422, 429, 500])('safe error classification for HTTP %s retains local edits and avoids server internals', status => {
  const result = editorFailure({ response: { status, data: { message: 'SECRET DATABASE TRACE', errors: status === 422 ? { sections: ['Image descriptions are required.'] } : {} } } }, 'save');
  expect(result.message).toContain('Your local edits are retained');
  expect(result.message).not.toContain('SECRET DATABASE TRACE');
  expect(result.state).toBe(status === 401 || status === 419 ? 'session-expired' : status === 409 ? 'conflict' : 'failed');
  if (status === 422) expect(result.message).toContain('Image descriptions are required');
});

test('unconfirmed publication never falsely claims that the server did not publish', () => {
  const result = editorFailure(new Error('Network failed'), 'publish');
  expect(result.message).toContain('Publication could not be confirmed');
  expect(result.message).toContain('server already received');
  expect(result.message).not.toContain('Nothing new');
});
