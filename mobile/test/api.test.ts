import { apiRequest } from '@/lib/api';
describe('mobile API client',()=>{
 afterEach(()=>jest.restoreAllMocks());
 it('adds a bearer token without exposing it in an error',async()=>{const fetchMock=jest.spyOn(globalThis,'fetch').mockResolvedValue(new Response(JSON.stringify({data:{ok:true}}),{status:200,headers:{'Content-Type':'application/json'}}));await expect(apiRequest('/me',{token:'private-token'})).resolves.toEqual({data:{ok:true}});expect(fetchMock).toHaveBeenCalledWith(expect.stringContaining('/me'),expect.objectContaining({headers:expect.objectContaining({Authorization:'Bearer private-token'})}));});
 it('normalises validation envelopes',async()=>{jest.spyOn(globalThis,'fetch').mockResolvedValue(new Response(JSON.stringify({error:{code:'validation_failed',message:'Check the form.',fields:{email:['Required.']}}}),{status:422,headers:{'Content-Type':'application/json'}}));await expect(apiRequest('/auth/token',{method:'POST'})).rejects.toMatchObject({code:'validation_failed',message:'Check the form.',status:422});});
 it('returns a safe offline message',async()=>{jest.spyOn(globalThis,'fetch').mockRejectedValue(new Error('socket details'));await expect(apiRequest('/me')).rejects.toMatchObject({code:'network_unavailable',message:'Unable to connect. Check your internet connection and try again.'});});
});
