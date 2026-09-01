import { useQueryClient } from '@tanstack/react-query';
import { FilePlus2,X } from 'lucide-react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from './api';

function slugify(value:string){return value.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').replace(/-+/g,'-')}

export default function NewPageDialog({onClose}:{onClose:()=>void}){
 const navigate=useNavigate(),qc=useQueryClient(),[title,setTitle]=useState(''),[slug,setSlug]=useState(''),[manualSlug,setManualSlug]=useState(false),[mode,setMode]=useState<'blank'|'template'>('blank'),[template,setTemplate]=useState<'standard'|'landing'|'resource'>('standard'),[busy,setBusy]=useState(false),[error,setError]=useState('');
 const create=async(event:React.FormEvent)=>{event.preventDefault();setBusy(true);setError('');try{const response=await api.post('/cms/pages',{title,slug:slug||undefined,start_mode:mode,template});await qc.invalidateQueries({queryKey:['cms-pages']});onClose();navigate(response.data.data.public_path)}catch(e:any){const errors=e?.response?.data?.errors;setError(errors?.slug?.[0]||errors?.title?.[0]||e?.response?.data?.message||'The page could not be created. Your choices are still here so you can correct them and retry.')}finally{setBusy(false)}};
 return <div className="cms-new-page-dialog" role="dialog" aria-modal="true" aria-labelledby="new-page-title" onKeyDown={event=>event.key==='Escape'&&!busy&&onClose()}><form onSubmit={create}><header><div><span>Create on the actual website</span><h2 id="new-page-title">New page</h2><p>The page starts privately and opens at its real address in Edit Mode.</p></div><button type="button" aria-label="Close new page dialog" disabled={busy} onClick={onClose}><X/></button></header>
  <label htmlFor="new-page-name">Page name<input id="new-page-name" autoFocus value={title} maxLength={150} required onChange={event=>{const value=event.target.value;setTitle(value);if(!manualSlug)setSlug(slugify(value))}} placeholder="Patient support guide"/></label>
  <label htmlFor="new-page-slug">Page address<div className="cms-page-address"><span>/p/</span><input id="new-page-slug" value={slug} maxLength={100} required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" onChange={event=>{setManualSlug(true);setSlug(event.target.value.toLowerCase())}} placeholder="patient-support-guide"/></div><small>Lowercase letters and numbers separated by hyphens. Existing and reserved addresses are rejected.</small></label>
  <fieldset><legend>Starting point</legend><label><input type="radio" name="start-mode" checked={mode==='blank'} onChange={()=>setMode('blank')}/> Blank page <small>Start with an empty page and add any of the 18 components.</small></label><label><input type="radio" name="start-mode" checked={mode==='template'} onChange={()=>setMode('template')}/> Starter template <small>Start with safe, editable sections.</small></label></fieldset>
  {mode==='template'&&<label htmlFor="new-page-template">Starter template<select id="new-page-template" value={template} onChange={event=>setTemplate(event.target.value as typeof template)}><option value="standard">Standard page — hero and text</option><option value="landing">Landing page — hero and next-step cards</option><option value="resource">Resource page — hero, article text and guidance CTA</option></select></label>}
  {error&&<p className="cms-new-page-error" role="alert">{error}</p>}
  <footer><button type="button" className="btn btn-outline-primary" disabled={busy} onClick={onClose}>Cancel</button><button type="submit" className="btn btn-primary" disabled={busy||!title||!slug}><FilePlus2/> {busy?'Creating private page…':'Create and open page'}</button></footer>
 </form></div>;
}
