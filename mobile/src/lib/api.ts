import type { ApiError, DataEnvelope, PaginatedEnvelope } from '@dr-funmilola/mobile-contract';
const rawBaseUrl = process.env.EXPO_PUBLIC_API_URL ?? 'http://127.0.0.1:8000/api/v1';
export const API_BASE_URL = rawBaseUrl.replace(/\/$/, '');

export class ApiClientError extends Error {
  constructor(public code:string, message:string, public fields?:Record<string,string[]>, public status?:number) { super(message); this.name='ApiClientError'; }
}
type RequestOptions = Omit<RequestInit,'body'> & { body?:unknown; token?:string|null };
export async function apiRequest<T>(path:string, options:RequestOptions={}):Promise<T> {
  const { body, token, headers, ...rest } = options;
  const isFormData=typeof FormData!=='undefined'&&body instanceof FormData;
  let response:Response;
  try {
    response = await fetch(`${API_BASE_URL}${path}`, { ...rest, body:body===undefined?undefined:isFormData?body:JSON.stringify(body), headers:{ Accept:'application/json', ...(body===undefined||isFormData?{}:{'Content-Type':'application/json'}), ...(token?{Authorization:`Bearer ${token}`}:{}) , ...headers } });
  } catch { throw new ApiClientError('network_unavailable','Unable to connect. Check your internet connection and try again.'); }
  if (response.status===204) return undefined as T;
  const payload = await response.json().catch(()=>null) as T|ApiError|null;
  if (!response.ok) {
    const error=payload&&typeof payload==='object'&&'error' in payload?payload.error:null;
    throw new ApiClientError(error?.code??'request_failed',error?.message??'The request could not be completed.',error?.fields,response.status);
  }
  return payload as T;
}
export const mobileApi={
  data:<T>(path:string,token?:string|null)=>apiRequest<DataEnvelope<T>>(path,{token}).then(r=>r.data),
  page:<T>(path:string,token:string)=>apiRequest<PaginatedEnvelope<T>>(path,{token}),
};
