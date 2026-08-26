export type Service = { id: number; name: string; slug: string; summary: string; description?: string; duration_minutes: number; online_available: boolean };
export type Publication = { id: number; title: string; authors?: string; journal?: string; published_at?: string; external_url?: string; category: string };
export type PublicData = { services: Service[]; publications: Publication[]; profile: { name: string; title: string; location: string; orcid: string } };
export type Slot = { starts_at: string; ends_at: string; label: string };
