import { useCallback, useEffect, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { api } from './api';
import type { CmsEditorSaveState } from './CmsEditContext';
import { alignSectionIds, AUTOSAVE_DELAY_MS, autosaveKey, clearRecovery, editorFailure, readRecovery, sectionsEqual, writeRecovery, type RecoveryCopy } from './cmsDraftRecovery';

type State = {
  document: any; baseline: any[]; lockVersion: number; history: any[]; future: any[];
  saveState: CmsEditorSaveState; revision: number; busy: boolean; recovery: RecoveryCopy | null;
  message: string; error: string; storageWarning: string;
};
const initial: State = { document: null, baseline: [], lockVersion: 0, history: [], future: [], saveState: 'clean', revision: 0, busy: false, recovery: null, message: '', error: '', storageWarning: '' };
const editedSaveState = (value: State, document: any): CmsEditorSaveState => ['saving', 'failed', 'conflict', 'session-expired'].includes(value.saveState) ? value.saveState : sectionsEqual(document.sections, value.baseline) ? 'clean' : 'unsaved';

export default function useCmsDraft({ pageId, editorId, serverDraft, editing, endpoint, normalize }: { pageId?: number; editorId?: number; serverDraft: any; editing: boolean; endpoint: string; normalize: (document: any) => any }) {
  const qc = useQueryClient();
  const [state, renderState] = useState<State>(initial);
  const current = useRef(state);
  const generation = useRef(0);
  const [autosaveEnabled, setAutosaveEnabled] = useState(false);
  const update = useCallback((change: (value: State) => State) => { current.current = change(current.current); renderState(current.current); }, []);
  const setMessage = useCallback((message: string) => update(value => ({ ...value, message })), [update]);
  const setError = useCallback((error: string) => update(value => ({ ...value, error, message: error ? '' : value.message })), [update]);

  useEffect(() => {
    generation.current += 1;
    if (!serverDraft || serverDraft.id !== pageId) { update(() => initial); return; }
    const document = normalize(serverDraft);
    const copy = editorId && pageId ? readRecovery(editorId, pageId) : null;
    const recovery = copy && !sectionsEqual(copy.sections, document.sections) ? copy : null;
    if (copy && !recovery && editorId && pageId) clearRecovery(editorId, pageId);
    update(() => ({ ...initial, document, baseline: document.sections, lockVersion: document.lock_version, recovery }));
    try { setAutosaveEnabled(Boolean(editorId && localStorage.getItem(autosaveKey(editorId)) === 'true')); } catch { setAutosaveEnabled(false); }
    return () => { generation.current += 1; };
    // Server cache confirmations must not replace a working document. Only a different page/account loads automatically.
  }, [serverDraft?.id, pageId, editorId, normalize, update]);

  const protect = useCallback((value: State): State => {
    if (!pageId || !editorId || !value.document) return value;
    if (!value.busy && !value.recovery && sectionsEqual(value.document.sections, value.baseline)) {
      clearRecovery(editorId, pageId);
      return { ...value, storageWarning: '' };
    }
    const copy: RecoveryCopy = { schema: 1, pageId, editorId, lockVersion: value.lockVersion, updatedAt: Date.now(), sections: value.document.sections };
    const protectedCopy = writeRecovery(copy);
    return { ...value, storageWarning: protectedCopy ? '' : 'A local recovery copy could not be stored. Keep this tab open and save manually before leaving.' };
  }, [editorId, pageId]);

  const changeDocument = useCallback((change: (document: any) => any) => update(value => {
    if (!value.document || value.recovery) return value;
    const document = change(value.document);
    if (document === value.document) return value;
    return protect({ ...value, document, history: [...value.history, value.document].slice(-50), future: [], revision: value.revision + 1, message: '', saveState: editedSaveState(value, document) });
  }), [protect, update]);
  const undo = useCallback(() => update(value => {
    if (!value.history.length || value.recovery) return value;
    return protect({ ...value, document: value.history.at(-1), history: value.history.slice(0, -1), future: [value.document, ...value.future], revision: value.revision + 1, message: '', saveState: editedSaveState(value, value.history.at(-1)) });
  }), [protect, update]);
  const redo = useCallback(() => update(value => {
    if (!value.future.length || value.recovery) return value;
    return protect({ ...value, document: value.future[0], history: [...value.history, value.document].slice(-50), future: value.future.slice(1), revision: value.revision + 1, message: '', saveState: editedSaveState(value, value.future[0]) });
  }), [protect, update]);

  const mutate = useCallback(async (operation: 'save' | 'publish') => {
    const submitted = current.current;
    if (!editing || !pageId || !submitted.document || submitted.busy || submitted.recovery || submitted.saveState === 'conflict') return;
    if (operation === 'publish' && !window.confirm('Publish this exact page for all website visitors?')) return;
    const activeGeneration = generation.current;
    update(value => protect({ ...value, busy: true, saveState: 'saving', error: '', message: '' }));
    try {
      if (submitted.saveState === 'session-expired' && editorId) {
        const account = (await api.get('/me')).data.user;
        if (account?.id !== editorId || account?.role !== 'power_admin') throw { response: { status: 401 } };
      }
      if (activeGeneration !== generation.current) return;
      const payload = { lock_version: submitted.lockVersion, sections: alignSectionIds(submitted.document.sections, submitted.baseline) };
      const response = operation === 'save' ? await api.put(`/cms/pages/${pageId}/visual-draft`, payload) : await api.post(`/cms/pages/${pageId}/publish`, payload);
      const confirmed = normalize(response.data.data);
      if (!confirmed || !Array.isArray(confirmed.sections) || !Number.isInteger(confirmed.lock_version)) throw new Error('Invalid save confirmation');
      if (activeGeneration !== generation.current) return;
      qc.setQueryData(['cms-page', pageId], confirmed);
      update(value => {
        const newerEdits = value.revision !== submitted.revision && !sectionsEqual(value.document.sections, submitted.document.sections);
        const document = newerEdits ? { ...value.document, lock_version: confirmed.lock_version, sections: alignSectionIds(value.document.sections, confirmed.sections) } : confirmed;
        const next: State = { ...value, document, baseline: confirmed.sections, lockVersion: confirmed.lock_version, busy: false, saveState: newerEdits ? 'unsaved' : 'saved', error: '', message: operation === 'publish' ? (newerEdits ? 'The submitted version was published. Your newer edits remain private and unsaved.' : 'Published successfully. Logged-out visitors now see this exact version.') : (newerEdits ? 'The submitted draft was saved. Your newer edits are still unsaved.' : 'The complete page draft is saved privately.') };
        if (newerEdits) return protect(next);
        const cleared = !editorId || clearRecovery(editorId, pageId);
        return { ...next, recovery: null, storageWarning: cleared ? '' : 'The draft is saved, but this browser could not remove its older recovery copy.' };
      });
      if (operation === 'publish') void qc.invalidateQueries({ queryKey: ['cms-public', endpoint] });
    } catch (error) {
      if (activeGeneration !== generation.current) return;
      const failure = editorFailure(error, operation);
      update(value => protect({ ...value, busy: false, saveState: failure.state, error: failure.message, message: '' }));
    }
  }, [editing, editorId, endpoint, normalize, pageId, protect, qc, update]);
  const save = useCallback(() => mutate('save'), [mutate]);
  const publish = useCallback(() => mutate('publish'), [mutate]);

  const previewDraft = useCallback(async () => {
    const value = current.current;
    if (!editing || !pageId || !value.document || value.busy || value.recovery || value.saveState === 'conflict') return;
    const activeGeneration = generation.current;
    update(state => ({ ...state, busy: true, error: '', message: '' }));
    try {
      const response = await api.post(`/cms/pages/${pageId}/preview`, { lock_version: value.lockVersion, sections: value.document.sections });
      if (activeGeneration !== generation.current) return;
      window.open(response.data.preview_url, '_blank', 'noopener,noreferrer');
      update(state => ({ ...state, busy: false, message: 'Exact draft preview opened in a new tab. This did not publish the page.' }));
    } catch (error) {
      if (activeGeneration !== generation.current) return;
      const failure = editorFailure(error, 'preview');
      update(state => protect({ ...state, busy: false, saveState: failure.state, error: failure.message, message: '' }));
    }
  }, [editing, pageId, protect, update]);

  const reloadServerDraft = useCallback(async () => {
    const value = current.current;
    if (!editing || !pageId || !editorId || value.busy || !window.confirm('Load the latest server draft for review? Your local edits will remain in a recovery copy in this tab.')) return;
    const copy: RecoveryCopy = { schema: 1, editorId, pageId, lockVersion: value.lockVersion, updatedAt: Date.now(), sections: value.document.sections };
    if (!writeRecovery(copy)) { setError('The local recovery copy could not be stored. The server draft was not loaded, so your working edits are unchanged.'); return; }
    const activeGeneration = generation.current;
    update(state => ({ ...state, busy: true, error: '', message: '' }));
    try {
      const response = await api.get(`/cms/pages/${pageId}`);
      const document = normalize(response.data.data);
      if (!document || !Array.isArray(document.sections) || !Number.isInteger(document.lock_version)) throw new Error('Invalid draft');
      if (activeGeneration !== generation.current) return;
      if (current.current.revision !== value.revision) {
        update(state => ({ ...state, busy: false, error: 'You edited the page while the server draft was loading. Your working page was not replaced. Load the server draft again when ready.' }));
        return;
      }
      qc.setQueryData(['cms-page', pageId], document);
      update(state => ({ ...state, document, baseline: document.sections, lockVersion: document.lock_version, history: [], future: [], revision: state.revision + 1, saveState: 'clean', busy: false, recovery: copy, message: 'The latest server draft is shown. Review it, then restore or discard your local copy.' }));
    } catch (error) {
      if (activeGeneration !== generation.current) return;
      const failure = editorFailure(error, 'reload');
      update(state => ({ ...state, busy: false, error: failure.message, saveState: failure.state }));
    }
  }, [editing, editorId, normalize, pageId, qc, setError, update]);

  const restoreRecovery = useCallback(() => {
    const value = current.current, copy = value.recovery;
    if (!copy || value.busy) return;
    if (copy.lockVersion !== value.lockVersion && !window.confirm('The server draft changed after this local copy was made. Restoring it replaces the whole working page; saving will replace the newer server draft. Have you reviewed the server version and want to continue?')) return;
    update(state => protect({ ...state, document: { ...state.document, sections: alignSectionIds(copy.sections, state.baseline) }, history: [state.document], future: [], revision: state.revision + 1, recovery: null, saveState: 'unsaved', error: '', message: 'Local edits restored. Review them and choose Save Draft; nothing was published.' }));
  }, [protect, update]);
  const discardRecovery = useCallback(() => {
    if (!editorId || !pageId || !window.confirm('Permanently discard this tab’s recovery copy? The current server draft will stay unchanged.')) return;
    if (!clearRecovery(editorId, pageId)) { setError('The recovery copy could not be removed. It has been kept for safety.'); return; }
    update(value => ({ ...value, recovery: null, storageWarning: '', message: 'Local recovery copy discarded. The server draft is unchanged.' }));
  }, [editorId, pageId, setError, update]);
  const setAutosave = useCallback((enabled: boolean) => {
    setAutosaveEnabled(enabled);
    try { if (editorId) localStorage.setItem(autosaveKey(editorId), String(enabled)); } catch { /* The current tab still honors the selected preference. */ }
    setMessage(enabled ? 'Autosave is on: private drafts save after 15 seconds without another edit. Publication remains manual.' : 'Autosave is off. Use Save Draft to save your changes.');
  }, [editorId, setMessage]);

  useEffect(() => {
    if (!editing || !autosaveEnabled || state.busy || state.recovery || state.saveState !== 'unsaved') return;
    const timer = window.setTimeout(() => { void save(); }, AUTOSAVE_DELAY_MS);
    return () => window.clearTimeout(timer);
  }, [autosaveEnabled, editing, save, state.busy, state.document, state.recovery, state.saveState]);

  const hasUnsavedChanges = Boolean(state.document && !sectionsEqual(state.document.sections, state.baseline));
  useEffect(() => {
    if (!hasUnsavedChanges && !state.busy) return;
    const warn = (event: BeforeUnloadEvent) => { event.preventDefault(); event.returnValue = ''; };
    window.addEventListener('beforeunload', warn);
    return () => window.removeEventListener('beforeunload', warn);
  }, [hasUnsavedChanges, state.busy]);

  return { ...state, autosaveEnabled, hasUnsavedChanges, changeDocument, undo, redo, save, publish, previewDraft, reloadServerDraft, restoreRecovery, discardRecovery, setAutosave, setMessage, setError };
}
