import * as DocumentPicker from 'expo-document-picker';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { API_BASE_URL } from './api';

export async function pickPatientDocument(){
  const result=await DocumentPicker.getDocumentAsync({type:['application/pdf','image/jpeg','image/png'],copyToCacheDirectory:true,multiple:false});
  return result.canceled?null:result.assets[0];
}

export async function downloadAndShareDocument(id:number,fileName:string,token:string){
  if(!FileSystem.cacheDirectory)throw new Error('Temporary file storage is unavailable.');
  const safeName=fileName.replace(/[^a-zA-Z0-9._-]/g,'_');
  const destination=`${FileSystem.cacheDirectory}${id}-${safeName}`;
  const result=await FileSystem.downloadAsync(`${API_BASE_URL}/documents/${id}/download`,destination,{headers:{Accept:'application/octet-stream',Authorization:`Bearer ${token}`}});
  if(result.status<200||result.status>=300)throw new Error('The document could not be downloaded.');
  if(!await Sharing.isAvailableAsync())throw new Error('Sharing is unavailable on this device.');
  await Sharing.shareAsync(result.uri,{dialogTitle:'Open or save document'});
  return result.uri;
}
