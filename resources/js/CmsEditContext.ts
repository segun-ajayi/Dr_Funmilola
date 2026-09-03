import { createContext } from 'react';

export type CmsEditorSaveState='clean'|'unsaved'|'saving'|'saved'|'failed'|'conflict'|'session-expired';

export type CmsEditorBridge={
 pageTitle:string;
 selection:string;
 saveState:CmsEditorSaveState;
 autosaveEnabled:boolean;
 busy:boolean;
 hasUnsavedChanges:boolean;
 recoveryPending:boolean;
 canUndo:boolean;
 canRedo:boolean;
 select:()=>void;
 addSection:()=>void;
 undo:()=>void;
 redo:()=>void;
 preview:()=>void;
 save:()=>void;
 retry:()=>void;
 reloadServerDraft:()=>void;
 setAutosave:(enabled:boolean)=>void;
 publish:()=>void;
};

type CmsEditContextValue={
 editing:boolean;
 editorId?:number;
 setEditing:(editing:boolean)=>void;
 editor:CmsEditorBridge|null;
 registerEditor:(editor:CmsEditorBridge|null)=>void;
};

export const CmsEditContext=createContext<CmsEditContextValue>({editing:false,setEditing:()=>undefined,editor:null,registerEditor:()=>undefined});
