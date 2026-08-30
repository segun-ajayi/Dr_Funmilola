import type { DataEnvelope,MessageThread,PracticeMessage } from '@dr-funmilola/mobile-contract';
import { useMutation } from '@tanstack/react-query';
import { useState } from 'react';
import { RefreshControl,Text,View } from 'react-native';
import { ApiClientError,apiRequest } from '@/lib/api';
import { createMutationId } from '@/lib/mutation';
import { Button,Card,Empty,Field,Heading,Loading,Notice,Screen,text } from '@/components/ui';
import { usePatientPage } from '@/hooks/usePatientPage';
import { queryClient } from '@/providers/queryClient';
import { useSession } from '@/providers/SessionProvider';
import { colors } from '@/theme';

const message=(error:unknown)=>error instanceof ApiClientError?error.message:'Please try again.';
export default function Messages(){
  const {session}=useSession();const q=usePatientPage<MessageThread>('messages','/message-threads?per_page=50');
  const [subject,setSubject]=useState('');const [body,setBody]=useState('');const [replies,setReplies]=useState<Record<number,string>>({});
  const create=useMutation({mutationFn:()=>apiRequest<DataEnvelope<MessageThread>>('/message-threads',{method:'POST',token:session!.token,body:{client_request_id:createMutationId(),subject:subject.trim(),body:body.trim()}}),onSuccess:async()=>{setSubject('');setBody('');await queryClient.invalidateQueries({queryKey:['messages']});}});
  const reply=useMutation({mutationFn:({threadId,body}:{threadId:number;body:string})=>apiRequest<DataEnvelope<PracticeMessage>>(`/message-threads/${threadId}/messages`,{method:'POST',token:session!.token,body:{client_request_id:createMutationId(),body}}),onSuccess:async(_,variables)=>{setReplies(current=>({...current,[variables.threadId]:''}));await queryClient.invalidateQueries({queryKey:['messages']});}});
  return <Screen refreshControl={<RefreshControl refreshing={q.isRefetching} onRefresh={()=>void q.refetch()} tintColor={colors.green}/>}>
    <Heading title="Messages" body="Private conversations with the practice. Do not use messaging for emergencies."/>
    <Notice title="Emergency help" body="If you have urgent or severe symptoms, contact local emergency services immediately."/>
    <Card><Text style={text.title}>Start a conversation</Text><Field label="Subject" value={subject} onChangeText={setSubject}/><Field label="Message" value={body} onChangeText={setBody} multiline/>{create.isError?<Notice tone="error" title="Message not sent" body={`${message(create.error)} Your draft is still here.`}/>:null}<Button title={create.isPending?'Sending…':'Send message'} disabled={create.isPending||!subject.trim()||!body.trim()} onPress={()=>create.mutate()}/></Card>
    {reply.isError?<Notice tone="error" title="Reply not sent" body={`${message(reply.error)} Your reply is still here.`}/>:null}
    {q.isLoading?<Loading/>:q.isError?<Notice tone="error" title="Messages unavailable" body="Pull down to try again."/>:q.data?.data.length===0?<Empty title="No conversations" body="Start a private conversation above."/>:q.data?.data.map(thread=><Card key={thread.public_id} label={thread.subject}><Text style={text.badge}>{thread.status}</Text><Text style={text.title}>{thread.subject}</Text>{thread.messages.map(item=><View key={item.id}><Text style={text.muted}>{item.sender.name} · {new Date(item.created_at).toLocaleString()}</Text><Text style={text.body}>{item.body}</Text></View>)}{thread.status==='open'?<><Field label={`Reply to ${thread.subject}`} value={replies[thread.id]??''} onChangeText={value=>setReplies(current=>({...current,[thread.id]:value}))} multiline/><Button title={reply.isPending?'Sending…':'Send reply'} variant="secondary" disabled={reply.isPending||!(replies[thread.id]??'').trim()} onPress={()=>reply.mutate({threadId:thread.id,body:(replies[thread.id]??'').trim()})}/></>:null}</Card>)}
  </Screen>;
}
