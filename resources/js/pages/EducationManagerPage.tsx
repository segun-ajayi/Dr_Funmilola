import { useQuery,useQueryClient } from '@tanstack/react-query';
import { ArrowLeft,BookOpenCheck,Plus,Send } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';

const blank={title:'',slug:'',summary:'',content:'',author:'Dr. Funmilola Olanike Wuraola',medical_reviewer:'',reviewed_at:'',content_updated_at:'',category:'Breast health',medical_disclaimer:'This information is educational and does not replace an individual medical consultation.'};
const fields=[
  ['education-title','Article title','title','input'],['education-slug','URL slug','slug','input'],['education-category','Category','category','input'],
  ['education-summary','Short summary','summary','textarea'],['education-content','Article content','content','content'],
] as const;

export default function EducationManagerPage(){
  const qc=useQueryClient(),[form,setForm]=useState(blank),[message,setMessage]=useState(''),[error,setError]=useState(''),[busy,setBusy]=useState('');
  const q=useQuery({queryKey:['cms-education'],queryFn:async()=>(await api.get('/cms/education')).data.data,retry:false});
  const set=(key:string,value:string)=>setForm({...form,[key]:value});
  const create=async(e:React.FormEvent)=>{e.preventDefault();setBusy('create');setMessage('');setError('');try{await api.post('/cms/education',{...form,tags:[]});setForm(blank);setMessage('Article draft created.');await qc.invalidateQueries({queryKey:['cms-education']})}catch(requestError:any){setError(requestError?.response?.data?.message||'The draft was not saved. Your article is still here so you can try again.')}finally{setBusy('')}};
  const publish=async(id:number)=>{setBusy(`publish-${id}`);setMessage('');setError('');try{await api.post(`/cms/education/${id}/publish`);setMessage('Article published.');await qc.invalidateQueries({queryKey:['cms-education']})}catch(requestError:any){setError(requestError?.response?.data?.message||'The article was not published. Review it and try again.')}finally{setBusy('')}};
  if(q.isError)return <section className="portal-guest"><h1>Power Admin access required.</h1><Link className="btn btn-primary" to="/sign-in">Sign in</Link></section>;
  return <main className="staff-main management-page"><header><div><span className="eyebrow">Publishing</span><h1>Education articles</h1><p>Create medically reviewed, plain-text patient education.</p></div><Link className="btn btn-outline-primary" to="/staff"><ArrowLeft/> Dashboard</Link></header>
    {message&&<div className="success-line" role="status">{message}</div>}{error&&<div className="alert alert-danger" role="alert">{error}</div>}
    <div className="management-grid"><section className="staff-card"><div className="card-heading"><h2><Plus/> New draft</h2></div><form className="management-form" onSubmit={create}>
      {fields.map(([id,label,key,type])=><label key={key} htmlFor={id}>{label}{type==='input'?<input id={id} className="form-control" required value={form[key]} onChange={e=>set(key,e.target.value)}/>:<textarea id={id} className={`form-control ${type==='content'?'management-content':''}`} required value={form[key]} onChange={e=>set(key,e.target.value)}/>}</label>)}
      <div className="row g-2"><div className="col-md-6"><label htmlFor="education-author">Author<input id="education-author" className="form-control" required value={form.author} onChange={e=>set('author',e.target.value)}/></label></div><div className="col-md-6"><label htmlFor="education-reviewer">Medical reviewer<input id="education-reviewer" className="form-control" required value={form.medical_reviewer} onChange={e=>set('medical_reviewer',e.target.value)}/></label></div><div className="col-md-6"><label htmlFor="education-reviewed">Reviewed date<input id="education-reviewed" className="form-control" required type="date" value={form.reviewed_at} onChange={e=>set('reviewed_at',e.target.value)}/></label></div><div className="col-md-6"><label htmlFor="education-updated">Updated date<input id="education-updated" className="form-control" required type="date" value={form.content_updated_at} onChange={e=>set('content_updated_at',e.target.value)}/></label></div></div>
      <label htmlFor="education-disclaimer">Medical disclaimer<textarea id="education-disclaimer" className="form-control" required minLength={40} value={form.medical_disclaimer} onChange={e=>set('medical_disclaimer',e.target.value)}/></label><button disabled={busy==='create'} className="btn btn-primary"><BookOpenCheck/> {busy==='create'?'Saving…':'Save draft'}</button>
    </form></section><section className="staff-card"><div className="card-heading"><h2>Article library</h2></div>{q.isLoading?<p role="status">Loading articles…</p>:q.data?.length?q.data.map((a:any)=><article className="management-row" key={a.id}><div><span className="status">{a.status}</span><h3>{a.title}</h3><p>{a.category} · Updated {new Date(a.content_updated_at).toLocaleDateString()}</p></div>{a.status!=='published'&&<button disabled={busy===`publish-${a.id}`} className="btn btn-outline-primary" onClick={()=>publish(a.id)}><Send/> {busy===`publish-${a.id}`?'Publishing…':'Publish'}</button>}</article>):<div className="staff-empty"><BookOpenCheck/><p>No education articles yet.</p></div>}</section></div>
  </main>
}
