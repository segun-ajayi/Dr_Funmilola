import { useQuery } from '@tanstack/react-query';
import { mobileApi } from '@/lib/api';import { useSession } from '@/providers/SessionProvider';
export function usePatientPage<T>(key:string,path:string){const {session}=useSession();return useQuery({queryKey:[key],queryFn:()=>mobileApi.page<T>(path,session!.token),enabled:!!session});}
