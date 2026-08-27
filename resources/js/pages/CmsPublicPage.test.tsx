import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { vi } from 'vitest';
import CmsPublicPage from './CmsPublicPage';

const get=vi.fn();
vi.mock('../api',()=>({api:{get:(...args:unknown[])=>get(...args)}}));
function view(path='/p/guide'){return render(<QueryClientProvider client={new QueryClient({defaultOptions:{queries:{retry:false}}})}><MemoryRouter initialEntries={[path]}><Routes><Route path="/p/:slug" element={<CmsPublicPage/>}/><Route path="/" element={<div>Home</div>}/></Routes></MemoryRouter></QueryClientProvider>)}
test('renders visible structured sections with accessible links and hides draft-hidden sections',async()=>{get.mockResolvedValueOnce({data:{data:{sections:[{section_key:'a',type:'hero',is_visible:true,content:{eyebrow:'Patient guide',heading:'Prepare with confidence',text:'Clear appointment information.',primary_label:'Book care',primary_url:'/book'},presentation:{background:'ivory'}},{section_key:'b',type:'text',is_visible:false,content:{heading:'Hidden draft',body:'Not public'},presentation:{}}]}}});view();expect(await screen.findByRole('heading',{name:'Prepare with confidence'})).toBeInTheDocument();expect(screen.getByRole('link',{name:/Book care/})).toHaveAttribute('href','/book');expect(screen.queryByText('Hidden draft')).not.toBeInTheDocument();});
test('shows a friendly unavailable state when the public API rejects the page',async()=>{get.mockRejectedValueOnce(new Error('not found'));view();expect(await screen.findByRole('heading',{name:'This page is not available.'})).toBeInTheDocument();expect(screen.getByRole('link',{name:'Return home'})).toBeInTheDocument();});
