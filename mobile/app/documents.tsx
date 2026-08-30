import type { DataEnvelope,PatientDocument } from '@dr-funmilola/mobile-contract';
import { useMutation } from '@tanstack/react-query';
import { useState } from 'react';
import { RefreshControl,Text } from 'react-native';
import { ApiClientError,apiRequest } from '@/lib/api';
import { downloadAndShareDocument,pickPatientDocument } from '@/lib/documents';
import { createMutationId } from '@/lib/mutation';
import { Button,Card,Empty,Field,Heading,Loading,Notice,Screen,text } from '@/components/ui';
import { usePatientPage } from '@/hooks/usePatientPage';
import { queryClient } from '@/providers/queryClient';
import { useSession } from '@/providers/SessionProvider';
import { colors } from '@/theme';

const size=(bytes:number)=>bytes<1024*1024?`${Math.ceil(bytes/1024)} KB`:`${(bytes/1024/1024).toFixed(1)} MB`;
const message=(error:unknown)=>error instanceof ApiClientError?error.message:error instanceof Error?error.message:'Please try again.';

export default function Documents(){
  const {session}=useSession();
  const q=usePatientPage<PatientDocument>('documents','/documents?per_page=50');
  const [label,setLabel]=useState('');
  const [asset,setAsset]=useState<Awaited<ReturnType<typeof pickPatientDocument>>>(null);
  const upload=useMutation({mutationFn:async()=>{const form=new FormData();form.append('client_request_id',createMutationId());form.append('label',label.trim());form.append('document',{uri:asset!.uri,name:asset!.name,mimeType:asset!.mimeType??'application/octet-stream',type:asset!.mimeType??'application/octet-stream'} as never);return apiRequest<DataEnvelope<PatientDocument>>('/documents',{method:'POST',token:session!.token,body:form});},onSuccess:async()=>{setAsset(null);setLabel('');await queryClient.invalidateQueries({queryKey:['documents']});}});
  const download=useMutation({mutationFn:(document:PatientDocument)=>downloadAndShareDocument(document.id,document.original_name,session!.token)});
  const choose=async()=>setAsset(await pickPatientDocument());

  return <Screen refreshControl={<RefreshControl refreshing={q.isRefetching} onRefresh={()=>void q.refetch()} tintColor={colors.green}/>}>
    <Heading title="Documents" body="Securely upload, open, or save files shared with your care team."/>
    <Card>
      <Text style={text.title}>Upload a document</Text>
      <Field label="Document label" value={label} onChangeText={setLabel} placeholder="e.g. Pathology report"/>
      <Button title={asset?`Selected: ${asset.name}`:'Choose PDF or image'} variant="secondary" onPress={()=>void choose()}/>
      <Text style={text.muted}>PDF, JPG or PNG · Maximum 10 MB · Files are security-scanned before storage.</Text>
      {upload.isError?<Notice tone="error" title="Upload not completed" body={`${message(upload.error)} Your selection is still here so you can retry.`}/>:null}
      {upload.isSuccess?<Notice title="Upload complete" body="The scanned file is now available to your care team."/>:null}
      <Button title={upload.isPending?'Scanning and uploading…':'Upload securely'} disabled={upload.isPending||!asset||!label.trim()} onPress={()=>upload.mutate()}/>
    </Card>
    {download.isError?<Notice tone="error" title="Document not opened" body={message(download.error)}/>:null}
    {q.isLoading?<Loading/>:q.isError?<Notice tone="error" title="Documents unavailable" body="Pull down to try again."/>:q.data?.data.length===0?<Empty title="No documents" body="Upload a file above or wait for files shared by your care team."/>:q.data?.data.map(document=><Card key={document.public_id} label={document.label}>
      <Text style={text.title}>{document.label}</Text><Text style={text.body}>{document.original_name}</Text><Text style={text.muted}>{size(document.size_bytes)} · Added {new Date(document.created_at).toLocaleDateString()}</Text>
      <Button title={download.isPending?'Preparing…':'Open or save securely'} variant="secondary" disabled={download.isPending} onPress={()=>download.mutate(document)}/>
    </Card>)}
  </Screen>;
}
