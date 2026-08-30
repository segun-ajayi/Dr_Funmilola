import type { DataEnvelope,MobileNotification } from '@dr-funmilola/mobile-contract';
import { useMutation } from '@tanstack/react-query';
import { RefreshControl,Text } from 'react-native';
import { ApiClientError,apiRequest } from '@/lib/api';
import { createMutationId } from '@/lib/mutation';
import { Button,Card,Empty,Heading,Loading,Notice,Screen,text } from '@/components/ui';
import { usePatientPage } from '@/hooks/usePatientPage';
import { queryClient } from '@/providers/queryClient';
import { useSession } from '@/providers/SessionProvider';
import { colors } from '@/theme';

export default function Notifications(){
  const {session}=useSession();const q=usePatientPage<MobileNotification>('notifications','/notifications?per_page=50');
  const read=useMutation({mutationFn:(id:string)=>apiRequest<DataEnvelope<{id:string;read_at:string}>>(`/notifications/${id}/read`,{method:'PATCH',token:session!.token,body:{client_request_id:createMutationId()}}),onSuccess:()=>queryClient.invalidateQueries({queryKey:['notifications']})});
  return <Screen refreshControl={<RefreshControl refreshing={q.isRefetching} onRefresh={()=>void q.refetch()} tintColor={colors.green}/>}><Heading title="Notifications" body="Account and care updates from the practice."/>{read.isError?<Notice tone="error" title="Notification not updated" body={read.error instanceof ApiClientError?read.error.message:'Please try again.'}/>:null}{q.isLoading?<Loading/>:q.isError?<Notice tone="error" title="Notifications unavailable" body="Pull down to try again."/>:q.data?.data.length===0?<Empty title="You're up to date" body="New updates will appear here."/>:q.data?.data.map(item=><Card key={item.id} label={item.data.title}><Text style={text.badge}>{item.read_at?'Read':'New'}</Text><Text style={text.title}>{item.data.title}</Text><Text style={text.body}>{item.data.message}</Text><Text style={text.muted}>{new Date(item.created_at).toLocaleString()}</Text>{!item.read_at?<Button title="Mark as read" variant="secondary" disabled={read.isPending} onPress={()=>read.mutate(item.id)}/>:null}</Card>)}</Screen>;
}
