import { useQuery } from '@tanstack/react-query';
import { ArrowRight, BookOpen, ShieldCheck } from 'lucide-react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import Seo from '../Seo';

export default function EducationPage() {
  const articles = useQuery({ queryKey: ['education'], queryFn: async () => (await api.get('/education/articles')).data });
  return <><Seo title="Patient education" description="Medically reviewed breast-health education for informed conversations with your care team." canonicalPath="/education" />
    <section className="page-hero"><div className="container"><span className="eyebrow">Patient education</span><h1>Clear information for informed conversations.</h1><p>General breast-health resources with named medical review and update dates.</p></div></section>
    <section className="section"><div className="container narrow"><div className="education-grid">{articles.data?.data?.map((article: any) => <article key={article.id}><BookOpen /><span>{article.category}</span><h2><Link to={`/education/${article.slug}`}>{article.title}</Link></h2><p>{article.summary}</p><small><ShieldCheck /> Reviewed by {article.medical_reviewer} · {new Date(article.reviewed_at).toLocaleDateString('en-NG')}</small><Link className="text-link" to={`/education/${article.slug}`}>Read resource <ArrowRight /></Link></article>)}</div>{!articles.data?.total && <div className="empty-state"><BookOpen /><h3>Education resources are being medically reviewed</h3><p>Nothing is published until an author, reviewer, review date and disclaimer are recorded.</p></div>}</div></section>
  </>;
}
