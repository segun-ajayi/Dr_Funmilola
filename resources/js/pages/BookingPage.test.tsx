import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import BookingPage from './BookingPage';

const service = {
  id: 1,
  name: 'Breast Concern Consultation',
  slug: 'breast-concern',
  summary: 'Specialist review',
  description: '',
  icon: 'heart-pulse',
  duration_minutes: 45,
  online_available: true,
};

describe('booking attachment flow', () => {
  afterEach(() => vi.restoreAllMocks());

  it('labels an optional private attachment and sends it in an idempotent multipart request', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch')
      .mockResolvedValueOnce(new Response(JSON.stringify({ data: [{ starts_at: '2026-08-31T09:00:00+01:00', ends_at: '2026-08-31T09:45:00+01:00', label: '9:00 AM' }] }), { status: 200, headers: { 'Content-Type': 'application/json' } }))
      .mockResolvedValueOnce(new Response(JSON.stringify({ message: 'Received', reference: 'private-reference' }), { status: 201, headers: { 'Content-Type': 'application/json' } }));

    render(<BookingPage services={[service]} />);
    fireEvent.click(screen.getByRole('button', { name: /Breast Concern Consultation/ }));
    fireEvent.click(screen.getByRole('button', { name: /Continue/ }));
    fireEvent.change(screen.getByLabelText('Preferred date'), { target: { value: '2026-08-31' } });
    fireEvent.click(await screen.findByRole('button', { name: '9:00 AM' }));
    fireEvent.click(screen.getByRole('button', { name: /Continue/ }));

    const attachment = screen.getByLabelText(/Attach a document/);
    expect(attachment).toHaveAttribute('accept', expect.stringContaining('.pdf'));
    expect(screen.getByText(/security-scanned and stored privately/)).toBeInTheDocument();
    const file = new File(['%PDF-1.4\nbenign'], 'referral.pdf', { type: 'application/pdf' });
    fireEvent.change(attachment, { target: { files: [file] } });
    expect(screen.getByText(/Selected: referral.pdf/)).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText('Full name'), { target: { value: 'Demo Patient' } });
    fireEvent.change(screen.getByLabelText('Phone'), { target: { value: '+2348000000000' } });
    fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'demo@example.test' } });
    fireEvent.change(screen.getByLabelText('Reason for appointment'), { target: { value: 'A detailed reason for requesting specialist care.' } });
    fireEvent.click(screen.getByRole('button', { name: /Send appointment request/ }));

    await screen.findByText(/Your attachment was scanned and stored securely/);
    const request = fetchMock.mock.calls[1];
    expect(request[0]).toBe('/api/appointment-requests');
    expect(request[1]?.headers).toEqual({ Accept: 'application/json' });
    const body = request[1]?.body as FormData;
    expect(body.get('attachment')).toBe(file);
    expect(String(body.get('client_request_id'))).toMatch(/^[0-9a-f-]{36}$/i);
    await waitFor(() => expect(screen.getByText('private-reference')).toBeInTheDocument());
  });
});
