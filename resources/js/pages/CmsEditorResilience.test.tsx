import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import { Layout } from '../App';
import { api } from '../api';
import { AUTOSAVE_DELAY_MS, recoveryKey } from '../cmsDraftRecovery';
import CmsPublicPage from './CmsPublicPage';

vi.mock('../api', () => ({ api: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() } }));
const get = vi.mocked(api.get), put = vi.mocked(api.put), post = vi.mocked(api.post);
let server: any;
let account: any;
beforeEach(() => {
  sessionStorage.clear(); localStorage.clear(); vi.resetAllMocks();
  vi.spyOn(window, 'confirm').mockReturnValue(true);
  account = { id: 41, role: 'power_admin' };
  server = { id: 1, title: 'Home', slug: 'home', lock_version: 3, sections: [{ id: 10, section_key: 'copy', type: 'text', sort_order: 0, is_visible: true, content: { heading: 'Care', body: 'Server paragraph' }, presentation: {} }] };
  get.mockImplementation(async (url: string) => {
    if (url === '/me') return { data: { user: account } } as any;
    if (url === '/content/pages/home' || url === '/cms/pages/1') return { data: { data: structuredClone(server) } } as any;
    if (url === '/cms/pages') return { data: { data: [{ id: 1, slug: 'home' }] } } as any;
    return { data: { data: {} } } as any;
  });
  put.mockImplementation(async (_url: string, payload: any) => {
    server = { ...server, lock_version: server.lock_version + 1, sections: payload.sections };
    return { data: { data: structuredClone(server) } } as any;
  });
});
afterEach(() => { vi.useRealTimers(); vi.restoreAllMocks(); });

async function openEditor() {
  const view = render(<QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}><MemoryRouter initialEntries={['/']}><Layout><CmsPublicPage slug="home"/></Layout></MemoryRouter></QueryClientProvider>);
  fireEvent.click(await screen.findByRole('button', { name: 'Edit site' }));
  await waitFor(() => expect(screen.getByRole('button', { name: 'Save Draft' })).toBeEnabled());
  return view;
}
function editBody(value: string) {
  const paragraph = screen.queryByRole('textbox', { name: 'Editing Body text' }) || screen.getByText('Server paragraph');
  fireEvent.doubleClick(paragraph);
  paragraph.textContent = value;
  fireEvent.input(paragraph);
}

test('interrupted save retains exact edits and retry clears recovery only after confirmation', async () => {
  put.mockRejectedValueOnce(new Error('Network Error'));
  await openEditor(); editBody('Retain this exact paragraph');
  expect(JSON.parse(sessionStorage.getItem(recoveryKey(41, 1))!).sections[0].content.body).toBe('Retain this exact paragraph');
  fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
  expect(await screen.findByRole('alert')).toHaveTextContent('connection was interrupted');
  expect(screen.getByRole('textbox', { name: 'Editing Body text' })).toHaveTextContent('Retain this exact paragraph');
  expect(screen.queryByText('Draft saved')).not.toBeInTheDocument();
  expect(screen.getByRole('button', { name: 'Undo' })).toBeEnabled();
  fireEvent.click(await screen.findByRole('button', { name: 'Retry Save' }));
  expect(await screen.findByText('Draft saved')).toBeInTheDocument();
  expect(put).toHaveBeenCalledTimes(2);
  expect(sessionStorage.getItem(recoveryKey(41, 1))).toBeNull();
  expect(post).not.toHaveBeenCalled();
});

test('typing during an in-flight save is retained and a second request uses the confirmed lock', async () => {
  let finish!: (value: any) => void;
  put.mockImplementationOnce(() => new Promise(resolve => { finish = resolve; }));
  await openEditor(); editBody('Submitted paragraph');
  fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
  expect(screen.getByRole('button', { name: 'Save Draft' })).toBeDisabled();
  editBody('Newer paragraph typed while saving');
  fireEvent.keyDown(document, { key: 's', ctrlKey: true });
  expect(put).toHaveBeenCalledTimes(1);
  await act(async () => { finish({ data: { data: { ...server, lock_version: 4, sections: (put.mock.calls[0][1] as any).sections } } }); });
  expect(screen.getByRole('textbox', { name: 'Editing Body text' })).toHaveTextContent('Newer paragraph typed while saving');
  expect(screen.getByText('Unsaved changes')).toBeInTheDocument();
  expect(screen.queryByText('Draft saved')).not.toBeInTheDocument();
  expect(JSON.parse(sessionStorage.getItem(recoveryKey(41, 1))!).lockVersion).toBe(4);
  fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
  await waitFor(() => expect(put).toHaveBeenLastCalledWith('/cms/pages/1/visual-draft', expect.objectContaining({ lock_version: 4, sections: [expect.objectContaining({ content: expect.objectContaining({ body: 'Newer paragraph typed while saving' }) })] })));
});

test('editing while preview loads remains unsaved, and Undo to the server draft clears recovery', async () => {
  let finish!: (value: any) => void;
  post.mockImplementationOnce(() => new Promise(resolve => { finish = resolve; }));
  vi.spyOn(window, 'open').mockReturnValue(null);
  await openEditor();
  fireEvent.click(screen.getByRole('button', { name: 'Preview' }));
  editBody('Newer than preview');
  await act(async () => { finish({ data: { preview_url: '/preview/test' } }); });
  expect(screen.getByText('Unsaved changes')).toBeInTheDocument();
  expect(screen.getByRole('textbox', { name: 'Editing Body text' })).toHaveTextContent('Newer than preview');
  fireEvent.click(screen.getByRole('button', { name: 'Undo' }));
  expect(screen.getByText('All changes saved')).toBeInTheDocument();
  expect(sessionStorage.getItem(recoveryKey(41, 1))).toBeNull();
  const unload = new Event('beforeunload', { cancelable: true }); window.dispatchEvent(unload);
  expect(unload.defaultPrevented).toBe(false);
});

test('publishing confirms only the submitted snapshot while newer changes remain private and unsaved', async () => {
  let finish!: (value: any) => void;
  post.mockImplementationOnce(() => new Promise(resolve => { finish = resolve; }));
  await openEditor(); editBody('Submitted publication');
  fireEvent.click(screen.getByRole('button', { name: 'Publish' }));
  editBody('Newer private edits');
  fireEvent.keyDown(document, { key: 's', ctrlKey: true });
  expect(put).not.toHaveBeenCalled();
  await act(async () => { finish({ data: { data: { ...server, lock_version: 4, sections: (post.mock.calls[0][1] as any).sections } } }); });
  expect(screen.getByText(/submitted version was published/)).toHaveTextContent('newer edits remain private and unsaved');
  expect(screen.getByText('Unsaved changes')).toBeInTheDocument();
  expect(screen.getByRole('textbox', { name: 'Editing Body text' })).toHaveTextContent('Newer private edits');
  expect(post).toHaveBeenCalledTimes(1);
});

test('refresh offers a per-account local copy and restores it only by explicit choice', async () => {
  const first = await openEditor(); editBody('Recovered paragraph');
  first.unmount();
  const second = render(<QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}><MemoryRouter><Layout><CmsPublicPage slug="home"/></Layout></MemoryRouter></QueryClientProvider>);
  fireEvent.click(await screen.findByRole('button', { name: 'Edit site' }));
  expect(await screen.findByRole('heading', { name: 'Recover unsaved page edits' })).toBeInTheDocument();
  expect(screen.getByText('Server paragraph')).toBeInTheDocument();
  expect(screen.getByRole('button', { name: 'Save Draft' })).toBeDisabled();
  fireEvent.click(screen.getByRole('button', { name: 'Restore local edits' }));
  expect(screen.getByText('Recovered paragraph')).toBeInTheDocument();
  expect(screen.getByText('Unsaved changes')).toBeInTheDocument();
  expect(put).not.toHaveBeenCalled();
  second.unmount(); account = { id: 42, role: 'power_admin' };
  await openEditor();
  expect(screen.queryByRole('heading', { name: 'Recover unsaved page edits' })).not.toBeInTheDocument();
  expect(screen.getByText('Server paragraph')).toBeInTheDocument();
  expect(sessionStorage.getItem(recoveryKey(41, 1))).not.toBeNull();
});

test('conflict never resubmits stale edits and server review keeps a recoverable local copy', async () => {
  put.mockRejectedValueOnce({ response: { status: 409 } });
  await openEditor(); editBody('My local changes');
  fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
  expect(await screen.findByRole('alert')).toHaveTextContent('Another editing session changed this page');
  expect(screen.getByRole('button', { name: 'Save Draft' })).toBeDisabled();
  fireEvent.keyDown(document, { key: 's', ctrlKey: true });
  expect(put).toHaveBeenCalledTimes(1);
  server = { ...server, lock_version: 9, sections: [{ ...server.sections[0], content: { heading: 'Care', body: 'Other editor changes' } }] };
  fireEvent.click(await screen.findByRole('button', { name: 'Load server draft' }));
  expect(await screen.findByText('Other editor changes')).toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'Recover unsaved page edits' })).toBeInTheDocument();
  vi.mocked(window.confirm).mockReturnValueOnce(false);
  fireEvent.click(screen.getByRole('button', { name: 'Restore local edits' }));
  expect(screen.getByText('Other editor changes')).toBeInTheDocument();
  fireEvent.click(screen.getByRole('button', { name: 'Restore local edits' }));
  expect(screen.getByText('My local changes')).toBeInTheDocument();
  fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
  await waitFor(() => expect(put).toHaveBeenLastCalledWith('/cms/pages/1/visual-draft', expect.objectContaining({ lock_version: 9 })));
  expect(await screen.findByText('Draft saved')).toBeInTheDocument();
});

test.each([401, 419])('session expiry %s keeps edits, offers sign-in, and verifies the same account before retry', async status => {
  put.mockRejectedValueOnce({ response: { status } });
  await openEditor(); editBody('Keep through sign-in');
  fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
  expect(await screen.findByRole('alert')).toHaveTextContent('editing session expired');
  expect(await screen.findByRole('link', { name: 'Sign in again' })).toHaveAttribute('target', '_blank');
  expect(screen.getByRole('button', { name: 'Publish' })).toBeDisabled();
  fireEvent.click(screen.getByRole('button', { name: 'Retry Save' }));
  expect(await screen.findByText('Draft saved')).toBeInTheDocument();
  expect(get.mock.calls.filter(([url]) => url === '/me')).toHaveLength(2);
  expect(server.sections[0].content.body).toBe('Keep through sign-in');
});

test('a different signed-in account cannot retry the original editor’s pending save', async () => {
  put.mockRejectedValueOnce({ response: { status: 419 } });
  await openEditor(); editBody('Original editor copy');
  fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
  const retry = await screen.findByRole('button', { name: 'Retry Save' });
  account = { id: 42, role: 'power_admin' };
  fireEvent.click(retry);
  await waitFor(() => expect(screen.getByRole('button', { name: 'Retry Save' })).toBeEnabled());
  fireEvent.click(screen.getByRole('button', { name: 'Retry Save' }));
  await waitFor(() => expect(screen.getByRole('button', { name: 'Retry Save' })).toBeEnabled());
  expect(put).toHaveBeenCalledTimes(1);
  expect(screen.getByRole('textbox', { name: 'Editing Body text' })).toHaveTextContent('Original editor copy');
  expect(screen.getByRole('alert')).toHaveTextContent('same account');
});

test('autosave is opt-in, waits for idle editing, saves privately and stops after a failure', async () => {
  await openEditor();
  vi.useFakeTimers();
  editBody('Automatic private draft');
  await act(async () => { await vi.advanceTimersByTimeAsync(AUTOSAVE_DELAY_MS * 2); });
  expect(put).not.toHaveBeenCalled();
  fireEvent.click(screen.getByRole('button', { name: 'Autosave off' }));
  expect(screen.getByRole('button', { name: 'Autosave on' })).toHaveAttribute('aria-pressed', 'true');
  await act(async () => { await vi.advanceTimersByTimeAsync(AUTOSAVE_DELAY_MS - 1); });
  expect(put).not.toHaveBeenCalled();
  await act(async () => { await vi.advanceTimersByTimeAsync(1); });
  expect(put).toHaveBeenCalledTimes(1);
  expect(post).not.toHaveBeenCalled();
  expect(screen.getByText('Draft saved')).toBeInTheDocument();
  put.mockRejectedValueOnce({ response: { status: 500 } });
  editBody('Keep after autosave failure');
  await act(async () => { await vi.advanceTimersByTimeAsync(AUTOSAVE_DELAY_MS); });
  expect(screen.getByRole('alert')).toHaveTextContent('server could not confirm');
  await act(async () => { await vi.advanceTimersByTimeAsync(AUTOSAVE_DELAY_MS * 4); });
  expect(put).toHaveBeenCalledTimes(2);
  expect(screen.getByRole('button', { name: 'Retry Save' })).toBeEnabled();
});

test('storage failure is truthful and unsaved unload protection stays active after exiting Edit Mode', async () => {
  vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('quota'); });
  await openEditor(); editBody('Keep this tab open');
  expect(screen.getByRole('alert')).toHaveTextContent('recovery copy could not be stored');
  const before = new Event('beforeunload', { cancelable: true });
  window.dispatchEvent(before); expect(before.defaultPrevented).toBe(true);
  fireEvent.click(screen.getByRole('button', { name: 'Exit Edit Mode' }));
  const after = new Event('beforeunload', { cancelable: true });
  window.dispatchEvent(after); expect(after.defaultPrevented).toBe(true);
  expect(put).not.toHaveBeenCalled();
});
