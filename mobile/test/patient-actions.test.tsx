import { QueryClient,QueryClientProvider } from '@tanstack/react-query';
import { fireEvent,render,waitFor } from '@testing-library/react-native';
import type { ReactElement } from 'react';
import Consultations from '../app/consultations';
import Documents from '../app/documents';
import Messages from '../app/messages';
import Notifications from '../app/notifications';
import { apiRequest } from '@/lib/api';
import { pickPatientDocument } from '@/lib/documents';
import { usePatientPage } from '@/hooks/usePatientPage';

jest.mock('@/providers/SessionProvider',()=>({useSession:()=>({session:{token:'test-token'}})}));
jest.mock('@/hooks/usePatientPage',()=>({usePatientPage:jest.fn()}));
jest.mock('@/providers/queryClient',()=>({queryClient:{invalidateQueries:jest.fn().mockResolvedValue(undefined)}}));
jest.mock('@/lib/api',()=>({apiRequest:jest.fn(),ApiClientError:class ApiClientError extends Error{}}));
jest.mock('@/lib/mutation',()=>({createMutationId:()=> 'c78edcc5-e156-4ac8-a123-1d273c1aa999'}));
jest.mock('@/lib/documents',()=>({pickPatientDocument:jest.fn(),downloadAndShareDocument:jest.fn()}));

const mockedPage=jest.mocked(usePatientPage);
const mockedRequest=jest.mocked(apiRequest);
const mockedPicker=jest.mocked(pickPatientDocument);
const page=(data:unknown[])=>({isLoading:false,isError:false,isRefetching:false,refetch:jest.fn(),data:{data,meta:{current_page:1,per_page:50,total:data.length,last_page:1},links:{next:null,previous:null}}}) as never;
const clients:QueryClient[]=[];
const renderScreen=(screen:ReactElement)=>{const client=new QueryClient({defaultOptions:{queries:{gcTime:0,retry:false},mutations:{gcTime:0,retry:false,networkMode:'always'}}});clients.push(client);return render(<QueryClientProvider client={client}>{screen}</QueryClientProvider>);};

beforeEach(()=>{jest.clearAllMocks();mockedRequest.mockResolvedValue({data:{}} as never);});
afterEach(()=>{clients.splice(0).forEach(client=>client.clear());});

it('keeps a message draft available when sending fails',async()=>{
  mockedPage.mockReturnValue(page([]));
  mockedRequest.mockRejectedValueOnce(new Error('offline'));
  const view=await renderScreen(<Messages/>);
  await fireEvent.changeText(view.getByLabelText('Subject'),'Follow up');
  await fireEvent.changeText(view.getByLabelText('Message'),'My private draft');
  await fireEvent.press(view.getByRole('button',{name:'Send message'}));
  await view.findByText(/Your draft is still here/);
  expect(view.getByLabelText('Message').props.value).toBe('My private draft');
  await view.unmount();
});

it('uploads a selected document as multipart data',async()=>{
  mockedPage.mockReturnValue(page([]));
  mockedPicker.mockResolvedValue({uri:'file:///scan.pdf',name:'scan.pdf',mimeType:'application/pdf',size:200,lastModified:1} as never);
  const view=await renderScreen(<Documents/>);
  await fireEvent.changeText(view.getByLabelText('Document label'),'Pathology report');
  await fireEvent.press(view.getByRole('button',{name:'Choose PDF or image'}));
  await view.findByRole('button',{name:'Selected: scan.pdf'});
  await fireEvent.press(view.getByRole('button',{name:'Upload securely'}));
  await waitFor(()=>expect(mockedRequest).toHaveBeenCalledWith('/documents',expect.objectContaining({method:'POST',token:'test-token',body:expect.any(FormData)})));
  await view.unmount();
});

it('marks an owned notification as read',async()=>{
  mockedPage.mockReturnValue(page([{id:'notification-one',type:'portal',data:{title:'Appointment updated',message:'Your time changed.',kind:'appointment'},read_at:null,created_at:'2026-08-30T12:00:00Z'}]));
  const view=await renderScreen(<Notifications/>);
  await fireEvent.press(view.getByRole('button',{name:'Mark as read'}));
  await waitFor(()=>expect(mockedRequest).toHaveBeenCalledWith('/notifications/notification-one/read',{method:'PATCH',token:'test-token',body:{client_request_id:'c78edcc5-e156-4ac8-a123-1d273c1aa999'}}));
  await view.unmount();
});

it('records consultation consent without requesting camera or microphone access',async()=>{
  mockedPage.mockReturnValue(page([{id:8,public_id:'consultation-one',status:'scheduled',has_consent:false,appointment:{starts_at:'2026-08-30T12:00:00Z',ends_at:'2026-08-30T12:45:00Z',service:{id:1,name:'Review'}}}]));
  const view=await renderScreen(<Consultations/>);
  expect(view.getByText('Live video is not active')).toBeTruthy();
  await fireEvent.press(view.getByRole('button',{name:'Review and accept consent'}));
  await waitFor(()=>expect(mockedRequest).toHaveBeenCalledWith('/consultations/8/consent',{method:'POST',token:'test-token',body:{client_request_id:'c78edcc5-e156-4ac8-a123-1d273c1aa999',accepted:true}}));
  await view.unmount();
});
