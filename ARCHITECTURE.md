# Architecture

The platform is an API-first modular Laravel monolith with a React/TypeScript web client. Android and iOS applications will consume the same versioned REST API when mobile delivery begins. Laravel owns identity, authorization, scheduling rules, clinical privacy, content publishing, notifications and audit records.

Production services are PostgreSQL 17+, Redis, queue workers, the Laravel scheduler, private object storage and an abstracted transactional mail provider. Local automated tests use SQLite for speed and isolation.

Core domains are Identity, Patients, Appointments, Consultations, Messaging, Content/CMS, Academic Research, Media, Notifications and Audit. Domain boundaries are expressed through models, services, policies, events and API resources inside one deployable application.
