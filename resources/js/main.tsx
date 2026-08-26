import React from 'react';
import ReactDOM from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';
import '../css/app.css';
import App from './App';

ReactDOM.createRoot(document.getElementById('app')!).render(
  <React.StrictMode><QueryClientProvider client={new QueryClient()}><BrowserRouter><App /></BrowserRouter></QueryClientProvider></React.StrictMode>,
);
