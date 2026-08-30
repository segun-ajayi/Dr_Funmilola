import type { DataEnvelope,MobileDevice,MobileUser,NotificationPreference,PatientProfile } from '@dr-funmilola/mobile-contract';
import { useMutation,useQuery } from '@tanstack/react-query';
import { useEffect,useState } from 'react';
import { StyleSheet,Switch,Text,View } from 'react-native';
import { ApiClientError,apiRequest,mobileApi } from '@/lib/api';
import { createMutationId } from '@/lib/mutation';
import { Button,Card,Field,Heading,Loading,Notice,Screen,text } from '@/components/ui';
import { queryClient } from '@/providers/queryClient';
import { useSession } from '@/providers/SessionProvider';
import { colors,spacing } from '@/theme';

const message=(error:unknown)=>error instanceof ApiClientError?error.message:'Please try again.';
type Form={name:string;phone:string;date_of_birth:string;address:string;emergency_contact_name:string;emergency_contact_phone:string;preferred_communication:PatientProfile['preferred_communication']};

export default function Account(){
  const {session,refreshUser,signOut}=useSession();
  const p=session?.user.profile;
  const [form,setForm]=useState<Form>({name:'',phone:'',date_of_birth:'',address:'',emergency_contact_name:'',emergency_contact_phone:'',preferred_communication:'email'});
  useEffect(()=>setForm({name:session?.user.name??'',phone:session?.user.phone??'',date_of_birth:p?.date_of_birth??'',address:p?.address??'',emergency_contact_name:p?.emergency_contact_name??'',emergency_contact_phone:p?.emergency_contact_phone??'',preferred_communication:p?.preferred_communication??'email'}),[session?.user.name,session?.user.phone,p?.date_of_birth,p?.address,p?.emergency_contact_name,p?.emergency_contact_phone,p?.preferred_communication]);
  const preferences=useQuery({queryKey:['notification-preferences'],queryFn:()=>mobileApi.data<NotificationPreference>('/notification-preferences',session!.token),enabled:!!session});
  const devices=useQuery({queryKey:['devices'],queryFn:()=>mobileApi.data<MobileDevice[]>('/devices',session!.token),enabled:!!session});
  const profileMutation=useMutation({mutationFn:()=>apiRequest<DataEnvelope<MobileUser>>('/me',{method:'PATCH',token:session!.token,body:{client_request_id:createMutationId(),...form,phone:form.phone||null,date_of_birth:form.date_of_birth||null,address:form.address||null,emergency_contact_name:form.emergency_contact_name||null,emergency_contact_phone:form.emergency_contact_phone||null}}),onSuccess:async()=>{await refreshUser();}});
  const preferenceMutation=useMutation({mutationFn:(next:NotificationPreference)=>apiRequest<DataEnvelope<NotificationPreference>>('/notification-preferences',{method:'PUT',token:session!.token,body:{client_request_id:createMutationId(),in_app_reminders:next.in_app_reminders,email_reminders:next.email_reminders,push_reminders:false}}),onSuccess:data=>queryClient.setQueryData(['notification-preferences'],data.data)});
  const revokeMutation=useMutation({mutationFn:(id:number)=>apiRequest(`/devices/${id}`,{method:'DELETE',token:session!.token}),onSuccess:()=>queryClient.invalidateQueries({queryKey:['devices']})});
  const update=(key:keyof Form,value:string)=>setForm(current=>({...current,[key]:value}));
  const setPreference=(key:'in_app_reminders'|'email_reminders',value:boolean)=>{if(preferences.data)preferenceMutation.mutate({...preferences.data,[key]:value});};

  return <Screen>
    <Heading eyebrow="Account" title="Profile & security" body="Keep your personal and emergency-contact details current."/>
    <Card>
      <Text style={text.title}>Personal details</Text>
      <Text style={text.muted}>{session?.user.email} · Email changes require the practice.</Text>
      <Field label="Full name" value={form.name} onChangeText={value=>update('name',value)} autoCapitalize="words"/>
      <Field label="Phone" value={form.phone} onChangeText={value=>update('phone',value)} keyboardType="phone-pad"/>
      <Field label="Date of birth (YYYY-MM-DD)" value={form.date_of_birth} onChangeText={value=>update('date_of_birth',value)} autoCapitalize="none"/>
      <Field label="Address" value={form.address} onChangeText={value=>update('address',value)} multiline/>
      <Field label="Emergency contact name" value={form.emergency_contact_name} onChangeText={value=>update('emergency_contact_name',value)} autoCapitalize="words"/>
      <Field label="Emergency contact phone" value={form.emergency_contact_phone} onChangeText={value=>update('emergency_contact_phone',value)} keyboardType="phone-pad"/>
      <Text style={styles.label}>Preferred communication</Text>
      <View style={styles.choiceRow}>{(['email','phone','sms'] as const).map(choice=><Button key={choice} title={choice===form.preferred_communication?`✓ ${choice}`:choice} variant="secondary" onPress={()=>update('preferred_communication',choice)}/>)}</View>
      {profileMutation.isError?<Notice tone="error" title="Profile not saved" body={message(profileMutation.error)}/>:null}
      {profileMutation.isSuccess?<Notice title="Profile saved" body="Your care team can now see the updated details."/>:null}
      <Button title={profileMutation.isPending?'Saving…':'Save profile'} disabled={profileMutation.isPending||!form.name.trim()} onPress={()=>profileMutation.mutate()}/>
    </Card>
    <Card>
      <Text style={text.title}>Reminder preferences</Text>
      {preferences.isLoading?<Loading label="Loading preferences"/>:preferences.isError?<Notice tone="error" title="Preferences unavailable" body="Pull down or reopen this page to try again."/>:<>
        <View style={styles.switchRow}><Text style={text.body}>In-app reminders</Text><Switch accessibilityLabel="In-app reminders" value={preferences.data?.in_app_reminders??true} onValueChange={value=>setPreference('in_app_reminders',value)} trackColor={{true:colors.green}}/></View>
        <View style={styles.switchRow}><Text style={text.body}>Email reminders</Text><Switch accessibilityLabel="Email reminders" value={preferences.data?.email_reminders??true} onValueChange={value=>setPreference('email_reminders',value)} trackColor={{true:colors.green}}/></View>
        <Text style={text.muted}>Push reminders remain off until the secure push provider is configured.</Text>
        {preferenceMutation.isError?<Notice tone="error" title="Preferences not saved" body={message(preferenceMutation.error)}/>:null}
      </>}
    </Card>
    <Card>
      <Text style={text.title}>Signed-in devices</Text>
      {devices.isLoading?<Loading label="Loading devices"/>:devices.isError?<Notice tone="error" title="Devices unavailable" body="Try again shortly."/>:devices.data?.map(device=><View key={device.id} style={styles.device}>
        <View style={styles.grow}><Text style={text.body}>{device.name}{device.current?' (this device)':''}</Text><Text style={text.muted}>Last used {device.last_used_at?new Date(device.last_used_at).toLocaleString():'not yet'}</Text></View>
        {!device.current?<Button title="Revoke" variant="danger" disabled={revokeMutation.isPending} onPress={()=>revokeMutation.mutate(device.id)}/>:null}
      </View>)}
      {revokeMutation.isError?<Notice tone="error" title="Device not revoked" body={message(revokeMutation.error)}/>:null}
    </Card>
    <Button title="Sign out of this device" variant="danger" onPress={()=>void signOut()}/>
  </Screen>;
}

const styles=StyleSheet.create({label:{fontSize:15,fontWeight:'700',color:colors.ink},choiceRow:{gap:spacing.sm},switchRow:{minHeight:48,flexDirection:'row',alignItems:'center',justifyContent:'space-between'},device:{gap:spacing.sm,borderTopColor:colors.line,borderTopWidth:1,paddingTop:spacing.sm},grow:{flex:1}});
