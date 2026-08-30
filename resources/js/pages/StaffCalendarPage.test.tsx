import { QueryClient,QueryClientProvider } from '@tanstack/react-query';
import { fireEvent,render,screen,waitFor,within } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach,expect,test,vi } from 'vitest';
import StaffCalendarPage from './StaffCalendarPage';
import { api } from '../api';

vi.mock('../api',()=>({api:{get:vi.fn(),post:vi.fn(),put:vi.fn(),patch:vi.fn(),delete:vi.fn()}}));
const get=vi.mocked(api.get),post=vi.mocked(api.post),patchRequest=vi.mocked(api.patch);
const date=new Intl.DateTimeFormat('en-CA',{timeZone:'Africa/Lagos',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
const calendar={data:[{id:31,title:'Specialist review',start:`${date}T08:00:00Z`,end:`${date}T08:45:00Z`,status:'confirmed',method:'in_person',patient:{id:8,name:'Ada Patient',email:'ada@example.test',phone:'08010000000'},service:{id:2,name:'Specialist review',slug:'review'},reason:'Follow-up review',location:'Main clinic',administrative_notes:'Bring imaging',allowed_statuses:['checked_in','cancelled','rescheduled','no_show']}],timezone:'Africa/Lagos',filters:{services:[{id:2,name:'Specialist review',slug:'review',duration_minutes:45,online_available:true}],statuses:['requested','confirmed','completed','cancelled']}};

function view(){return render(<QueryClientProvider client={new QueryClient({defaultOptions:{queries:{retry:false}}})}><MemoryRouter><StaffCalendarPage/></MemoryRouter></QueryClientProvider>)}
beforeEach(()=>{get.mockReset();post.mockReset();patchRequest.mockReset();get.mockImplementation(async(url:string)=>{if(url==='/staff/calendar')return{data:calendar} as any;if(url==='/staff/availability-rules')return{data:{data:[{id:1,weekday:1,start_time:'09:00:00',end_time:'13:00:00',slot_minutes:45,buffer_minutes:15,consultation_method:'both',is_active:true}]}} as any;if(url==='/staff/availability-exceptions')return{data:{data:[]}} as any;if(url==='/staff/patients/search')return{data:{data:[{id:8,name:'Ada Patient',email:'ada@example.test',phone:'08010000000'}]}} as any;if(url==='/availability/2')return{data:{data:[{starts_at:`${date}T09:00:00+01:00`,ends_at:`${date}T09:45:00+01:00`,label:'9:00 AM'}]}} as any;return{data:{data:[]}} as any});post.mockResolvedValue({data:{data:{id:99}}} as any);patchRequest.mockResolvedValue({data:{data:{}}} as any)});

test('offers distinct accessible views, filters and appointment details',async()=>{
 view();
 expect(await screen.findByRole('region',{name:'week calendar'})).toBeInTheDocument();
 expect(await screen.findByRole('button',{name:/Ada Patient/})).toBeInTheDocument();
 fireEvent.click(screen.getByRole('button',{name:'day'}));
 expect(screen.getByRole('region',{name:'day calendar'})).toBeInTheDocument();
 fireEvent.click(screen.getByRole('button',{name:'agenda'}));
 expect(screen.getByRole('region',{name:'agenda calendar'})).toBeInTheDocument();
 fireEvent.change(screen.getByLabelText('Status'),{target:{value:'completed'}});
 expect(screen.getByText('0 appointments')).toBeInTheDocument();
 fireEvent.change(screen.getByLabelText('Status'),{target:{value:''}});
 fireEvent.click(await screen.findByRole('button',{name:/Ada Patient/}));
 expect(screen.getByRole('dialog',{name:'Ada Patient'})).toHaveTextContent('Bring imaging');
 expect(screen.getByRole('button',{name:'checked in'})).toBeInTheDocument();
});

test('failed keyboard-equivalent move leaves the event in place and announces recovery',async()=>{
 patchRequest.mockRejectedValueOnce({response:{data:{message:'That target time conflicts with another appointment.'}}});
 view();
 fireEvent.click(await screen.findByRole('button',{name:/Ada Patient/}));
 fireEvent.click(screen.getByRole('button',{name:/Move one day later/}));
 expect(await screen.findByRole('alert')).toHaveTextContent('conflicts');
 expect(screen.getByRole('button',{name:/Ada Patient/})).toBeInTheDocument();
});

test('staff can search an existing patient and create from server availability',async()=>{
 view();
 fireEvent.click(await screen.findByRole('button',{name:/New appointment/}));
 fireEvent.change(screen.getByLabelText('Find an existing patient'),{target:{value:'Ada'}});
 fireEvent.click(screen.getByRole('button',{name:'Search'}));
 const dialog=screen.getByRole('dialog',{name:'New appointment'});
 fireEvent.click(await within(dialog).findByRole('button',{name:/Ada Patient/}));
 fireEvent.change(within(dialog).getByLabelText('Service'),{target:{value:'2'}});
 await waitFor(()=>expect(get).toHaveBeenCalledWith('/availability/2',expect.objectContaining({params:expect.objectContaining({method:'in_person'})})));
 fireEvent.click(await within(dialog).findByRole('button',{name:'9:00 AM'}));
 fireEvent.change(within(dialog).getByLabelText('Reason'),{target:{value:'Planned follow-up'}});
 fireEvent.click(within(dialog).getByRole('button',{name:'Create and confirm appointment'}));
 await waitFor(()=>expect(post).toHaveBeenCalledWith('/staff/appointments',expect.objectContaining({patient_id:8,service_id:2,reason:'Planned follow-up'})));
});
