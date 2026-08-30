import { useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Check, Play, Square, Video } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useState } from 'react';
import { api } from '../api';

export default function StaffConsultationsPage(){
 const qc=useQueryClient(),[busy,setBusy]=useState(''),[error,setError]=useState(''),[message,setMessage]=useState('');
 const consultations=useQuery({queryKey:['staff-consultations'],queryFn:async()=>(await api.get('/staff/consultations')).data.data,retry:false});
 const appointments=useQuery({queryKey:['online-appointments'],queryFn:async()=>(await api.get('/staff/appointments',{params:{method:'online',status:'confirmed'}})).data.data,retry:false});
 const refresh=async()=>{await qc.invalidateQueries({queryKey:['staff-consultations']});await qc.invalidateQueries({queryKey:['online-appointments']})};
 const run=async(key:string,action:()=>Promise<unknown>,success:string)=>{setBusy(key);setError('');setMessage('');try{await action();setMessage(success);await refresh()}catch(requestError:any){setError(requestError?.response?.data?.message||'The consultation was not updated. Review its current state and try again.')}finally{setBusy('')}};
 const create=(id:number)=>run(`create-${id}`,()=>api.post(`/staff/appointments/${id}/consultation`),'Consultation room prepared.');
 const status=(id:number,value:string)=>run(`status-${id}`,()=>api.patch(`/staff/consultations/${id}/status`,{status:value}),'Consultation status updated.');
 if(consultations.isError)return <section className="portal-guest"><h1>Staff access required.</h1><Link className="btn btn-primary" to="/sign-in">Sign in</Link></section>;
 const existing=new Set((consultations.data||[]).map((c:any)=>c.appointment_id));
 return <section className="staff-main consultation-admin"><header><div><span className="eyebrow">Practice operations</span><h1>Online consultations</h1><p>Prepare rooms and guide patients through the waiting room.</p></div><Link className="btn btn-outline-primary" to="/staff"><ArrowLeft/> Practice today</Link></header>{error&&<div className="alert alert-danger" role="alert">{error}</div>}{message&&<div className="success-line" role="status">{message}</div>}
  <div className="staff-grid"><section className="staff-card"><div className="card-heading"><div><span className="eyebrow">Sessions</span><h2>Consultation rooms</h2></div></div>{consultations.isLoading?<p role="status">Loading consultation rooms…</p>:consultations.data?.map((c:any)=><article className="consultation-admin-row" key={c.id}><Video/><div><h3>{c.appointment.patient.name}</h3><p>{c.appointment.service.name} · {new Date(c.appointment.starts_at).toLocaleString('en-NG')}</p><span className={`status status-${c.status}`}>{c.status}</span></div><div className="consultation-controls">{c.status==='waiting'&&<button disabled={busy===`status-${c.id}`} onClick={()=>status(c.id,'ready')}><Check/> Admit</button>}{c.status==='ready'&&<button disabled={busy===`status-${c.id}`} onClick={()=>status(c.id,'in_progress')}><Play/> Start</button>}{c.status==='in_progress'&&<button disabled={busy===`status-${c.id}`} className="danger" onClick={()=>status(c.id,'ended')}><Square/> End</button>}</div></article>)}</section>
   <aside className="staff-card"><div className="card-heading"><div><span className="eyebrow">Setup</span><h2>Confirmed online appointments</h2></div></div>{appointments.data?.filter((a:any)=>!existing.has(a.id)).map((a:any)=><div className="room-setup" key={a.id}><div><b>{a.patient.name}</b><small>{new Date(a.starts_at).toLocaleString('en-NG')}</small></div><button disabled={busy===`create-${a.id}`} className="btn btn-outline-primary" onClick={()=>create(a.id)}>{busy===`create-${a.id}`?'Preparing…':'Prepare room'}</button></div>)}{!appointments.data?.filter((a:any)=>!existing.has(a.id)).length&&<p className="section-help">No confirmed online appointments need a room.</p>}</aside></div>
 </section>
}
