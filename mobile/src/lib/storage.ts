import * as SecureStore from 'expo-secure-store';
const TOKEN_KEY = 'dr_funmilola_mobile_token_v1';
let webPreviewToken:string|null=null;
const isWebPreview=typeof document!=='undefined';
export const tokenStorage = {
  get: () => isWebPreview?Promise.resolve(webPreviewToken):SecureStore.getItemAsync(TOKEN_KEY),
  set: (token:string) => { if(isWebPreview){webPreviewToken=token;return Promise.resolve();} return SecureStore.setItemAsync(TOKEN_KEY,token,{keychainAccessible:SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY}); },
  clear: () => { if(isWebPreview){webPreviewToken=null;return Promise.resolve();} return SecureStore.deleteItemAsync(TOKEN_KEY); },
};
