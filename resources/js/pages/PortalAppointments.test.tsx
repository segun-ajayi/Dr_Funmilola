import { QueryClient,QueryClientProvider } from '@tanstack/react-query';
import { fireEvent,render,screen,waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach,expect,test,vi } from 'vitest';
import PortalPage from './PortalPage';
import { api } from '../api';

vi.mock('../api',()=>({api:{get:vi.fn(),post:vi.fn(),put:vi.fn()}}));
const get=vi.mocked(api.get),post=vi.mocked(api.post);
const appointment={id:41,public_id:'appointment-41',starts_at:'2026-09-07T08:00:00Z',status:'confirmed',consultation_method:'in_person',service:{id:3,name:'Specialist review',slug:'review'},cancellation_request:{id:9,status:'declined'},reschedule_request:null,allowed_actions:['request_cancellation','request_reschedule']};

beforeEach(()=>{
  get.mockReset();post.mockReset();
  get.mockImplementation(async(url:string)=>{
    if(url==='/me')return {data:{user:{id:1,name:'Demo Patient',email_verified:true}}} as any;
    if(url==='/me/appointments')return {data:{data:[appointment],total:1}} as any;
    if(url==='/me/message-threads'||url==='/me/documents')return {data:{data:[]}} as any;
    if(url==='/me/notifications')return {data:{unread:0,data:[]}} as any;
    if(url==='/availability/3')return {data:{data:[{starts_at:'2026-09-14T09:00:00+01:00',ends_at:'2026-09-14T09:45:00+01:00',label:'9:00 AM'}]}} as any;
    return {data:{}} as any;
  });
  post.mockResolvedValue({data:{message:'ok'}} as any);
});

test('renders declined state and submits a server-derived reschedule option',async()=>{
  render(<QueryClientProvider client={new QueryClient({defaultOptions:{queries:{retry:false}}})}><MemoryRouter><PortalPage/></MemoryRouter></QueryClientProvider>);
  expect(await screen.findByText('Cancellation declined')).toBeInTheDocument();
  expect(screen.getByRole('button',{name:'Need to cancel?'})).toBeInTheDocument();
  fireEvent.click(screen.getByRole('button',{name:'Request a new time'}));
  fireEvent.change(screen.getByLabelText('Preferred date'),{target:{value:'2026-09-14'}});
  const slot=await screen.findByRole('button',{name:'9:00 AM'});
  fireEvent.click(slot);
  fireEvent.click(screen.getByRole('button',{name:'Request selected time'}));
  await waitFor(()=>expect(post).toHaveBeenCalledWith('/me/appointments/41/reschedule-request',{starts_at:'2026-09-14T09:00:00+01:00',reason:''}));
  expect(await screen.findByRole('status')).toHaveTextContent('preferred time was sent');
});
