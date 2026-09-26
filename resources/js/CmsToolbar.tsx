import { Cloud, Eye, FilePlus2, MousePointer2, Plus, Redo2, RefreshCw, Save, Send, Undo2, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { CmsEditorBridge } from './CmsEditContext';

export default function CmsToolbar({ editor, navigationDirty, contextual, newPageOpen, newPageButtonRef, onNewPage, onExit }: { editor: CmsEditorBridge | null; navigationDirty: boolean; contextual: string; newPageOpen: boolean; newPageButtonRef: React.RefObject<HTMLButtonElement | null>; onNewPage: () => void; onExit: () => void }) {
  const unavailable = !editor || editor.recoveryPending;
  const blocked = unavailable || editor?.busy || editor?.saveState === 'conflict';
  const failure = editor && ['failed', 'conflict', 'session-expired'].includes(editor.saveState);
  const status = editor?.recoveryPending ? 'Choose whether to recover your local edits' : editor?.saveState === 'conflict' ? 'Conflict — local edits retained' : editor?.saveState === 'session-expired' ? 'Session expired — local edits retained' : editor?.saveState === 'failed' ? 'Request failed — local edits retained' : navigationDirty ? 'Unsaved navigation changes' : !editor ? 'This page is not editable' : editor.saveState === 'saving' ? 'Saving…' : editor.saveState === 'unsaved' ? 'Unsaved changes' : editor.saveState === 'saved' ? 'Draft saved' : 'All changes saved';
  return <div className="visual-editor-toolbar" role="toolbar" aria-label="Website editor" aria-busy={editor?.busy || false}>
    <div className="visual-editor-identity"><b>Edit Mode</b><span>{editor?.pageTitle || 'This public page'}{editor?.selection ? ` · ${editor.selection}` : ''}</span></div>
    <div className="visual-toolbar-actions" role="group" aria-label="Page editing actions">
      <button type="button" onClick={editor?.select} disabled={unavailable}><MousePointer2/> Select</button>
      <button type="button" className="cms-add-section-trigger" aria-haspopup="dialog" aria-expanded={editor?.componentLibraryOpen || false} aria-controls={editor?.componentLibraryOpen ? 'component-library-dialog' : undefined} onClick={editor?.addSection} disabled={unavailable}><Plus/> Add Section</button>
      <button ref={newPageButtonRef} type="button" aria-haspopup="dialog" aria-expanded={newPageOpen} aria-controls={newPageOpen ? 'new-page-dialog' : undefined} onClick={onNewPage}><FilePlus2/> New Page</button>
      <span className="visual-toolbar-separator" aria-hidden="true"/>
      <button type="button" aria-keyshortcuts="Control+Z Meta+Z" onClick={editor?.undo} disabled={!editor?.canUndo}><Undo2/> Undo</button>
      <button type="button" aria-keyshortcuts="Control+Shift+Z Meta+Shift+Z" onClick={editor?.redo} disabled={!editor?.canRedo}><Redo2/> Redo</button>
      <button type="button" onClick={editor?.preview} disabled={blocked}><Eye/> Preview</button>
      <button type="button" aria-keyshortcuts="Control+S Meta+S" onClick={editor?.save} disabled={blocked}><Save/> Save Draft</button>
      <button type="button" aria-pressed={editor?.autosaveEnabled || false} onClick={() => editor?.setAutosave(!editor.autosaveEnabled)} disabled={unavailable} title="Save a private draft after 15 seconds without another edit. Never publishes."><Cloud/> Autosave {editor?.autosaveEnabled ? 'on' : 'off'}</button>
      {editor && ['failed', 'session-expired'].includes(editor.saveState) && <button type="button" onClick={editor.retry} disabled={editor.busy}><RefreshCw/> Retry Save</button>}
      {editor?.saveState === 'conflict' && <button type="button" onClick={editor.reloadServerDraft} disabled={editor.busy}><RefreshCw/> Load server draft</button>}
      {editor?.saveState === 'session-expired' && <a className="visual-editor-more" href="/sign-in" target="_blank" rel="noopener noreferrer">Sign in again</a>}
      <button type="button" className="publish" onClick={editor?.publish} disabled={blocked || editor?.saveState === 'session-expired'}><Send/> Publish</button>
      <button type="button" onClick={onExit} disabled={editor?.busy}><X/> Exit Edit Mode</button>
      <Link className="visual-editor-more" to={contextual}>Page settings</Link>
    </div>
    <span className={`visual-save-state ${failure ? editor?.saveState : navigationDirty ? 'unsaved' : editor?.saveState || 'clean'}`} role="status" aria-live={failure ? 'assertive' : 'polite'} aria-atomic="true">{status}</span>
  </div>;
}
