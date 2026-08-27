import * as Crypto from 'expo-crypto';
export const createMutationId=()=>Crypto.randomUUID();
