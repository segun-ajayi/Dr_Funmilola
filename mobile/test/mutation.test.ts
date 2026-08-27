jest.mock('expo-crypto',()=>({randomUUID:jest.fn(()=> 'c78edcc5-e156-4ac8-a123-1d273c1aa999')}));
import { createMutationId } from '@/lib/mutation';
it('creates a stable UUID request identifier for each cancellation submission',()=>{expect(createMutationId()).toBe('c78edcc5-e156-4ac8-a123-1d273c1aa999');});
