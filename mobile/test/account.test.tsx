import { QueryClient,QueryClientProvider } from '@tanstack/react-query';
import { fireEvent,render,waitFor } from '@testing-library/react-native';
import Account from '../app/(tabs)/account';
import { apiRequest,mobileApi } from '@/lib/api';

const mockRefreshUser=jest.fn().mockResolvedValue(undefined);
jest.mock('@/providers/SessionProvider',()=>({useSession:()=>({session:{token:'test-token',user:{id:1,name:'Patient One',email:'patient@example.test',phone:null,profile:{date_of_birth:null,address:null,emergency_contact_name:null,emergency_contact_phone:null,preferred_communication:'email'}}},refreshUser:mockRefreshUser,signOut:jest.fn()})}));
jest.mock('@/providers/queryClient',()=>({queryClient:{setQueryData:jest.fn(),invalidateQueries:jest.fn().mockResolvedValue(undefined)}}));
jest.mock('@/lib/api',()=>({apiRequest:jest.fn(),mobileApi:{data:jest.fn()},ApiClientError:class ApiClientError extends Error{}}));
jest.mock('@/lib/mutation',()=>({createMutationId:()=> 'c78edcc5-e156-4ac8-a123-1d273c1aa999'}));

const mockedRequest=jest.mocked(apiRequest);
const mockedData=jest.mocked(mobileApi.data);

beforeEach(()=>{
  jest.clearAllMocks();
  mockedData.mockImplementation((path:string)=>Promise.resolve(path==='/devices'?[]:{id:1,in_app_reminders:true,email_reminders:true,push_reminders:false}) as never);
  mockedRequest.mockResolvedValue({data:{}} as never);
});

it('edits and saves the patient profile in the shared Android and iOS screen',async()=>{
  const client=new QueryClient({defaultOptions:{queries:{gcTime:0,retry:false},mutations:{gcTime:0,retry:false}}});
  const view=await render(<QueryClientProvider client={client}><Account/></QueryClientProvider>);
  await fireEvent.changeText(view.getByLabelText('Full name'),'Updated Patient');
  await fireEvent.changeText(view.getByLabelText('Phone'),'+2348000000000');
  await fireEvent.press(view.getByRole('button',{name:'sms'}));
  await fireEvent.press(view.getByRole('button',{name:'Save profile'}));
  await waitFor(()=>expect(mockedRequest).toHaveBeenCalledWith('/me',expect.objectContaining({method:'PATCH',token:'test-token',body:expect.objectContaining({name:'Updated Patient',phone:'+2348000000000',preferred_communication:'sms'})})));
  expect(mockRefreshUser).toHaveBeenCalled();
  await view.unmount();client.clear();
});
