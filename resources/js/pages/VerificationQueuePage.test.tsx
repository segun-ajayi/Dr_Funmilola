import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, expect, test, vi } from 'vitest';
import { api } from '../api';
import VerificationQueuePage from './VerificationQueuePage';

vi.mock('../api', () => ({ api: { get: vi.fn(), post: vi.fn(), patch: vi.fn() } }));
const get = vi.mocked(api.get);
const post = vi.mocked(api.post);
const patch = vi.mocked(api.patch);

function view() {
  return render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <MemoryRouter><VerificationQueuePage /></MemoryRouter>
    </QueryClientProvider>,
  );
}

beforeEach(() => {
  get.mockReset();
  post.mockReset();
  patch.mockReset();
  get.mockImplementation(async (_url, config: any) => ({
    data: {
      data: config?.params?.status === 'published' ? [{
        id: 8,
        category: 'Publication',
        confidence: 'high',
        claim: 'A sourced published study',
        source_title: 'PubMed PMID 123',
        source_url: 'https://pubmed.ncbi.nlm.nih.gov/123/',
        target_type: 'publication',
      }] : [],
    },
  } as any));
  post.mockResolvedValue({ data: { data: {} } } as any);
  patch.mockResolvedValue({ data: { data: {} } } as any);
});

test('published evidence has a deliberate reasoned retraction flow', async () => {
  view();
  fireEvent.click(await screen.findByRole('button', { name: 'published' }));
  expect(await screen.findByRole('heading', { name: 'A sourced published study' })).toBeInTheDocument();
  fireEvent.click(screen.getByRole('button', { name: /Retract record/ }));
  fireEvent.change(screen.getByLabelText('Reason for retraction'), { target: { value: 'The journal withdrew this source.' } });
  fireEvent.click(screen.getByRole('button', { name: /Confirm retraction/ }));
  await waitFor(() => expect(post).toHaveBeenCalledWith('/cms/verification-queue/8/retract', { reason: 'The journal withdrew this source.' }));
  expect(await screen.findByRole('status')).toHaveTextContent('removed from every public feed');
});

test('short retraction reason is announced and never submitted', async () => {
  view();
  fireEvent.click(await screen.findByRole('button', { name: 'published' }));
  fireEvent.click(await screen.findByRole('button', { name: /Retract record/ }));
  fireEvent.change(screen.getByLabelText('Reason for retraction'), { target: { value: 'Short' } });
  fireEvent.click(screen.getByRole('button', { name: /Confirm retraction/ }));
  expect(screen.getByRole('alert')).toHaveTextContent('at least 10 characters');
  expect(post).not.toHaveBeenCalled();
});
