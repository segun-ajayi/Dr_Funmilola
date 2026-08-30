import type { AuthTokenResponse,DataEnvelope,MobileUser } from '@dr-funmilola/mobile-contract';
import Constants from 'expo-constants';
import { createContext,PropsWithChildren,useCallback,useContext,useEffect,useMemo,useState } from 'react';
import { apiRequest,mobileApi } from '@/lib/api';
import { tokenStorage } from '@/lib/storage';
import { queryClient } from './queryClient';
type Session={token:string;user:MobileUser};
type Value={session:Session|null;restoring:boolean;refreshUser():Promise<void>;signIn(email:string,password:string):Promise<void>;signOut():Promise<void>};
const Context=createContext<Value|null>(null);
export function SessionProvider({children}:PropsWithChildren){
 const [session,setSession]=useState<Session|null>(null); const [restoring,setRestoring]=useState(true);
 const clearSession=useCallback(async()=>{setSession(null);queryClient.clear();await tokenStorage.clear();},[]);
 useEffect(()=>{void(async()=>{const token=await tokenStorage.get();if(token){try{setSession({token,user:await mobileApi.data<MobileUser>('/me',token)});}catch{await clearSession();}}setRestoring(false);})();},[clearSession]);
 const refreshUser=useCallback(async()=>{if(!session?.token)return;const user=await mobileApi.data<MobileUser>('/me',session.token);setSession(current=>current?{...current,user}:current);},[session?.token]);
 const signIn=useCallback(async(email:string,password:string)=>{const result=await apiRequest<DataEnvelope<AuthTokenResponse>>('/auth/token',{method:'POST',body:{email:email.trim().toLowerCase(),password,device_name:Constants.deviceName??'Mobile app'}});await tokenStorage.set(result.data.access_token);try{setSession({token:result.data.access_token,user:await mobileApi.data<MobileUser>('/me',result.data.access_token)});}catch(error){await clearSession();throw error;}},[clearSession]);
 const signOut=useCallback(async()=>{const token=session?.token;await clearSession();if(token)await apiRequest('/auth/token',{method:'DELETE',token}).catch(()=>undefined);},[clearSession,session?.token]);
 const value=useMemo(()=>({session,restoring,refreshUser,signIn,signOut}),[session,restoring,refreshUser,signIn,signOut]);return <Context.Provider value={value}>{children}</Context.Provider>;
}
export function useSession(){const value=useContext(Context);if(!value)throw new Error('useSession must be used inside SessionProvider');return value;}
