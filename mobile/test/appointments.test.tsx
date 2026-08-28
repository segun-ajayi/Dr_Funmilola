import { QueryClient,QueryClientProvider } from '@tanstack/react-query';
import { fireEvent,render,waitFor } from '@testing-library/react-native';
import Appointments from '../app/appointments';
import { apiRequest } from '@/lib/api';
import { usePatientPage } from '@/hooks/usePatientPage';

jest.mock('@/providers/SessionProvider',()=>({useSession:()=>({session:{token:'test-token'}})}));
jest.mock('@/hooks/usePatientPage',()=>({usePatientPage:jest.fn()}));
jest.mock('@/providers/queryClient',()=>({queryClient:{invalidateQueries:jest.fn().mockResolvedValue(undefined)}}));
jest.mock('@/lib/api',()=>({apiRequest:jest.fn(),ApiClientError:class ApiClientError extends Error{}}));
jest.mock('@/lib/mutation',()=>({createMutationId:()=> 'c78edcc5-e156-4ac8-a123-1d273c1aa999'}));

const mockedPage=jest.mocked(usePatientPage);
const mockedRequest=jest.mocked(apiRequest);
const base={id:1,public_id:'one',starts_at:'2026-09-07T09:00:00+01:00',ends_at:'2026-09-07T09:45:00+01:00',timezone:'Africa/Lagos',consultation_method:'in_person' as const,location:null,reason:'Review',service:{id:1,name:'Review',slug:'review'},cancellation_request:null,reschedule_request:null};

beforeEach(()=>{jest.clearAllMocks()});

it('shows only server-allowed actions and loads eligible reschedule times',async()=>{
  mockedPage.mockReturnValue({isLoading:false,isError:false,isRefetching:false,refetch:jest.fn(),data:{data:[{...base,status:'checked_in',allowed_actions:[]},{...base,id:2,public_id:'two',status:'confirmed',allowed_actions:['request_reschedule'] }],meta:{current_page:1,per_page:50,total:2,last_page:1},links:{next:null,previous:null}}} as any);
  mockedRequest.mockResolvedValueOnce({data:[{starts_at:'2026-09-14T09:00:00+01:00',ends_at:'2026-09-14T09:45:00+01:00',label:'9:00 AM'}]} as any);
  const view=await render(<QueryClientProvider client={new QueryClient()}><Appointments/></QueryClientProvider>);

  expect(view.queryByText('Request cancellation')).toBeNull();
  await fireEvent.press(view.getByRole('button',{name:'Request a new time'}));
  await fireEvent.changeText(await view.findByLabelText('Preferred date (YYYY-MM-DD)'),'2026-09-14');
  await fireEvent.press(view.getByRole('button',{name:'Find available times'}));
  await waitFor(()=>expect(mockedRequest).toHaveBeenCalled());
  expect(await view.findByRole('button',{name:'9:00 AM'})).toBeTruthy();
  expect(mockedRequest).toHaveBeenCalledWith('/appointments/2/reschedule-options?date=2026-09-14',{token:'test-token'});
});
