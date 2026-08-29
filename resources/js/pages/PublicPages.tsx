import { useQuery } from '@tanstack/react-query';
import { ArrowRight, BookOpen, ExternalLink, HeartPulse, LockKeyhole, Mail, MapPin, ShieldCheck } from 'lucide-react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../api';
import Seo from '../Seo';
import type { PublicData } from '../types';

export function ServicesDirectory({ data }: { data?: PublicData }) {
  return <section className="section public-supplement" aria-labelledby="service-directory-title"><div className="container narrow">
    <div className="section-heading"><span className="eyebrow">Available consultations</span><h2 id="service-directory-title">Choose the care that fits your concern.</h2><p>Service descriptions and appointment lengths come from the same active practice catalogue used by booking.</p></div>
    <div className="row g-4">{data?.services.map((service) => <div className="col-md-6" key={service.id}><article className="service-card tall"><HeartPulse /><h3>{service.name}</h3><p>{service.description || service.summary}</p><p className="meta">Typical consultation · {service.duration_minutes} minutes {service.online_available && '· Online available'}</p><Link to={`/book?service=${encodeURIComponent(service.slug)}`}>Request this service <ArrowRight /></Link></article></div>)}</div>
  </div></section>;
}

export function VerifiedProfile() {
  const profile = useQuery({ queryKey: ['academic-profile'], queryFn: async () => (await api.get('/academic/profile')).data });
  const career = profile.data?.career || [];
  const achievements = profile.data?.achievements || [];
  return <section className="section public-supplement" aria-labelledby="verified-profile-title"><div className="container narrow">
    <div className="section-heading"><span className="eyebrow">Source-verified profile</span><h2 id="verified-profile-title">Professional record</h2><p>Only entries released through the evidence review queue are shown.</p></div>
    {career.length > 0 && <div className="career-timeline">{career.map((item: any) => <article key={item.id}><b>{item.year_label}</b><div><h3>{item.position}</h3><p>{item.institution}{item.location && ` · ${item.location}`}</p>{item.description && <p>{item.description}</p>}<a href={item.source_url} rel="noopener noreferrer">View source <ExternalLink /></a></div></article>)}</div>}
    {achievements.length > 0 && <div className="education-grid">{achievements.map((item: any) => <article key={item.id}><ShieldCheck /><span>{item.category}{item.year_label && ` · ${item.year_label}`}</span><h3>{item.title}</h3><p>{item.description}</p><a href={item.source_url} rel="noopener noreferrer">View source <ExternalLink /></a></article>)}</div>}
    {!career.length && !achievements.length && <div className="empty-state"><ShieldCheck /><h3>Profile verification is underway</h3><p>Career and achievement claims remain private until their sources and current context are approved.</p></div>}
  </div></section>;
}

export function ContactPage() {
  return <><Seo title="Contact the practice" description="Request an appointment or contact the breast oncology practice securely through the patient portal." canonicalPath="/contact" />
    <section className="page-hero"><div className="container"><span className="eyebrow">Contact</span><h1>Choose a secure way to reach the practice.</h1><p>Use appointment booking for a new care request. Existing patients can send a private message after signing in.</p></div></section>
    <section className="section"><div className="container narrow"><div className="contact-options">
      <article><HeartPulse /><h2>New care request</h2><p>Choose a consultation and a currently available time. The practice team will review and confirm your request.</p><Link className="btn btn-primary" to="/book">Request an appointment <ArrowRight /></Link></article>
      <article><LockKeyhole /><h2>Existing patient</h2><p>Use secure portal messages for private questions about an existing appointment or care journey.</p><Link className="btn btn-outline-primary" to="/portal">Open patient portal <Mail /></Link></article>
    </div><div className="notice"><MapPin /><div><b>Clinic details</b><p>The practice is based in Ile-Ife, Osun State. Exact clinic details are shared with confirmed patients.</p></div></div><div className="alert alert-warning" role="note"><strong>Urgent or emergency concern?</strong> This website and its messages are not monitored as an emergency service. Use the appropriate local emergency service or nearest emergency facility.</div></div></section>
  </>;
}

export function EducationArticlePage() {
  const { slug } = useParams();
  const article = useQuery({ queryKey: ['education-article', slug], queryFn: async () => (await api.get(`/education/articles/${slug}`)).data.data, retry: false });
  if (article.isLoading) return <div className="portal-loading">Opening education resource…</div>;
  if (!article.data) return <NotFoundPage />;
  const item = article.data;
  return <><Seo title={item.title} description={item.summary} canonicalPath={`/education/${item.slug}`} />
    <article className="article-detail"><header className="page-hero"><div className="container narrow"><span className="eyebrow">{item.category}</span><h1>{item.title}</h1><p>{item.summary}</p></div></header><div className="section"><div className="container article-body"><p className="review-line"><ShieldCheck /> Written by {item.author} · medically reviewed by {item.medical_reviewer} on {new Date(item.reviewed_at).toLocaleDateString('en-NG')}</p><div className="article-copy">{String(item.content).split(/\n{2,}/).map((paragraph: string, index: number) => <p key={index}>{paragraph}</p>)}</div><div className="notice"><ShieldCheck /><div><b>Medical information notice</b><p>{item.medical_disclaimer}</p></div></div><Link className="text-link" to="/education">Back to education <ArrowRight /></Link></div></div></article>
  </>;
}

export function PublicationDetailPage() {
  const { publicationId } = useParams();
  const publication = useQuery({ queryKey: ['publication', publicationId], queryFn: async () => (await api.get(`/academic/publications/${publicationId}`)).data.data, retry: false });
  if (publication.isLoading) return <div className="portal-loading">Opening publication…</div>;
  if (!publication.data) return <NotFoundPage />;
  const item = publication.data;
  return <><Seo title={item.title} description={`${item.authors}. ${item.journal}.`} canonicalPath={`/academic/publications/${item.id}`} />
    <article><header className="page-hero"><div className="container narrow"><span className="eyebrow">{item.category}</span><h1>{item.title}</h1><p>{item.authors}</p></div></header><section className="section"><div className="container article-body"><dl className="publication-facts"><div><dt>Journal</dt><dd>{item.journal}</dd></div><div><dt>Published</dt><dd>{item.published_at ? new Date(item.published_at).toLocaleDateString('en-NG', { year: 'numeric', month: 'long' }) : 'Date not supplied'}</dd></div>{item.doi && <div><dt>DOI</dt><dd><a href={`https://doi.org/${item.doi}`} rel="noopener noreferrer">{item.doi} <ExternalLink /></a></dd></div>}{item.pmid && <div><dt>PMID</dt><dd>{item.pmid}</dd></div>}</dl>{item.abstract && <><h2>Abstract</h2><p>{item.abstract}</p></>}{item.external_url && <a className="btn btn-primary" href={item.external_url} rel="noopener noreferrer">Open authoritative source <ExternalLink /></a>}<p><Link className="text-link" to="/academic">Back to academic portfolio</Link></p></div></section></article>
  </>;
}

export function LegalStatusPage({ type }: { type: 'privacy' | 'terms' | 'accessibility' }) {
  const labels = { privacy: 'Privacy notice', terms: 'Website terms', accessibility: 'Accessibility statement' };
  return <><Seo title={labels[type]} description={`${labels[type]} publication status for the practice website.`} canonicalPath={`/${type}`} noIndex />
    <section className="page-hero"><div className="container narrow"><span className="eyebrow">Governance</span><h1>{labels[type]}</h1><p>This document is not yet published because owner and legal approval is still required.</p></div></section><section className="section"><div className="container article-body"><div className="notice"><ShieldCheck /><div><b>Approval pending</b><p>The site will not present an unapproved legal draft as final policy. This route is intentionally excluded from search indexing until approved content is published.</p></div></div><p>For a care request, use the <Link to="/book">appointment form</Link>. Existing patients should use <Link to="/portal">secure portal messaging</Link> for private communication.</p></div></section>
  </>;
}

export function NotFoundPage() {
  return <><Seo title="Page not found" description="The requested page could not be found." noIndex />
    <section className="page-hero not-found"><div className="container narrow"><span className="eyebrow">404 · Page not found</span><h1>This page is not available.</h1><p>The address may be incorrect, or the page may have been removed. You can return home, view services or contact the practice.</p><div className="hero-actions"><Link className="btn btn-primary" to="/">Return home</Link><Link className="btn btn-outline-primary" to="/contact">Contact the practice</Link></div></div></section>
  </>;
}
