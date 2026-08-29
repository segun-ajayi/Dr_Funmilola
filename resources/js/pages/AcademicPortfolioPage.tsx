import { useQuery } from '@tanstack/react-query';
import { BookOpen, ExternalLink, Search, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import Seo from '../Seo';

export default function AcademicPortfolioPage() {
  const [q, setQ] = useState('');
  const [category, setCategory] = useState('');
  const [sort, setSort] = useState('newest');
  const [page, setPage] = useState(1);
  const publications = useQuery({
    queryKey: ['academic-publications', q, category, sort, page],
    queryFn: async () => (await api.get('/academic/publications', { params: { q: q || undefined, category: category || undefined, sort, page } })).data,
  });
  const profile = useQuery({ queryKey: ['academic-profile'], queryFn: async () => (await api.get('/academic/profile')).data });
  const change = (setter: (value: string) => void, value: string) => { setter(value); setPage(1); };

  return <><Seo title="Academic portfolio" description="Source-verified publications, career entries and achievements from Dr. Funmilola Olanike Wuraola." canonicalPath="/academic" />
    <section className="page-hero"><div className="container"><span className="eyebrow">Academic profile</span><h1>Research grounded in better cancer care.</h1><p>Only records approved through the practice’s source-verification process appear here.</p></div></section>
    <section className="section"><div className="container narrow">
      <div className="academic-search"><Search /><label><span>Search publications</span><input className="form-control" placeholder="Title or author" value={q} onChange={(event) => change(setQ, event.target.value)} /></label><label><span>Category</span><select className="form-control" value={category} onChange={(event) => change(setCategory, event.target.value)}><option value="">All categories</option><option>Breast Cancer</option><option>Breast Reconstruction</option><option>Breast Surgery</option><option>Genetics</option><option>Cancer Survivorship</option><option>Health Economics</option><option>Screening</option></select></label><label><span>Sort</span><select className="form-control" value={sort} onChange={(event) => change(setSort, event.target.value)}><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="title">Title A–Z</option></select></label></div>
      <p className="result-count" role="status">{publications.data?.total ?? 0} published record{publications.data?.total === 1 ? '' : 's'}</p>
      {publications.data?.data?.map((publication: any) => <article className="publication" key={publication.id}><span>{publication.category}</span><h2><Link to={`/academic/publications/${publication.id}`}>{publication.title}</Link></h2><p>{publication.authors}</p><small>{publication.journal} {publication.published_at && `· ${new Date(publication.published_at).getFullYear()}`}</small><div className="publication-links"><Link className="text-link" to={`/academic/publications/${publication.id}`}>Publication details <BookOpen /></Link>{publication.doi && <a className="text-link" href={`https://doi.org/${publication.doi}`} rel="noopener noreferrer">DOI {publication.doi} <ExternalLink /></a>}{publication.external_url && <a className="text-link" href={publication.external_url} rel="noopener noreferrer">Authoritative source <ExternalLink /></a>}</div></article>)}
      {!publications.data?.total && <div className="empty-state"><BookOpen /><h3>Verified publication review is underway</h3><p>Records appear after source review and Power Admin approval.</p></div>}
      {(publications.data?.last_page ?? 1) > 1 && <nav className="pagination-controls" aria-label="Publication pages"><button disabled={page <= 1} onClick={() => setPage(page - 1)}>Previous</button><span>Page {page} of {publications.data.last_page}</span><button disabled={page >= publications.data.last_page} onClick={() => setPage(page + 1)}>Next</button></nav>}
      <section className="verified-profile"><div className="section-heading"><span className="eyebrow">Verified experience</span><h2>Career and achievements</h2></div>{profile.data?.career?.length > 0 && <div className="career-timeline">{profile.data.career.map((item: any) => <article key={item.id}><b>{item.year_label}</b><div><h3>{item.position}</h3><p>{item.institution} · {item.location}</p><a href={item.source_url} rel="noopener noreferrer">View source <ExternalLink /></a></div></article>)}</div>}{profile.data?.achievements?.length > 0 && <div className="education-grid">{profile.data.achievements.map((item: any) => <article key={item.id}><ShieldCheck /><span>{item.category}{item.year_label && ` · ${item.year_label}`}</span><h3>{item.title}</h3><p>{item.description}</p><a href={item.source_url} rel="noopener noreferrer">View source <ExternalLink /></a></article>)}</div>}{!profile.data?.career?.length && !profile.data?.achievements?.length && <p>No career or achievement record has completed source approval yet.</p>}</section>
    </div></section>
  </>;
}
