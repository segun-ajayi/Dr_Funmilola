import type { ConsultationConnection,DataEnvelope,MobileConsultation } from '@dr-funmilola/mobile-contract';
import { useMutation } from '@tanstack/react-query';
import { RefreshControl,Text } from 'react-native';
import { ApiClientError,apiRequest } from '@/lib/api';
import { createMutationId } from '@/lib/mutation';
import { Button,Card,Empty,Heading,Loading,Notice,Screen,text } from '@/components/ui';
import { usePatientPage } from '@/hooks/usePatientPage';
import { queryClient } from '@/providers/queryClient';
import { useSession } from '@/providers/SessionProvider';
import { colors } from '@/theme';

const message=(error:unknown)=>error instanceof ApiClientError?error.message:'Please try again.';
type Action='consent'|'waiting-room'|'join'|'leave';
export default function Consultations(){
  const {session}=useSession();const q=usePatientPage<MobileConsultation>('consultations','/consultations?per_page=50');
  const action=useMutation({mutationFn:({consultation,action}:{consultation:MobileConsultation;action:Action})=>apiRequest<DataEnvelope<MobileConsultation|ConsultationConnection|null>>(`/consultations/${consultation.id}/${action}`,{method:'POST',token:session!.token,body:{client_request_id:createMutationId(),...(action==='consent'?{accepted:true}:{})}}),onSuccess:()=>queryClient.invalidateQueries({queryKey:['consultations']})});
  return <Screen refreshControl={<RefreshControl refreshing={q.isRefetching} onRefresh={()=>void q.refetch()} tintColor={colors.green}/>}><Heading title="Consultations" body="Give consent, enter the waiting room, and follow your admission status."/><Notice title="Live video is not active" body="The app will not request camera or microphone access. Connection controls will activate only after an approved secure video provider is configured."/>{action.isError?<Notice tone="error" title="Consultation not updated" body={message(action.error)}/>:null}{action.isSuccess&&'configuration' in (action.data?.data??{})&&!((action.data?.data as ConsultationConnection).configuration.ready)?<Notice title="Video provider pending" body={(action.data?.data as ConsultationConnection).configuration.message??'Live video is not configured.'}/>:null}{q.isLoading?<Loading/>:q.isError?<Notice tone="error" title="Consultations unavailable" body="Pull down to try again."/>:q.data?.data.length===0?<Empty title="No consultations" body="Scheduled online consultations will appear here."/>:q.data?.data.map(consultation=><Card key={consultation.id} label={`${consultation.appointment.service.name} consultation`}><Text style={text.badge}>{consultation.status}</Text><Text style={text.title}>{consultation.appointment.service.name}</Text><Text style={text.body}>{new Date(consultation.appointment.starts_at).toLocaleString()}</Text><Text style={text.muted}>{consultation.has_consent?'Consent recorded':'Consent is required before entering the waiting room.'}</Text>{!consultation.has_consent&&consultation.status!=='ended'?<Button title="Review and accept consent" variant="secondary" disabled={action.isPending} onPress={()=>action.mutate({consultation,action:'consent'})}/>:null}{consultation.has_consent&&consultation.status==='scheduled'?<Button title="Enter waiting room" disabled={action.isPending} onPress={()=>action.mutate({consultation,action:'waiting-room'})}/>:null}{consultation.has_consent&&['ready','in_progress'].includes(consultation.status)?<Button title="Prepare secure connection" disabled={action.isPending} onPress={()=>action.mutate({consultation,action:'join'})}/>:null}</Card>)}</Screen>;
}
