import axios from 'axios';

export const api = axios.create({ baseURL: '/api', withCredentials: true, withXSRFToken: true, headers: { Accept: 'application/json' } });

export async function prepareSession() {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}
