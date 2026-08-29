import { useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Check, ExternalLink, RotateCcw, Send, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useState } from 'react';
import { api } from '../api';

const states = ['pending_review', 'verified', 'rejected', 'published', 'retracted'];

export default function VerificationQueuePage() {
  const queryClient = useQueryClient();
  const [status, setStatus] = useState('pending_review');
  const [retracting, setRetracting] = useState<number | null>(null);
  const [reason, setReason] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const queue = useQuery({
    queryKey: ['verification-queue', status],
    queryFn: async () => (await api.get('/cms/verification-queue', { params: { status } })).data,
    retry: false,
  });

  const refresh = () => queryClient.invalidateQueries({ queryKey: ['verification-queue'] });
  const run = async (action: () => Promise<unknown>, success: string) => {
    setMessage('');
    setError('');
    try {
      await action();
      setMessage(success);
      await refresh();
      return true;
    } catch (requestError: any) {
      setError(requestError?.response?.data?.message || 'The request could not be completed. Please try again.');
      return false;
    }
  };
  const decide = (id: number, decision: string) => run(
    () => api.patch(`/cms/verification-queue/${id}`, { decision }),
    decision === 'verified' ? 'The source was marked verified.' : 'The claim was rejected.',
  );
  const publish = (id: number) => run(
    () => api.post(`/cms/verification-queue/${id}/publish`),
    'The approved record is now public.',
  );
  const retract = async (id: number) => {
    if (reason.trim().length < 10) {
      setError('Please enter a retraction reason of at least 10 characters.');
      return;
    }
    const completed = await run(
      () => api.post(`/cms/verification-queue/${id}/retract`, { reason: reason.trim() }),
      'The record was retracted and removed from every public feed.',
    );
    if (completed) {
      setRetracting(null);
      setReason('');
    }
  };

  if (queue.isError) {
    return <section className="portal-guest"><h1>Power Admin access required.</h1><Link className="btn btn-primary" to="/sign-in">Sign in</Link></section>;
  }

  return <section className="staff-main verification-page">
    <header>
      <div><span className="eyebrow">Evidence first</span><h1>Research review</h1><p>Check each source before anything becomes public.</p></div>
      <Link className="btn btn-outline-primary" to="/staff/cms"><ArrowLeft /> Website editor</Link>
    </header>
    <div className="review-tabs">
      {states.map((state) => <button className={status === state ? 'active' : ''} onClick={() => { setStatus(state); setRetracting(null); }} key={state}>{state.replace('_', ' ')}</button>)}
    </div>
    {message && <p className="cms-message" role="status">{message}</p>}
    {error && <p className="cms-error" role="alert">{error}</p>}
    <div className="review-list">
      {queue.data?.data?.map((item: any) => <article key={item.id}>
        <div>
          <span>{item.category} · {item.confidence} confidence</span>
          <h2>{item.claim}</h2>
          <p>{item.source_title}</p>
          <a href={item.source_url} target="_blank" rel="noopener noreferrer">Open authoritative source <ExternalLink /></a>
          {status === 'retracted' && item.retracted_reason && <p><strong>Retraction reason:</strong> {item.retracted_reason}</p>}
        </div>
        <aside>
          {status === 'pending_review' && <>
            <button onClick={() => decide(item.id, 'verified')}><Check /> Mark verified</button>
            <button className="danger" onClick={() => decide(item.id, 'rejected')}><X /> Reject</button>
          </>}
          {status === 'verified' && <button onClick={() => publish(item.id)} disabled={!item.target_type}><Send /> Publish approved record</button>}
          {status === 'published' && retracting !== item.id && <button className="danger" onClick={() => { setRetracting(item.id); setReason(''); }}><RotateCcw /> Retract record</button>}
          {status === 'published' && retracting === item.id && <div className="retraction-form">
            <label htmlFor={`retraction-${item.id}`}>Reason for retraction</label>
            <textarea id={`retraction-${item.id}`} value={reason} onChange={(event) => setReason(event.target.value)} rows={4} maxLength={500} />
            <button className="danger" onClick={() => retract(item.id)}><RotateCcw /> Confirm retraction</button>
            <button onClick={() => { setRetracting(null); setReason(''); }}>Cancel</button>
          </div>}
        </aside>
      </article>)}
    </div>
    {!queue.data?.data?.length && <div className="staff-empty"><Check /><p>No records in this review state.</p></div>}
  </section>;
}
