import { QueryClientProvider } from '@tanstack/react-query';
import { PropsWithChildren } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { SessionProvider } from './SessionProvider';import { queryClient } from './queryClient';
export function AppProviders({children}:PropsWithChildren){return <SafeAreaProvider><QueryClientProvider client={queryClient}><SessionProvider>{children}</SessionProvider></QueryClientProvider></SafeAreaProvider>;}
