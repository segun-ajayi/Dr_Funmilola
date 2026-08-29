import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, expect, test, vi } from 'vitest';
import { api } from '../api';
import Seo from '../Seo';
import AcademicPortfolioPage from './AcademicPortfolioPage';
import EducationPage from './EducationPage';
import { ContactPage, NotFoundPage, PublicationDetailPage } from './PublicPages';

vi.mock('../api', () => ({ api: { get: vi.fn() } }));
const get = vi.mocked(api.get);

function view(node: React.ReactNode, path = '/') {
  return render(<QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}><MemoryRouter initialEntries={[path]}>{node}</MemoryRouter></QueryClientProvider>);
}

beforeEach(() => get.mockReset());

test('contact and 404 pages provide useful working destinations', () => {
  const contact = view(<ContactPage />);
  expect(screen.getByRole('link', { name: /Request an appointment/ })).toHaveAttribute('href', '/book');
  expect(screen.getByRole('link', { name: /Open patient portal/ })).toHaveAttribute('href', '/portal');
  contact.unmount();
  view(<NotFoundPage />, '/missing');
  expect(screen.getByRole('heading', { name: 'This page is not available.' })).toBeInTheDocument();
  expect(screen.getByRole('link', { name: 'Contact the practice' })).toHaveAttribute('href', '/contact');
});

test('education cards link to medically reviewed detail routes', async () => {
  get.mockResolvedValue({ data: { data: [{ id: 1, slug: 'clinic-guide', title: 'Clinic guide', summary: 'Prepare for care.', category: 'Appointments', medical_reviewer: 'Dr. Reviewer', reviewed_at: '2026-08-01' }], total: 1 } } as any);
  view(<EducationPage />, '/education');
  expect(await screen.findByRole('link', { name: 'Clinic guide' })).toHaveAttribute('href', '/education/clinic-guide');
  expect(screen.getByRole('link', { name: /Read resource/ })).toHaveAttribute('href', '/education/clinic-guide');
});

test('academic portfolio exposes sort detail DOI and authoritative source journeys', async () => {
  get.mockImplementation(async (url: string) => url === '/academic/profile' ? { data: { career: [], achievements: [] } } as any : { data: { data: [{ id: 7, title: 'Published evidence', authors: 'F. Wuraola', journal: 'Journal', published_at: '2025-01-01', category: 'Breast Cancer', doi: '10.1000/safe', external_url: 'https://pubmed.ncbi.nlm.nih.gov/7/' }], total: 1, last_page: 1 } } as any);
  view(<AcademicPortfolioPage />, '/academic');
  expect(await screen.findByRole('link', { name: 'Published evidence' })).toHaveAttribute('href', '/academic/publications/7');
  expect(screen.getByRole('link', { name: /DOI 10.1000\/safe/ })).toHaveAttribute('href', 'https://doi.org/10.1000/safe');
  expect(screen.getByRole('link', { name: /Authoritative source/ })).toHaveAttribute('href', 'https://pubmed.ncbi.nlm.nih.gov/7/');
  fireEvent.change(screen.getByLabelText('Sort'), { target: { value: 'title' } });
  await waitFor(() => expect(get).toHaveBeenCalledWith('/academic/publications', { params: expect.objectContaining({ sort: 'title' }) }));
});

test('publication detail renders identifiers and updates canonical metadata', async () => {
  get.mockResolvedValue({ data: { data: { id: 7, title: 'Published evidence', authors: 'F. Wuraola', journal: 'Journal', published_at: '2025-01-01', category: 'Breast Cancer', doi: '10.1000/safe', pmid: '123', external_url: 'https://pubmed.ncbi.nlm.nih.gov/123/' } } } as any);
  view(<Routes><Route path="/academic/publications/:publicationId" element={<PublicationDetailPage />} /></Routes>, '/academic/publications/7');
  expect(await screen.findByRole('heading', { name: 'Published evidence' })).toBeInTheDocument();
  expect(screen.getByText('123')).toBeInTheDocument();
  await waitFor(() => expect(document.querySelector('link[rel="canonical"]')).toHaveAttribute('href', 'http://localhost:3000/academic/publications/7'));
});

test('SEO helper applies description Open Graph canonical and noindex metadata', async () => {
  view(<Seo title="Safe page" description="A safe description." canonicalPath="/safe" noIndex />);
  await waitFor(() => expect(document.title).toContain('Safe page'));
  expect(document.querySelector('meta[name="description"]')).toHaveAttribute('content', 'A safe description.');
  expect(document.querySelector('meta[name="robots"]')).toHaveAttribute('content', 'noindex, nofollow');
  expect(document.querySelector('meta[property="og:title"]')).toHaveAttribute('content', expect.stringContaining('Safe page'));
});
