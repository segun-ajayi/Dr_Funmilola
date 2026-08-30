import { createContext } from 'react';

export type CmsEditorSaveState='clean'|'unsaved'|'saving'|'saved'|'failed';

export type CmsEditorBridge={
 pageTitle:string;
 selection:string;
 saveState:CmsEditorSaveState;
 canUndo:boolean;
 canRedo:boolean;
 select:()=>void;
 addSection:()=>void;
 undo:()=>void;
 redo:()=>void;
 preview:()=>void;
 save:()=>void;
 publish:()=>void;
};

type CmsEditContextValue={
 editing:boolean;
 setEditing:(editing:boolean)=>void;
 editor:CmsEditorBridge|null;
 registerEditor:(editor:CmsEditorBridge|null)=>void;
};

export const CmsEditContext=createContext<CmsEditContextValue>({editing:false,setEditing:()=>undefined,editor:null,registerEditor:()=>undefined});
