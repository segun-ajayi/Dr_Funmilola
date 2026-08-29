import { useEffect } from 'react';

const practiceName = 'Dr. Funmilola Olanike Wuraola';

export default function Seo({ title, description, canonicalPath, noIndex = false }: { title: string; description: string; canonicalPath?: string; noIndex?: boolean }) {
  useEffect(() => {
    const fullTitle = title.includes(practiceName) ? title : `${title} | ${practiceName}`;
    const canonical = new URL(canonicalPath || window.location.pathname, window.location.origin).toString();
    document.title = fullTitle;
    setMeta('name', 'description', description);
    setMeta('name', 'robots', noIndex ? 'noindex, nofollow' : 'index, follow');
    setMeta('property', 'og:title', fullTitle);
    setMeta('property', 'og:description', description);
    setMeta('property', 'og:type', 'website');
    setMeta('property', 'og:url', canonical);
    let link = document.querySelector<HTMLLinkElement>('link[rel="canonical"]');
    if (!link) {
      link = document.createElement('link');
      link.rel = 'canonical';
      document.head.appendChild(link);
    }
    link.href = canonical;
  }, [title, description, canonicalPath, noIndex]);

  return null;
}

function setMeta(attribute: 'name' | 'property', key: string, content: string) {
  let tag = document.querySelector<HTMLMetaElement>(`meta[${attribute}="${key}"]`);
  if (!tag) {
    tag = document.createElement('meta');
    tag.setAttribute(attribute, key);
    document.head.appendChild(tag);
  }
  tag.content = content;
}
